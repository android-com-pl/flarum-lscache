<?php

namespace ACPL\FlarumLSCache\Listener;

use ACPL\FlarumLSCache\Utility\LSCachePurger;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;

class ClearingCacheListener extends AbstractCachePurgeListener
{
    public function __construct(protected LSCachePurger $purger, protected SettingsRepositoryInterface $settings)
    {
        parent::__construct($this->purger);
    }

    protected function addPurgeData(Dispatcher $event): void
    {
        if ($this->settings->get('acpl-lscache.clearing_cache_listener')) {
            $this->purger->addPurgePath('*');
        }
    }
}
