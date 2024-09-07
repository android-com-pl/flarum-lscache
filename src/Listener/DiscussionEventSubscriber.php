<?php

namespace ACPL\FlarumCache\Listener;

use Flarum\Discussion\Event\Deleted;
use Flarum\Discussion\Event\Hidden;
use Flarum\Discussion\Event\Renamed;
use Flarum\Discussion\Event\Restored;
use Flarum\Discussion\Event\Started;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionEventSubscriber extends AbstractCachePurgeSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $this->addPurgeListener($events, Deleted::class, [$this, 'handleDeleted']);

        $shared = [Hidden::class, Started::class, Restored::class, Renamed::class];
        foreach ($shared as $method) {
            $this->addPurgeListener($events, $method, [$this, 'handle']);
        }
    }

    protected function handle(Deleted|Hidden|Started|Restored|Renamed $event): void
    {
        $this->purger->addPurgeTags([
            'default',
            'index',
            'discussions.index',
            "discussion_{$event->discussion->id}",
            "user_{$event->actor->id}",
            "user_{$event->actor->username}",
        ]);
    }

    protected function handleDeleted(Deleted $event): void
    {
        // If discussion was hidden before, there is no need to purge cache, because it is not visible for guests anyway
        if ($event->discussion->hidden_at === null) {
            $this->handle($event);
        }
    }
}
