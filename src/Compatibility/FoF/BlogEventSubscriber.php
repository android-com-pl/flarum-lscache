<?php

namespace Acpl\FlarumLSCache\Compatibility\FoF;

use Acpl\FlarumLSCache\Event\LSCachePurging;
use Acpl\FlarumLSCache\Listener\{AbstractCachePurgeSubscriber, DiscussionCachePurgeTrait};
use FoF\Blog\Event\{ArticleApproved, ArticleFeatured, ArticleUnfeatured, BlogMetaCreated, BlogMetaUpdated};
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class BlogEventSubscriber extends AbstractCachePurgeSubscriber
{
    use DiscussionCachePurgeTrait;

    public function subscribe(Dispatcher $events): void
    {
        $blogEvents = [BlogMetaCreated::class, BlogMetaUpdated::class, ArticleApproved::class, ArticleFeatured::class, ArticleUnfeatured::class];
        foreach ($blogEvents as $event) {
            $this->addPurgeListener($events, $event, $this->handle(...));
        }

        $this->addPurgeListener($events, LSCachePurging::class, $this->handleLSCachePurging(...));
    }

    public function handle(BlogMetaCreated|BlogMetaUpdated|ArticleApproved|ArticleFeatured|ArticleUnfeatured $event): void
    {
        $this->handleDiscussionRelatedPurge();
        $this->purger->addPurgeTag("discussion_{$event->blogMeta->discussion_id}");
    }

    // Blog articles share their content and metadata with discussions.
    public function handleLSCachePurging(LSCachePurging $event): void
    {
        if (in_array('index', $event->data['tags'])) {
            $this->purger->addPurgeTags(['blog.overview', 'blog.category']);
        }

        $discussions = Arr::where($event->data['tags'], fn (string $tag): bool => (bool) preg_match('/^discussion_\d+$/', $tag));
        if (! empty($discussions)) {
            $this->purger->addPurgeTags(
                preg_replace('/^discussion_(\d+)$/', 'blog_$1', $discussions),
            );
        }
    }
}
