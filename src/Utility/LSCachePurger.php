<?php

namespace ACPL\FlarumLSCache\Utility;

use ACPL\FlarumLSCache\Command\LSCacheClearCommand;
use ACPL\FlarumLSCache\Event\LSCachePurging;
use Illuminate\Events\Dispatcher;
use Symfony\Component\Console\{Input\ArrayInput, Output\NullOutput};

use const PHP_SAPI;

/**
 * Manages the purging of LSCache by collecting purge data.
 * This data can be used either in middleware for HTTP requests or executed directly for CLI commands.
 */
class LSCachePurger
{
    /**
     * @var array{
     *   paths: string[],
     *   tags: string[]
     *  } $purgeData
     */
    private static array $purgeData = [
        'paths' => [],
        'tags' => [],
    ];

    /**
     * @var array|string[]
     */
    public static array $resourcesSupportedByEvent = ['discussion', 'post', 'user'];

    public function __construct(protected readonly LSCacheClearCommand $cacheClearCommand, protected Dispatcher $events)
    {
    }

    public function addPurgePath(string $purgePath): void
    {
        self::$purgeData['paths'][] = $purgePath;
    }

    /**
     * @param  array<string>  $paths
     */
    public function addPurgePaths(array $paths): void
    {
        self::$purgeData['paths'] = array_merge(self::$purgeData['paths'] ?? [], $paths);
    }

    public function addPurgeTag(string $tag): void
    {
        self::$purgeData['tags'][] = $tag;
    }

    /**
     * @param  array<string>  $tags
     */
    public function addPurgeTags(array $tags): void
    {
        self::$purgeData['tags'] = array_merge(self::$purgeData['tags'] ?? [], $tags);
    }

    public function getPurgeData(): array
    {
        return self::$purgeData;
    }

    public function clearPurgeData(): void
    {
        self::$purgeData = [
            'paths' => [],
            'tags' => [],
        ];
    }

    public function executePurge(): void
    {
        if (empty(self::$purgeData) || (empty(self::$purgeData['paths']) || empty(self::$purgeData['tags']))) {
            return;
        }

        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            $this->purgeViaCli();
        }
        // else Data will be handled by middleware
    }

    private function purgeViaCli(): void
    {
        $input = [];

        $this->events->dispatch(new LSCachePurging(self::$purgeData));

        if (! empty(self::$purgeData['paths'])) {
            $input['--path'] = self::$purgeData['paths'];
        }

        if (! empty(self::$purgeData['tags'])) {
            $input['--tag'] = self::$purgeData['tags'];
        }

        $this->cacheClearCommand->run(new ArrayInput($input), new NullOutput());
        $this->clearPurgeData();
    }

    public static function isResourceSupportedByEvent(string $resource): bool
    {
        return in_array($resource, self::$resourcesSupportedByEvent);
    }
}
