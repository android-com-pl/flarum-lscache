<?php

namespace Acpl\FlarumLSCache\Compatibility\FoF;

use Acpl\FlarumLSCache\Listener\{AbstractCachePurgeSubscriber, DiscussionCachePurgeTrait};
use Flarum\User\User;
use FoF\AuthorChange\Event\{
    DiscussionCreateDateChanged,
    DiscussionUserChanged,
    PostCreateDateChanged,
    PostEditDateChanged,
    PostUserChanged
};
use Illuminate\Contracts\Events\Dispatcher;

class AuthorChangeEventSubscriber extends AbstractCachePurgeSubscriber
{
    use DiscussionCachePurgeTrait;

    public function subscribe(Dispatcher $events): void
    {
        $this->addPurgeListener($events, DiscussionCreateDateChanged::class, $this->handleDiscussion(...));
        $this->addPurgeListener($events, DiscussionUserChanged::class, $this->handleDiscussion(...));
        $this->addPurgeListener($events, PostCreateDateChanged::class, $this->handlePost(...));
        $this->addPurgeListener($events, PostEditDateChanged::class, $this->handlePost(...));
        $this->addPurgeListener($events, PostUserChanged::class, $this->handlePost(...));
    }

    protected function handleDiscussion(DiscussionCreateDateChanged|DiscussionUserChanged $event): void
    {
        $this->handleDiscussionRelatedPurge();
        $this->purger->addPurgeTag("discussion_{$event->discussion->id}");
        $this->purgeUser($event->discussion->user);

        if ($event instanceof DiscussionUserChanged) {
            $this->purgeUser($event->oldUser);
        }
    }

    protected function handlePost(PostCreateDateChanged|PostEditDateChanged|PostUserChanged $event): void
    {
        $this->purger->addPurgeTags([
            "discussion_{$event->post->discussion_id}",
            'posts',
            "post_{$event->post->id}",
        ]);
        $this->purgeUser($event->post->user);

        if ($event instanceof PostUserChanged) {
            $this->purgeUser($event->oldUser);
        }
    }

    protected function purgeUser(?User $user): void
    {
        if ($user !== null) {
            $this->purger->addPurgeTags(["user_$user->id", "user_$user->username"]);
        }
    }
}
