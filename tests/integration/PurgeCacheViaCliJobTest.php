<?php

namespace Acpl\FlarumLSCache\Tests\integration;

use Acpl\FlarumLSCache\Command\LSCachePurgeCommand;
use Acpl\FlarumLSCache\Job\PurgeCacheViaCliJob;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PurgeCacheViaCliJobTest extends TestCase
{
    public static function exitCodes(): array
    {
        return ['success' => [0], 'failure' => [1]];
    }

    #[DataProvider('exitCodes')]
    public function test_failed_purge_is_reported_to_the_queue(int $exitCode): void
    {
        $command = $this->createMock(LSCachePurgeCommand::class);
        $command->expects(self::once())->method('run')->willReturn($exitCode);

        if ($exitCode !== 0) {
            $this->expectException(\RuntimeException::class);
        }

        (new PurgeCacheViaCliJob(['paths' => [], 'tags' => ['discussion_1']]))->handle($command);
    }
}
