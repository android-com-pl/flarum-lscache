<?php

namespace ACPL\FlarumLSCache\Listener;

use Flarum\User\Event\{AvatarChanged, Deleted, GroupsChanged, Renamed};
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

    /** Purge discussions where user has posted. */
    public function handleUserWithPosts(AvatarChanged|GroupsChanged|Renamed $event): void
    {
        // TODO: If user has a lot discussion chunk it and push to the queue job
        /** @phpstan-ignore-next-line Call to an undefined method Illuminate\Database\Eloquent\Relations\Relation::pluck(). */
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
