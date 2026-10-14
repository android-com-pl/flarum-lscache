<?php

namespace Acpl\FlarumLSCache\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PurgeHeadersTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('acpl-lscache');
        $this->prepareDatabase(['users' => [$this->normalUser()]]);
    }

    public static function purgeRequests(): array
    {
        return [
            'all' => [[], false, '*'],
            'stale all' => [[], true, 'stale,*'],
            'paths' => [['paths' => ['/d/1', '/']], false, '/d/1,/'],
            'tags' => [['tags' => ['discussion_1', 'discussions']], false, 'tag=discussion_1,tag=discussions'],
            'combined stale' => [['paths' => ['/'], 'tags' => ['discussions']], true, 'stale,/,tag=discussions'],
        ];
    }

    #[DataProvider('purgeRequests')]
    public function test_admin_can_purge_paths_and_tags(array $query, bool $stale, string $expected): void
    {
        $this->setting('acpl-lscache.serve_stale', $stale);
        $response = $this->send(
            $this->request('GET', '/api/lscache-purge', ['authenticatedAs' => 1])->withQueryParams($query)
        );

        self::assertSame(204, $response->getStatusCode());
        self::assertSame($expected, $response->getHeaderLine('X-LiteSpeed-Purge'));
        self::assertSame('no-cache', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
    }

    public static function purgeCredentials(): array
    {
        return [
            'guest without key' => [null, '', '', 403],
            'normal user' => [2, '', '', 403],
            'wrong key' => [null, 'test-purge-secret', 'wrong', 403],
            'missing key' => [null, 'test-purge-secret', '', 403],
            'unconfigured key' => [null, '', 'test-purge-secret', 403],
            'valid CLI key' => [null, 'test-purge-secret', 'test-purge-secret', 204],
        ];
    }

    #[DataProvider('purgeCredentials')]
    public function test_purge_requires_permission_or_valid_key(?int $userId, string $storedKey, string $requestKey, int $status): void
    {
        $this->setting('acpl-lscache.purgeKey', $storedKey);
        $response = $this->send(
            $this->request('GET', '/api/lscache-purge', ['authenticatedAs' => $userId])
                ->withHeader('LSCachePurgeKey', $requestKey)
        );

        self::assertSame($status, $response->getStatusCode());
        self::assertSame('no-cache', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
        if ($status === 204) {
            self::assertSame('*', $response->getHeaderLine('X-LiteSpeed-Purge'));
        } else {
            self::assertFalse($response->hasHeader('X-LiteSpeed-Purge'));
        }
    }
}
