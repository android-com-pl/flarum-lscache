<?php

namespace Acpl\FlarumLSCache\Compatibility\FoF;

use Acpl\FlarumLSCache\Listener\AbstractCachePurgeSubscriber;
use FoF\Masquerade\Events\ProfileUpdated;
use Illuminate\Contracts\Events\Dispatcher;

class MasqueradePurgeCacheSubscriber extends AbstractCachePurgeSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $this->addPurgeListener($events, ProfileUpdated::class, $this->handleProfileUpdated(...));
    }

    protected function handleProfileUpdated(ProfileUpdated $event): void
    {
        $this->purger->addPurgeTags([
            "user_{$event->user->id}",
            "user_{$event->user->username}",
            "masquerade_{$event->user->id}",
        ]);
    }
}
