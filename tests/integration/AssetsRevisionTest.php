<?php

namespace Acpl\FlarumLSCache\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AssetsRevisionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('acpl-lscache');
        $this->prepareDatabase(['users' => [$this->normalUser()]]);
    }

    public static function cacheSettings(): array
    {
        return [
            'guest, cache off, purge off, stale off' => [null, false, false, false, false],
            'guest, cache off, purge off, stale on' => [null, false, false, true, false],
            'guest, cache off, purge on, stale off' => [null, false, true, false, false],
            'guest, cache off, purge on, stale on' => [null, false, true, true, false],
            'guest, cache on, purge off, stale off' => [null, true, false, false, true],
            'guest, cache on, purge off, stale on' => [null, true, false, true, true],
            'guest, cache on, purge on, stale off' => [null, true, true, false, false],
            'guest, cache on, purge on, stale on' => [null, true, true, true, true],
            'user, cache off, purge off, stale off' => [2, false, false, false, false],
            'user, cache off, purge off, stale on' => [2, false, false, true, false],
            'user, cache off, purge on, stale off' => [2, false, true, false, false],
            'user, cache off, purge on, stale on' => [2, false, true, true, false],
            'user, cache on, purge off, stale off' => [2, true, false, false, false],
            'user, cache on, purge off, stale on' => [2, true, false, true, false],
            'user, cache on, purge on, stale off' => [2, true, true, false, false],
            'user, cache on, purge on, stale on' => [2, true, true, true, false],
        ];
    }

    #[DataProvider('cacheSettings')]
    public function test_asset_revision_checks_follow_guest_cache_policy(?int $userId, bool $enabled, bool $purge, bool $stale, bool $suppressed): void
    {
        $this->setting('acpl-lscache.cache_enabled', $enabled);
        $this->setting('acpl-lscache.clearing_cache_listener', $purge);
        $this->setting('acpl-lscache.serve_stale', $stale);

        $response = $this->send($this->request('GET', '/', ['authenticatedAs' => $userId]));

        self::assertSame(200, $response->getStatusCode());
        $html = (string) $response->getBody();
        $payload = $this->jsonScript($html, 'flarum-json-payload');
        self::assertNotEmpty($this->jsonScript($html, 'flarum-rev-manifest'));

        if ($suppressed) {
            self::assertArrayNotHasKey('assetsRevision', $payload);
        } else {
            self::assertArrayHasKey('assetsRevision', $payload);
            self::assertNotEmpty($payload['assetsRevision']);
        }

        $apiResponse = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => $userId]));

        self::assertSame(200, $apiResponse->getStatusCode());
        self::assertNotEmpty($apiResponse->getHeaderLine('X-Flarum-Assets-Revision'));
        if (! $suppressed) {
            self::assertSame($payload['assetsRevision'], $apiResponse->getHeaderLine('X-Flarum-Assets-Revision'));
        }
    }

    private function jsonScript(string $html, string $id): array
    {
        self::assertSame(1, preg_match('/<script id="'.preg_quote($id, '/').'" type="application\/json">(.*?)<\/script>/s', $html, $matches));

        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }
}
