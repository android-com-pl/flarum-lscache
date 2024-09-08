<?php

namespace ACPL\FlarumCache\Compatibility\v17development\FlarumBlog;

use ACPL\FlarumCache\Listener\AbstractCachePurgeSubscriber;
use Illuminate\Contracts\Events\Dispatcher;
use V17Development\FlarumBlog\Event\BlogMetaSaving;

class FlarumBlogEventSubscriber extends AbstractCachePurgeSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $this->addPurgeListener($events, BlogMetaSaving::class, [$this, 'handle']);
    }

    public function handle(BlogMetaSaving $event): void
    {
        $this->purger->addPurgeTags([
            'blog.overview',
            "blog_{$event->blogMeta->discussion_id}",
        ]);
    }
}
