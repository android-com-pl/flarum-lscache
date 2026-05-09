<?php

namespace Acpl\FlarumLSCache\Compatibility\FoF;

use Acpl\FlarumLSCache\Listener\{AbstractCachePurgeSubscriber, DiscussionCachePurgeTrait};
use Acpl\FlarumLSCache\Utility\LSCachePurger;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use FoF\MergeDiscussions\Events\DiscussionWasMerged;
use Illuminate\Contracts\Events\Dispatcher;

class MergeDiscussionsEventSubscriber extends AbstractCachePurgeSubscriber
{
    use DiscussionCachePurgeTrait;

    public function subscribe(Dispatcher $events): void
    {
        $this->addPurgeListener($events, DiscussionWasMerged::class, $this->handleDiscussionWasMerged(...));
    }

    protected function handleDiscussionWasMerged(DiscussionWasMerged $event): void
    {
        $this->handleDiscussionRelatedPurge();

        $discussions = $event->mergedDiscussions;
        $discussions->each(fn (Discussion $discussion): LSCachePurger => $this->purger->addPurgeTag("discussion_$discussion->id"));

        $event->posts->each(function (Post $post): void {
            $this->purger->addPurgeTags([
                "post_$post->id",
                "user_$post->user_id",
                "user_{$post->user->username}",
            ]);
        });
    }
}
