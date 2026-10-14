<?php

namespace Acpl\FlarumLSCache\Tests\integration;

use Acpl\FlarumLSCache\Compatibility\Flarum\{LikesEventSubscriber, TagsEventSubscriber};
use Acpl\FlarumLSCache\Compatibility\FoF\{MergeDiscussionsEventSubscriber, MovePostsSubscriber};
use Acpl\FlarumLSCache\Listener\DiscussionEventSubscriber;
use Acpl\FlarumLSCache\Utility\LSCachePurger;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Hidden;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\User\User;
use FoF\MergeDiscussions\Events\DiscussionWasMerged;
use FoF\MovePosts\Event\PostsMoved;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
class EventPurgeIntegrationTest extends ResourceTagsTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // HTTP tests run under CLI; keep purge data for middleware instead of sending a CLI job.
        $purger = $this->createPartialMock(LSCachePurger::class, ['executePurge']);
        $purger->clearPurgeData();
        $this->extend(new class($purger) implements ExtenderInterface {
            public function __construct(private LSCachePurger $purger)
            {
            }

            public function extend(Container $container, ?Extension $extension = null): void
            {
                $container->instance(LSCachePurger::class, $this->purger);
            }
        });
    }

    public static function tagChanges(): array
    {
        return [
            'replace tag' => [[['type' => 'tags', 'id' => '2']]],
            'remove all tags' => [[]],
        ];
    }

    #[DataProvider('tagChanges')]
    public function test_retagging_purges_old_and_current_tag_pages(array $newTags): void
    {
        $this->extension('flarum-tags');
        $this->prepareDatabase([
            'tags' => [
                ['id' => 1, 'name' => 'Old tag', 'slug' => 'old-tag', 'color' => '#123456'],
                ['id' => 2, 'name' => 'New tag', 'slug' => 'new-tag', 'color' => '#123456'],
            ],
            'discussion_tag' => [['discussion_id' => 1, 'tag_id' => 1]],
        ]);

        $response = $this->send($this->request('PATCH', '/api/discussions/1', [
            'authenticatedAs' => 1,
            'json' => ['data' => [
                'type' => 'discussions',
                'id' => '1',
                'relationships' => ['tags' => ['data' => $newTags]],
            ]],
        ]));

        self::assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        self::assertEquals(array_column($newTags, 'id'), Discussion::findOrFail(1)->tags->modelKeys());
        $tags = explode(',', $response->getHeaderLine('X-LiteSpeed-Purge'));
        foreach (['discussion_1', 'index', 'discussions', 'tags', 'tag_old-tag'] as $tag) {
            self::assertContains("tag=$tag", $tags);
        }
        if ($newTags !== []) {
            self::assertContains('tag=tag_new-tag', $tags);
        }
    }

    public function test_renaming_user_purges_old_and_new_profile_pages(): void
    {
        $response = $this->send($this->request('PATCH', '/api/users/2', [
            'authenticatedAs' => 1,
            'json' => ['data' => ['type' => 'users', 'id' => '2', 'attributes' => ['username' => 'renamed']]],
        ]));

        self::assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        self::assertSame('renamed', User::findOrFail(2)->username);
        $tags = explode(',', $response->getHeaderLine('X-LiteSpeed-Purge'));
        foreach (['user_normal', 'user_renamed', 'user_2', 'posts', 'discussions', 'discussion_1'] as $tag) {
            self::assertContains("tag=$tag", $tags);
        }
    }

    public function test_changing_exclusions_does_not_purge_existing_cache(): void
    {
        $response = $this->send($this->request('POST', '/api/settings', [
            'authenticatedAs' => 1,
            'json' => ['acpl-lscache.cache_exclude' => '^/discussions$'],
        ]));

        self::assertSame(204, $response->getStatusCode());
        self::assertFalse($response->hasHeader('X-LiteSpeed-Purge'));
    }

    public static function authorlessEvents(): array
    {
        return [
            'discussion' => [DiscussionEventSubscriber::class, ['discussion_1']],
            'retagging' => [TagsEventSubscriber::class, ['discussion_1', 'tags']],
            'liking' => [LikesEventSubscriber::class, ['discussion_1', 'post_1', 'user_admin']],
            'merging' => [MergeDiscussionsEventSubscriber::class, ['discussion_1', 'discussion_2', 'post_1']],
            'moving' => [MovePostsSubscriber::class, ['discussion_1', 'discussion_2', 'post_1']],
        ];
    }

    #[DataProvider('authorlessEvents')]
    public function test_events_without_authors_still_purge_content(string $subscriberClass, array $expectedTags): void
    {
        $container = $this->app()->getContainer();
        $discussion = Discussion::findOrFail(1);
        $discussion->user_id = null;
        $discussion->setRelation('user', null)->setRelation('tags', new Collection());
        $post = Post::findOrFail(1);
        $post->user_id = null;
        $post->setRelation('user', null)->setRelation('discussion', $discussion);
        $otherDiscussion = new Discussion();
        $otherDiscussion->id = 2;
        $actor = User::findOrFail(1);
        $event = match ($subscriberClass) {
            DiscussionEventSubscriber::class => new Hidden($discussion),
            TagsEventSubscriber::class => new DiscussionWasTagged($discussion, $actor, []),
            LikesEventSubscriber::class => new PostWasLiked($post, $actor),
            MergeDiscussionsEventSubscriber::class => new DiscussionWasMerged($actor, new Collection([$post]), $discussion, new Collection([$otherDiscussion])),
            MovePostsSubscriber::class => new PostsMoved(new Collection([$post]), $otherDiscussion, $discussion, $actor),
        };
        $purger = $container->make(LSCachePurger::class);
        $events = new Dispatcher();
        (new $subscriberClass($purger, $container->make(SettingsRepositoryInterface::class)))->subscribe($events);
        $events->dispatch($event);

        $tags = $purger->getPurgeData()['tags'];
        foreach ($expectedTags as $tag) {
            self::assertContains($tag, $tags);
        }
        self::assertNotContains('user_', $tags);
    }

    public function test_merging_purges_destination_source_and_post_list(): void
    {
        $this->extension('fof-merge-discussions');
        $this->prepareDatabase([
            'discussions' => [[
                'id' => 2,
                'title' => 'Source discussion',
                'slug' => 'source-discussion',
                'created_at' => '2026-01-02 12:00:00',
                'last_posted_at' => '2026-01-02 12:00:00',
                'user_id' => 2,
                'first_post_id' => 2,
                'last_post_id' => 2,
                'last_post_number' => 1,
                'comment_count' => 1,
                'participant_count' => 1,
            ]],
            'posts' => [[
                'id' => 2,
                'number' => 1,
                'discussion_id' => 2,
                'created_at' => '2026-01-02 12:00:00',
                'user_id' => 2,
                'type' => 'comment',
                'content' => '<t><p>Source post</p></t>',
            ]],
        ]);

        $response = $this->send($this->request('POST', '/api/discussions/1/merge', [
            'authenticatedAs' => 1,
            'json' => ['ids' => [2], 'ordering' => 'suffix'],
        ]));

        self::assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        self::assertNull(Discussion::find(2));
        self::assertSame(1, Post::findOrFail(2)->discussion_id);
        $tags = explode(',', $response->getHeaderLine('X-LiteSpeed-Purge'));
        foreach (['discussion_1', 'discussion_2', 'index', 'discussions', 'posts', 'post_2', 'user_2', 'user_normal'] as $tag) {
            self::assertContains("tag=$tag", $tags);
        }
    }
}
