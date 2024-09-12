<?php

namespace ACPL\FlarumLSCache\Compatibility\SychO\MovePosts;

use ACPL\FlarumLSCache\Listener\AbstractCachePurgeSubscriber;
use Flarum\Post\CommentPost;
use Illuminate\Contracts\Events\Dispatcher;
use SychO\MovePosts\Event\PostsMoved;

class SychOMovePostsSubscriber extends AbstractCachePurgeSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $this->addPurgeListener($events, PostsMoved::class, [$this, 'handlePostsMoved']);
    }

    protected function handlePostsMoved(PostsMoved $event): void
    {
        $postUserTags = [];
        $event->posts->each(function ($post) use (&$postUserTags) {
            /** @var CommentPost $post */
            $postUserTags[] = "user_{$post->user->id}";
            $postUserTags[] = "user_{$post->user->username}";
        });

        $this->purger->addPurgeTags([
            'default',
            'index',
            'posts.index',
            'discussions.index',
            "discussion_{$event->sourceDiscussion->id}",
            "discussion_{$event->targetDiscussion->id}",
            ...array_map(fn ($postId) => "post_$postId", $event->posts->pluck('id')->toArray()),
            ...$postUserTags,
        ]);
    }
}
