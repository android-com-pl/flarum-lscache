<?php

namespace ACPL\FlarumLSCache\Listener;

use ACPL\FlarumLSCache\Utility\LSCachePurger;
use Illuminate\Contracts\Events\Dispatcher;

abstract class AbstractCachePurgeListener
{
    public function __construct(protected LSCachePurger $purger)
    {
    }

    protected function handle(Dispatcher $event): void
    {
        $this->addPurgeData($event);
        $this->purger->executePurge();
    }

    abstract protected function addPurgeData(Dispatcher $event): void;
}
