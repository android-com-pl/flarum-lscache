<?php

namespace ACPL\FlarumLSCache\Listener;

use Illuminate\Contracts\Events\Dispatcher;

class ClearingCacheListener extends AbstractCachePurgeListener
{
    protected function addPurgeData(Dispatcher $event): void
    {
        if ($this->settings->get('acpl-lscache.clearing_cache_listener')) {
            $this->purger->addPurgePath('*');
        }
    }
}
