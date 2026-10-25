<?php

namespace Acpl\FlarumLSCache\Tests\integration;

use Flarum\Audit\{AuditLog, AuditLogger};
use Flarum\Testing\integration\TestCase;

class AuditIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        AuditLogger::$testMode = true;
        $this->extension('flarum-audit', 'acpl-lscache');
    }

    public function test_only_actor_purges_are_logged(): void
    {
        $this->setting('acpl-lscache.purgeKey', 'test-purge-secret');
        $response = $this->send(
            $this->request('GET', '/api/lscache-purge')->withHeader('LSCachePurgeKey', 'test-purge-secret')
        );

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('*', $response->getHeaderLine('X-LiteSpeed-Purge'));
        self::assertSame(0, AuditLog::where('action', 'acpl-lscache.cache_purged')->count());

        $response = $this->send($this->request('GET', '/api/lscache-purge', ['authenticatedAs' => 1]));

        self::assertSame(204, $response->getStatusCode());
        $logs = AuditLog::where('action', 'acpl-lscache.cache_purged')->orderBy('id')->get();
        self::assertCount(1, $logs);
        self::assertEquals(1, $logs->last()->actor_id);
        $payload = $logs->last()->payload;
        self::assertCount(2, $payload);
        self::assertSame(['*'], $payload['paths']);
        self::assertSame([], $payload['tags']);
    }
}
