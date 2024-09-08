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
        $shared = [Hidden::class, Started::class, Restored::class, Renamed::class];
        foreach ($shared as $event) {
            $this->addPurgeListener($events, $event, [$this, 'handle']);
        }

        $this->addPurgeListener($events, Deleted::class, [$this, 'handleDeleted']);
    }

    protected function handle(Deleted|Hidden|Started|Restored|Renamed $event): void
    {
        if (! $this->shouldPurge($event)) {
            return;
        }

        $this->purger->addPurgeTags([
            'default',
            'index',
            'discussions.index',
            "discussion_{$event->discussion->id}",
            "user_{$event->discussion->user->id}",
            "user_{$event->discussion->user->username}",
        ]);
    }

    protected function handleDeleted(Deleted $event): void
    {
        // If discussion was hidden before, there is no need to purge cache, because it is not visible for guests anyway
        if ($event->discussion->hidden_at === null && $this->shouldPurge($event)) {
            $this->handle($event);
        }
    }

    protected function shouldPurge(
        Deleted|Hidden|Started|Restored|Renamed $event,
    ): bool {
        return ! (
            $event->discussion->is_private
            || $event->discussion?->is_approved === false
        );
    }
}