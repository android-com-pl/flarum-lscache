<?php

namespace Acpl\FlarumLSCache\Tests\integration;

use Dflydev\FigCookies\FigResponseCookies;
use Flarum\Extend;
use Flarum\Http\CookieFactory;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Exception\PermissionDeniedException;
use Laminas\Diactoros\Response\EmptyResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CacheHeadersTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('acpl-lscache');
        $this->prepareDatabase(['users' => [$this->normalUser()]]);
    }

    public static function guestRequests(): array
    {
        return [
            'forum' => ['GET', '/', 'default'],
            'API collection' => ['GET', '/api/discussions', 'discussions'],
        ];
    }

    #[DataProvider('guestRequests')]
    public function test_guest_responses_have_public_cache_tags_and_vary(string $method, string $path, string $tag): void
    {
        $response = $this->send($this->request($method, $path));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('public,max-age=604800', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
        self::assertSame($tag, $response->getHeaderLine('X-LiteSpeed-Tag'));
        $cookies = $this->app()->getContainer()->make(CookieFactory::class);
        self::assertSame(
            'cookie='.$cookies->getName('lscache_vary').',cookie='.$cookies->getName('remember').',cookie=locale',
            $response->getHeaderLine('X-LiteSpeed-Vary')
        );
        self::assertNull(FigResponseCookies::get($response, $cookies->getName('lscache_vary'))->getValue());
        self::assertFalse($response->hasHeader('X-LiteSpeed-Purge'));
    }

    public static function cacheSettings(): array
    {
        return [
            'custom TTL' => [['public_cache_ttl' => 90], 'public,max-age=90'],
            'zero TTL falls back' => [['public_cache_ttl' => 0], 'public,max-age=604800'],
            'disabled cache' => [['cache_enabled' => false], 'no-cache'],
            // API middleware sees paths relative to the /api mount point.
            'matching exclusion' => [['cache_exclude' => "\n^/discussions$\n"], 'no-cache'],
            'nonmatching exclusion' => [['cache_exclude' => '^/private'], 'public,max-age=604800'],
        ];
    }

    #[DataProvider('cacheSettings')]
    public function test_settings_control_response_cache_policy(array $settings, string $expected): void
    {
        foreach ($settings as $key => $value) {
            $this->setting('acpl-lscache.'.$key, $value);
        }

        $response = $this->send($this->request('GET', '/api/discussions'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($expected, $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
    }

    public function test_authenticated_response_is_not_publicly_cached_and_sets_vary_cookie(): void
    {
        $response = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => 2]));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('no-cache', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
        $cookies = $this->app()->getContainer()->make(CookieFactory::class);
        self::assertNotEmpty(FigResponseCookies::get($response, $cookies->getName('lscache_vary'))->getValue());
        self::assertTrue($response->hasHeader('X-LiteSpeed-Vary'));
    }

    public static function csrfRequests(): array
    {
        return [
            'guest' => [null, true, 'private,max-age=7080'],
            'authenticated' => [2, true, 'private,max-age=7080'],
            'cache disabled' => [null, false, 'no-cache'],
        ];
    }

    #[DataProvider('csrfRequests')]
    public function test_csrf_policy_is_preserved_unless_cache_is_disabled(?int $userId, bool $enabled, string $expected): void
    {
        $this->config('session.lifetime', 120);
        $this->setting('acpl-lscache.cache_enabled', $enabled);
        $response = $this->send($this->request('GET', '/api/lscache-csrf', ['authenticatedAs' => $userId]));

        self::assertSame(204, $response->getStatusCode());
        self::assertSame($expected, $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
        self::assertNotEmpty($response->getHeaderLine('X-CSRF-Token'));
    }

    public static function missingRoutes(): array
    {
        return [
            'default error TTL' => [null, '404 3600', 'public,max-age=3600'],
            'custom error TTL' => [null, "invalid\n404\n404 0\n404 75", 'public,max-age=75'],
            'unconfigured error' => [null, '500 120', ''],
            'authenticated error' => [2, '404 3600', ''],
            'disabled cache' => [null, '404 3600', 'no-cache', false],
        ];
    }

    #[DataProvider('missingRoutes')]
    public function test_missing_route_cache_policy(?int $userId, string $settings, string $expected, bool $enabled = true): void
    {
        $this->setting('acpl-lscache.cache_enabled', $enabled);
        $this->setting('acpl-lscache.status_codes_cache', $settings);
        $response = $this->send($this->request('GET', '/api/nonexistent-lscache-route', ['authenticatedAs' => $userId]));

        self::assertSame(404, $response->getStatusCode());
        self::assertSame($expected, $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
        self::assertFalse($response->hasHeader('X-LiteSpeed-Purge'));
    }

    public static function authenticationRoutes(): array
    {
        return [
            'auth extension' => ['auth.test', '/auth/test', 204],
            'OAuth extension' => ['fof-oauth.test', '/auth/test/callback', 204],
            'auth failure' => ['auth.test', '/auth/test', 403],
            'OAuth failure' => ['fof-oauth.test', '/auth/test/callback', 403],
        ];
    }

    #[DataProvider('authenticationRoutes')]
    public function test_authentication_callbacks_are_not_cached(string $route, string $path, int $status): void
    {
        // Represent callbacks supplied by auth extensions without requiring an OAuth provider.
        $this->extend((new Extend\Routes('forum'))->get($path, $route, fn () => new class($status) implements RequestHandlerInterface {
            public function __construct(private int $status)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($this->status === 403) {
                    throw new PermissionDeniedException();
                }

                return new EmptyResponse();
            }
        }));
        $response = $this->send($this->request('GET', $path));

        self::assertSame($status, $response->getStatusCode());
        self::assertSame('no-cache', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
    }

    public function test_other_forbidden_responses_keep_configured_cache_ttl(): void
    {
        $this->setting('acpl-lscache.status_codes_cache', '403 75');
        $this->extend((new Extend\Routes('forum'))->get('/test-forbidden', 'test.forbidden', fn () => new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new PermissionDeniedException();
            }
        }));
        $response = $this->send($this->request('GET', '/test-forbidden'));

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('public,max-age=75', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
    }

    public function test_password_reset_page_is_not_cached(): void
    {
        $this->prepareDatabase(['password_tokens' => [[
            'token' => 'lscache-test-password-token',
            'user_id' => 2,
            'created_at' => date('Y-m-d H:i:s'),
        ]]]);
        $response = $this->send($this->request('GET', '/reset/lscache-test-password-token'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('no-cache', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
    }

    public static function loginAttempts(): array
    {
        return [
            'valid credentials' => ['too-obscure', 200],
            'invalid credentials' => ['wrong-password', 401],
        ];
    }

    #[DataProvider('loginAttempts')]
    public function test_login_sets_vary_cookie_only_on_success(string $password, int $status): void
    {
        $request = $this->request('POST', '/login', ['json' => [
            'identification' => 'normal',
            'password' => $password,
        ]])->withHeader('Accept', 'application/json');
        $response = $this->send($this->requestWithCsrfToken($request));

        self::assertSame($status, $response->getStatusCode());
        $cookies = $this->app()->getContainer()->make(CookieFactory::class);
        $vary = FigResponseCookies::get($response, $cookies->getName('lscache_vary'));
        if ($status === 200) {
            self::assertNotEmpty($vary->getValue());
        } else {
            self::assertNull($vary->getValue());
        }
        self::assertFalse($response->hasHeader('X-LiteSpeed-Cache-Control'));
        self::assertFalse($response->hasHeader('X-LiteSpeed-Tag'));
        self::assertFalse($response->hasHeader('X-LiteSpeed-Purge'));
    }

    public function test_logout_expires_vary_cookie_and_prevents_caching(): void
    {
        $response = $this->send($this->request('POST', '/logout', ['authenticatedAs' => 2]));

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('no-cache', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
        $cookies = $this->app()->getContainer()->make(CookieFactory::class);
        $vary = FigResponseCookies::get($response, $cookies->getName('lscache_vary'));
        self::assertNotNull($vary->getExpires());
        self::assertLessThan(time(), $vary->getExpires());
    }
}
