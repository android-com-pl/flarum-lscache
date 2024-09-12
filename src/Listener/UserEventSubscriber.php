<?php

namespace ACPL\FlarumLSCache\Listener;

use Flarum\User\Event\AvatarChanged;
use Flarum\User\Event\Deleted;
use Flarum\User\Event\GroupsChanged;
use Flarum\User\Event\Renamed;
use Illuminate\Contracts\Events\Dispatcher;

class UserEventSubscriber extends AbstractCachePurgeSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $shared = [AvatarChanged::class, Deleted::class, GroupsChanged::class, Renamed::class];
        foreach ($shared as $event) {
            $this->addPurgeListener($events, $event, [$this, 'handleUserWithPosts']);
        }
    }

    /** Purge discussions where user has posted, so new user data is visible there */
    public function handleUserWithPosts(AvatarChanged|GroupsChanged|Renamed $event): void
    {
        // TODO: If user has a lot discussion chunk it and push to the queue job
        $discussions = $event->user->posts()->getRelation('discussion')->pluck('id')->toArray();

        $this->purger->addPurgeTags([
            "user_{$event->user->id}",
            "user_{$event->user->username}",
            'posts.index',
            'discussions.index',
            ...array_map(fn ($id) => "discussion_$id", $discussions),
        ]);
    }
}
