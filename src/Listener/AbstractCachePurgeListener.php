<?php

namespace Acpl\FlarumLSCache\Listener;

use Acpl\FlarumLSCache\Utility\LSCachePurger;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * @template TEvent of object
 */
abstract class AbstractCachePurgeListener
{
    public function __construct(protected LSCachePurger $purger, protected SettingsRepositoryInterface $settings)
    {
    }

    /** @param  TEvent  $event */
    public function handle(object $event): void
    {
        $this->addPurgeData($event);
        $this->purger->executePurge();
    }

    /** @param  TEvent  $event */
    abstract protected function addPurgeData(object $event): void;
}
