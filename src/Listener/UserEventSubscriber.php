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
        $this->purger->addPurgeTags([
            "user_{$event->user->id}",
            "user_{$event->user->username}",
            'posts',
            'discussions',
            ...array_map(fn ($id) => "discussion_$id", $discussions),
        ]);

        $discussionCount = $event->user->posts()->getRelation('discussion')->distinct()->count();
        if ($discussionCount < 50) {
            /** @phpstan-ignore-next-line Call to an undefined method Illuminate\Database\Eloquent\Relations\Relation::pluck(). */
            $discussions = $event->user->posts()->getRelation('discussion')->pluck('id')->toArray();
            $this->purger->addPurgeTags([

            ]);
        }
    }
}
