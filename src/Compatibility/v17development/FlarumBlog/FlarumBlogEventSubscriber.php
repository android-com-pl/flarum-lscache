<?php

namespace ACPL\FlarumCache\Compatibility\v17development\FlarumBlog;

use ACPL\FlarumCache\Event\LSCachePurging;
use ACPL\FlarumCache\Listener\AbstractCachePurgeSubscriber;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use V17Development\FlarumBlog\Event\BlogMetaSaving;

class FlarumBlogEventSubscriber extends AbstractCachePurgeSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $this->addPurgeListener($events, BlogMetaSaving::class, [$this, 'handle']);
        $this->addPurgeListener($events, LSCachePurging::class, [$this, 'handleLSCachePurging']);
    }

    public function handle(BlogMetaSaving $event): void
    {
        $this->purger->addPurgeTags([
            'blog.overview',
            "blog_{$event->blogMeta->discussion_id}",
        ]);
    }

    /**
     * If discussion is detected, also purge blog, because blog is a discussion.
     */
    public function handleLSCachePurging(LSCachePurging $event): void
    {
        if (in_array('index', $event->data['tags'])) {
            $this->purger->addPurgeTag('blog.overview');
        }

        $discussion = Arr::first($event->data['tags'], fn (string $tag) => str_starts_with($tag, 'discussion_'));
        if ($discussion) {
            $this->purger->addPurgeTag('blog_'.explode('_', $discussion)[1]);
        }
    }
}
