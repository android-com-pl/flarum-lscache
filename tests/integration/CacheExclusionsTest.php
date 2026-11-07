<?php

namespace Acpl\FlarumLSCache\Tests\integration;

use Flarum\Extend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Exception\PermissionDeniedException;
use Laminas\Diactoros\Response\EmptyResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CacheExclusionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('acpl-lscache');
        $this->prepareDatabase([
            'users' => [$this->normalUser()],
            'unsubscribe_tokens' => [[
                'user_id' => 2,
                'email_type' => 'newPost',
                'token' => 'lscache-unsubscribe-token',
            ]],
            'email_tokens' => [[
                'token' => 'lscache-email-token',
                'user_id' => 2,
                'email' => 'normal@example.com',
                'created_at' => date('Y-m-d H:i:s'),
            ]],
        ]);
    }

    public static function coreRequests(): array
    {
        return [
            'unsubscribe form' => ['GET', '/notifications/unsubscribe/2/lscache-unsubscribe-token', 200],
            'unsubscribe HEAD with trailing slash' => ['HEAD', '/notifications/unsubscribe/2/lscache-unsubscribe-token/', 200],
            'email confirmation form' => ['GET', '/confirm/lscache-email-token', 200],
            'invalid email token' => ['HEAD', '/confirm/invalid-token', 403],
            'private notifications' => ['GET', '/notifications', 401],
            'logout page' => ['GET', '/logout', 302],
        ];
    }

    #[DataProvider('coreRequests')]
    public function test_core_session_and_token_pages_are_not_cached(string $method, string $path, int $status): void
    {
        $response = $this->send($this->request($method, $path));

        self::assertSame($status, $response->getStatusCode());
        self::assertSame('no-cache', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
        if ($method === 'GET' && $status === 200) {
            self::assertStringContainsString('name="csrfToken"', (string) $response->getBody());
        }
    }

    public function test_maintenance_login_form_is_not_cached(): void
    {
        $this->setting('maintenance_mode', 'low');

        $response = $this->send($this->request('GET', '/maintenance/login'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('name="csrfToken"', (string) $response->getBody());
        self::assertSame('no-cache', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
    }

    public static function extensionRequests(): array
    {
        return [
            'GDPR confirmation' => ['forum', 'GET', '/gdpr/erasure/confirm/token', 200],
            'GDPR export' => ['forum', 'HEAD', '/gdpr/export/private.zip', 200],
            'twofactor OAuth form' => ['forum', 'GET', '/twofactor/oauth/verify', 200],
            'other twofactor page' => ['forum', 'HEAD', '/twofactor/recovery', 200],
            'other maintenance page' => ['forum', 'GET', '/maintenance/status', 200],
            'Clockwork denied response' => ['forum', 'HEAD', '/__clockwork/latest', 403],
            'Upload UUID download' => ['api', 'GET', '/fof/download/uuid', 200],
            'Upload CSRF download' => ['api', 'HEAD', '/fof/download/uuid/1/csrf/', 200],
            'GDPR thrown error' => ['forum', 'HEAD', '/gdpr/erasure/confirm/token', 403],
            'Upload thrown error' => ['api', 'GET', '/fof/download/uuid', 403],
        ];
    }

    #[DataProvider('extensionRequests')]
    public function test_extension_paths_are_excluded_under_subdirectory_and_custom_api_mount(string $app, string $method, string $path, int $status): void
    {
        $this->config('url', 'http://localhost/community');
        $this->config('paths.api', 'rest');
        // Exercise the real stack without installing optional extensions or performing their actions.
        $this->extend((new Extend\Routes($app))->get(rtrim($path, '/'), 'lscache.exclusion-test', fn () => new class($status) implements RequestHandlerInterface {
            public function __construct(private int $status)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($this->status === 403 && $request->getUri()->getPath() !== '/__clockwork/latest') {
                    throw new PermissionDeniedException();
                }

                return new EmptyResponse($this->status);
            }
        }));

        $response = $this->send($this->request($method, '/community'.($app === 'api' ? '/rest' : '').$path));

        self::assertSame($status, $response->getStatusCode());
        self::assertSame('no-cache', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
    }

    public static function unresolvedRequests(): array
    {
        return [
            'namespace root' => ['GET', '/gdpr', 404],
            'namespace root with slash' => ['HEAD', '/__clockwork/', 404],
            'maintenance namespace with slash' => ['HEAD', '/maintenance/', 404],
            'twofactor error' => ['GET', '/twofactor/missing', 404],
            'auth error' => ['HEAD', '/auth/missing-provider', 404],
            'reset root' => ['GET', '/reset/', 405],
            'missing Upload route' => ['HEAD', '/api/fof/download/missing', 404],
            'purge endpoint' => ['GET', '/api/lscache-purge', 403],
            'manual exclusion' => ['HEAD', '/api/private-download', 404],
        ];
    }

    #[DataProvider('unresolvedRequests')]
    public function test_excluded_errors_without_resolved_route_names_are_not_cached(string $method, string $path, int $status): void
    {
        $this->setting('acpl-lscache.cache_exclude', "\n^/private-download$\n");
        $this->setting('acpl-lscache.status_codes_cache', "404 75\n405 75");

        $response = $this->send($this->request($method, $path));

        self::assertSame($status, $response->getStatusCode());
        self::assertSame('no-cache', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
    }

    public function test_similar_public_paths_remain_cacheable(): void
    {
        $paths = ['/confirmation-guide', '/gdpr-guide', '/__clockworker', '/maintenance-guide', '/twofactor-guide', '/fof/downloads'];
        foreach ($paths as $index => $path) {
            $this->extend((new Extend\Routes('forum'))->get($path, 'lscache.public-'.$index, fn () => new class implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return new EmptyResponse();
                }
            }));
        }

        foreach ($paths as $path) {
            $response = $this->send($this->request('GET', $path));

            self::assertSame(204, $response->getStatusCode(), $path);
            self::assertSame('public,max-age=604800', $response->getHeaderLine('X-LiteSpeed-Cache-Control'), $path);
        }
    }

    public static function downloadExclusions(): array
    {
        return [
            'automatic' => ['/fof/download/test', ''],
            'manual' => ['/controlled-download', '^/controlled-download$'],
        ];
    }

    #[DataProvider('downloadExclusions')]
    public function test_exclusions_override_cacheable_policy_and_preserve_other_headers(string $path, string $pattern): void
    {
        $this->setting('acpl-lscache.cache_exclude', $pattern);
        $this->extend((new Extend\Routes('api'))->get($path, 'lscache.download-test', fn () => new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new EmptyResponse(200, [
                    'X-LiteSpeed-Cache-Control' => 'public,max-age=600',
                    'Cache-Control' => 'private, no-store',
                    'Content-Disposition' => 'attachment; filename="private.zip"',
                    'X-LiteSpeed-Tag' => 'download-test',
                    'X-LiteSpeed-Purge' => 'tag=download-test',
                ]);
            }
        }));

        $response = $this->send($this->request('GET', '/api'.$path));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('no-cache', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
        self::assertSame('private, no-store', $response->getHeaderLine('Cache-Control'));
        self::assertSame('attachment; filename="private.zip"', $response->getHeaderLine('Content-Disposition'));
        self::assertSame('download-test,lscache.download-test', $response->getHeaderLine('X-LiteSpeed-Tag'));
        self::assertSame('tag=download-test', $response->getHeaderLine('X-LiteSpeed-Purge'));
        self::assertTrue($response->hasHeader('X-LiteSpeed-Vary'));
    }
}
