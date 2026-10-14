<?php

namespace Acpl\FlarumLSCache\Tests\integration;

use Acpl\FlarumLSCache\Listener\PostEventSubscriber;
use Acpl\FlarumLSCache\Utility\LSCachePurger;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\Attributes\DataProvider;

class PostDeletionTest extends ResourceTagsTest
{
    public static function deletedPosts(): array
    {
        return [
            'visible' => [null, true, 2],
            'already hidden' => ['2026-01-02 12:00:00', false, 2],
            'visible without author' => [null, true, null],
        ];
    }

    #[DataProvider('deletedPosts')]
    public function test_deletion_purges_only_previously_visible_posts(?string $hiddenAt, bool $shouldPurge, ?int $userId): void
    {
        $container = $this->app()->getContainer();
        $post = Post::findOrFail(1);
        $post->hidden_at = $hiddenAt;
        $post->user_id = $userId;

        $purger = $this->createPartialMock(LSCachePurger::class, ['executePurge']);
        $purger->expects(self::once())->method('executePurge');
        $purger->clearPurgeData();
        $events = new Dispatcher();
        $subscriber = new PostEventSubscriber($purger, $container->make(SettingsRepositoryInterface::class));
        $subscriber->subscribe($events);
        $events->dispatch(new Deleted($post));

        $tags = $purger->getPurgeData()['tags'];
        $purger->clearPurgeData();
        if ($shouldPurge) {
            foreach (['posts', 'post_1', 'discussion_1', 'default', 'index', 'discussions'] as $tag) {
                self::assertContains($tag, $tags);
            }
            if ($userId === null) {
                self::assertNotContains('user_', $tags);
            }
        } else {
            self::assertSame([], $tags);
        }
    }
}
