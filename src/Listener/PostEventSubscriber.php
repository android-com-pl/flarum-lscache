<?php

namespace ACPL\FlarumCache\Listener;

use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Illuminate\Contracts\Events\Dispatcher;

class PostEventSubscriber extends AbstractCachePurgeSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $shared = [Hidden::class, Posted::class, Restored::class];
        foreach ($shared as $event) {
            $this->addPurgeListener($events, $event, [$this, 'handle']);
        }

        $this->addPurgeListener($events, Revised::class, [$this, 'handleRevised']);
    }

    protected function handle(Hidden|Posted|Restored $event): void
    {
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
        // No need to purge homepage cache when post is revised
        $this->purger->addPurgeTags([
            'posts.index',
            "discussion_{$event->post->discussion_id}",
            "user_{$event->post->user_id}",
            "user_{$event->post->user_id}",
        ]);
    }
}
