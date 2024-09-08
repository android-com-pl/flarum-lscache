<?php

namespace ACPL\FlarumCache\Compatibility\Flarum\Tags;

use ACPL\FlarumCache\Listener\AbstractCachePurgeSubscriber;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleted as DiscussionDeleted;
use Flarum\Discussion\Event\Hidden as DiscussionHidden;
use Flarum\Discussion\Event\Renamed as DiscussionRenamed;
use Flarum\Discussion\Event\Restored as DiscussionRestored;
use Flarum\Discussion\Event\Started as DiscussionStarted;
use Flarum\Post\Event\Deleted as PostDeleted;
use Flarum\Post\Event\Hidden as PostHidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored as PostRestored;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;

class FlarumTagsEventSubscriber extends AbstractCachePurgeSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $this->addPurgeListener($events, DiscussionWasTagged::class, [$this, 'handleDiscussionWasTagged']);

        $discussionEvents = [
            DiscussionDeleted::class, DiscussionHidden::class, DiscussionRenamed::class, DiscussionRestored::class,
            DiscussionStarted::class,
        ];
        foreach ($discussionEvents as $event) {
            $this->addPurgeListener($events, $event, [$this, 'handleDiscussionEvents']);
        }

        $postEvents = [PostDeleted::class, PostHidden::class, Posted::class, PostRestored::class];
        foreach ($postEvents as $event) {
            $this->addPurgeListener($events, $event, [$this, 'handlePostEvents']);
        }
    }

    public function handleDiscussionWasTagged(DiscussionWasTagged $event): void
    {
        $this->purger->addPurgeTags([
            'default',
            'index',
            'discussions.index',
            "discussion_{$event->discussion->id}",
            "user_{$event->discussion->user->id}",
            "user_{$event->discussion->user->username}",
            "tags.index",
            ...$this->generateCacheTagsForDiscussionTags($event->discussion),
        ]);
    }

    public function handleDiscussionEvents(
        DiscussionDeleted|DiscussionHidden|DiscussionRenamed|DiscussionRestored|DiscussionStarted $event,
    ): void {
        if (
            ($event instanceof DiscussionDeleted && $event->discussion->hidden_at !== null)
            || $event->discussion->is_approved === false
        ) {
            return;
        }

        $this->purger->addPurgeTags([
            'tags.index',
            ...$this->generateCacheTagsForDiscussionTags($event->discussion),
        ]);
    }

    public function handlePostEvents(PostDeleted|PostHidden|Posted|PostRestored $event): void
    {
        if (
            ($event instanceof PostRestored && $event->post->discussion->hidden_at !== null)
            || $event->post->discussion->is_approved === false
        ) {
            return;
        }

        $this->purger->addPurgeTags([
            'tags.index',
            ...$this->generateCacheTagsForDiscussionTags($event->post->discussion),
        ]);
    }

    protected function generateCacheTagsForDiscussionTags(Discussion $discussion): array
    {
        return $discussion->tags->map(fn (Tag $tag) => "tag_$tag->slug")->toArray();
    }
}
