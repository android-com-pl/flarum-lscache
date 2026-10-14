<?php

namespace Acpl\FlarumLSCache\Listener;

use Flarum\Approval\Event\PostWasApproved;
use Flarum\Post\Event\{Deleted, Hidden, Posted, Restored, Revised};
use Illuminate\Contracts\Events\Dispatcher;

class PostEventSubscriber extends AbstractCachePurgeSubscriber
{
    use DiscussionCachePurgeTrait;

    public function subscribe(Dispatcher $events): void
    {
        $postEvents = [Deleted::class, Hidden::class, Posted::class, Restored::class, PostWasApproved::class, Revised::class];
        foreach ($postEvents as $event) {
            $this->addPurgeListener($events, $event, $this->handle(...));
        }
    }

    protected function handle(Deleted|Hidden|Posted|Restored|PostWasApproved|Revised $event): void
    {
        if (! $this->shouldPurge($event)) {
            return;
        }

        $this->purger->addPurgeTags([
            'posts',
            "post_{$event->post->id}",
            "discussion_{$event->post->discussion_id}",
        ]);

        if ($event->post->user !== null) {
            $this->purger->addPurgeTags([
                "user_{$event->post->user_id}",
                "user_{$event->post->user->username}",
            ]);
        }

        if (! $event instanceof Revised) {
            $this->handleDiscussionRelatedPurge();
        }
    }

    protected function shouldPurge(Deleted|Hidden|Posted|Restored|Revised|PostWasApproved $event): bool
    {
        return ! (
            ($event instanceof Deleted && $event->post->hidden_at !== null)
            || $event->post->discussion->is_private
            /** @phpstan-ignore-next-line  Access to an undefined property Flarum\Post\Post::$is_approved. */
            || $event->post->is_approved === false
        );
    }
}
