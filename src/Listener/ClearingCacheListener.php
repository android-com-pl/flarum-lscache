<?php

namespace Acpl\FlarumLSCache\Listener;

use Flarum\Foundation\Event\ClearingCache;

/** @extends AbstractCachePurgeListener<ClearingCache> */
class ClearingCacheListener extends AbstractCachePurgeListener
{
    protected function addPurgeData(object $event): void
    {
        if ($this->settings->get('acpl-lscache.clearing_cache_listener')) {
            $this->purger->addPurgePath('*');
        }
    }
}
