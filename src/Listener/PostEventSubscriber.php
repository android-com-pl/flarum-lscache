<?php

namespace ACPL\FlarumLSCache\Listener;

use Flarum\Approval\Event\PostWasApproved;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Illuminate\Contracts\Events\Dispatcher;

class PostEventSubscriber extends AbstractCachePurgeSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $shared = [Hidden::class, Posted::class, Restored::class, PostWasApproved::class];
        foreach ($shared as $event) {
            $this->addPurgeListener($events, $event, [$this, 'handle']);
        }

        $this->addPurgeListener($events, Revised::class, [$this, 'handleRevised']);
    }

    protected function handle(Hidden|Posted|Restored|PostWasApproved $event): void
    {
        if (! $this->shouldPurge($event)) {
            return;
        }

        $this->purger->addPurgeTags([
            'default',
            'index',
            'posts.index',
            'discussions.index',
            "discussion_{$event->post->discussion_id}",
            "user_{$event->post->user_id}",
            "user_{$event->post->user_id}",
        ]);
    }

    protected function handleRevised(Revised $event): void
    {
        if (! $this->shouldPurge($event)) {
            return;
        }

        // No need to purge homepage cache when post is revised
        $this->purger->addPurgeTags([
            'posts.index',
            "discussion_{$event->post->discussion_id}",
            "user_{$event->post->user_id}",
            "user_{$event->post->user_id}",
        ]);
    }

    protected function shouldPurge(Deleted|Hidden|Posted|Restored|Revised|PostWasApproved $event): bool
    {
        return ! (
            $event->post->discussion->is_private
            || $event->post?->is_approved === false
        );
    }
}
