<?php

namespace ACPL\FlarumLSCache\Listener;

use ACPL\FlarumLSCache\Utility\LSCachePurger;
use Illuminate\Contracts\Events\Dispatcher;

abstract class AbstractCachePurgeSubscriber
{
    public function __construct(protected LSCachePurger $purger)
    {
    }

    abstract public function subscribe(Dispatcher $events): void;

    protected function addPurgeListener(Dispatcher $events, string $event, callable $handler): void
    {
        $events->listen($event, function ($eventInstance) use ($handler) {
            $handler($eventInstance);
            $this->purger->executePurge();
        });
    }
}
