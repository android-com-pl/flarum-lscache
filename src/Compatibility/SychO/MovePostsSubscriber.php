<?php

namespace Acpl\FlarumLSCache\Compatibility\SychO;

use Acpl\FlarumLSCache\Listener\AbstractCachePurgeSubscriber;
use Acpl\FlarumLSCache\Listener\DiscussionCachePurgeTrait;
use Flarum\Post\CommentPost;
use Illuminate\Contracts\Events\Dispatcher;
use SychO\MovePosts\Event\PostsMoved;

class MovePostsSubscriber extends AbstractCachePurgeSubscriber
{
    use DiscussionCachePurgeTrait;

    public function subscribe(Dispatcher $events): void
    {
        $this->addPurgeListener($events, PostsMoved::class, $this->handlePostsMoved(...));
    }

    protected function handlePostsMoved(PostsMoved $event): void
    {
        $cacheTags = [];
        $event->posts->each(function ($post) use (&$cacheTags): void {
            /** @var CommentPost $post */
            $cacheTags[] = "post_{$post->id}";
            $cacheTags[] = "user_{$post->user->id}";
            $cacheTags[] = "user_{$post->user->username}";
        });

        $this->handleDiscussionRelatedPurge();
        $this->purger->addPurgeTags([
            "discussion_{$event->sourceDiscussion->id}",
            "discussion_{$event->targetDiscussion->id}",
            ...$cacheTags,
        ]);
    }
}
