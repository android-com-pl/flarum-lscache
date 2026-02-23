<?php

namespace Acpl\FlarumLSCache\Listener;

use Flarum\User\Event\{AvatarChanged, Deleting, GroupsChanged, Renamed};
use Illuminate\Contracts\Events\Dispatcher;

class UserEventSubscriber extends AbstractCachePurgeSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $shared = [AvatarChanged::class, Deleting::class, GroupsChanged::class, Renamed::class];
        foreach ($shared as $event) {
            $this->addPurgeListener($events, $event, $this->handleUserWithPosts(...));
        }
    }

    /** Purge discussions where a user has posted. */
    public function handleUserWithPosts(AvatarChanged|Deleting|GroupsChanged|Renamed $event): void
    {
        $this->purger->addPurgeTags([
            "user_{$event->user->id}",
            "user_{$event->user->username}",
            'posts',
            'discussions',
            ...$event->user->posts()
                ->whereNull('hidden_at')
                ->whereHas('discussion', fn ($query) => $query->whereNull('hidden_at')->where('is_private', false))
                ->distinct()
                ->pluck('discussion_id')
                ->map(fn ($id): string => "discussion_$id")
                ->toArray(),
        ]);
    }
}
