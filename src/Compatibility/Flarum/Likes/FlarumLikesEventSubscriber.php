<?php

namespace ACPL\FlarumCache\Compatibility\Flarum\Likes;

use ACPL\FlarumCache\Listener\AbstractCachePurgeSubscriber;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Illuminate\Contracts\Events\Dispatcher;

class FlarumLikesEventSubscriber extends AbstractCachePurgeSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        foreach ([PostWasLiked::class, PostWasUnliked::class] as $event) {
            $this->addPurgeListener($events, $event, [$this, 'handle']);
        }
    }

    protected function handle(PostWasLiked|PostWasUnliked $event): void
    {
        $this->purger->addPurgeTags([
            "posts.index",
            "post_{$event->post->id}",
            "discussion_{$event->post->discussion->id}",
            "user_{$event->post->user->id}",
            "user_{$event->post->user->username}",
            "user_{$event->user->id}",
            "user_{$event->user->username}",
        ]);
    }
}