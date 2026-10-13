<?php

namespace Acpl\FlarumLSCache\Tests\integration;

use Acpl\FlarumLSCache\Utility\LSCachePurger;
use Flarum\Discussion\Discussion;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Post\Post;
use Flarum\User\User;
use FoF\AuthorChange\Event\{DiscussionUserChanged, PostUserChanged};
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
class AuthorChangeIntegrationTest extends ResourceTagsTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-author-change');
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

    public static function changes(): array
    {
        $author = ['relationships' => ['user' => ['data' => ['type' => 'users', 'id' => '1']]]];
        $createdAt = ['attributes' => ['createdAt' => '2025-12-01T12:00:00Z']];

        return [
            'discussion author' => ['discussions', $author, ['index', 'discussions', 'user_1', 'user_admin']],
            'discussion creation date' => ['discussions', $createdAt, ['index', 'discussions']],
            'post author' => ['posts', $author, ['posts', 'post_1', 'user_1', 'user_admin']],
            'post creation date' => ['posts', $createdAt, ['posts', 'post_1']],
            'post edit date' => ['posts', ['attributes' => ['editedAt' => '2026-01-02T12:00:00Z']], ['posts', 'post_1']],
        ];
    }

    #[DataProvider('changes')]
    public function test_author_and_date_changes_purge_related_caches(string $resource, array $changes, array $expectedTags): void
    {
        $response = $this->send($this->request('PATCH', "/api/$resource/1", [
            'authenticatedAs' => 1,
            'json' => ['data' => ['type' => $resource, 'id' => '1', ...$changes]],
        ]));

        self::assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $tags = explode(',', $response->getHeaderLine('X-LiteSpeed-Purge'));
        $authorTags = isset($changes['relationships']) ? [] : ['user_2', 'user_normal'];
        foreach (['discussion_1', ...$authorTags, ...$expectedTags] as $tag) {
            self::assertContains("tag=$tag", $tags);
        }
    }

    public static function unauthorizedChanges(): array
    {
        return array_map(fn (array $change): array => array_slice($change, 0, 2), self::changes());
    }

    #[DataProvider('unauthorizedChanges')]
    public function test_normal_user_cannot_change_author_or_dates(string $resource, array $changes): void
    {
        $response = $this->send($this->request('PATCH', "/api/$resource/1", [
            'authenticatedAs' => 2,
            'json' => ['data' => ['type' => $resource, 'id' => '1', ...$changes]],
        ]));

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($response->hasHeader('X-LiteSpeed-Purge'));
    }

    public static function authorlessResources(): array
    {
        return [['discussions'], ['posts']];
    }

    #[DataProvider('authorlessResources')]
    public function test_author_change_event_purges_both_old_and_new_author(string $resource): void
    {
        $container = $this->app()->getContainer();
        $model = $resource === 'discussions' ? Discussion::findOrFail(1) : Post::findOrFail(1);
        $oldUser = $model->user;
        $model->user()->associate(User::findOrFail(1));

        $event = $resource === 'discussions'
            ? new DiscussionUserChanged($model, $oldUser)
            : new PostUserChanged($model, $oldUser);
        $container->make(Dispatcher::class)->dispatch($event);

        $tags = $container->make(LSCachePurger::class)->getPurgeData()['tags'];
        foreach (['discussion_1', 'user_1', 'user_admin', 'user_2', 'user_normal'] as $tag) {
            self::assertContains($tag, $tags);
        }
    }

    #[DataProvider('authorlessResources')]
    public function test_assigning_author_to_resource_without_previous_author(string $resource): void
    {
        $this->prepareDatabase([$resource => [['id' => 1, 'user_id' => null]]]);
        $response = $this->send($this->request('PATCH', "/api/$resource/1", [
            'authenticatedAs' => 1,
            'json' => ['data' => [
                'type' => $resource,
                'id' => '1',
                'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => '1']]],
            ]],
        ]));

        self::assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $tags = explode(',', $response->getHeaderLine('X-LiteSpeed-Purge'));
        self::assertContains('tag=discussion_1', $tags);
        self::assertContains('tag=user_1', $tags);
        self::assertContains('tag=user_admin', $tags);
        self::assertNotContains('tag=user_', $tags);
    }

    #[DataProvider('authorlessResources')]
    public function test_changing_date_of_resource_without_author(string $resource): void
    {
        $this->prepareDatabase([$resource => [['id' => 1, 'user_id' => null]]]);
        $response = $this->send($this->request('PATCH', "/api/$resource/1", [
            'authenticatedAs' => 1,
            'json' => ['data' => [
                'type' => $resource,
                'id' => '1',
                'attributes' => ['createdAt' => '2025-12-01T12:00:00Z'],
            ]],
        ]));

        self::assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $tags = explode(',', $response->getHeaderLine('X-LiteSpeed-Purge'));
        self::assertContains('tag=discussion_1', $tags);
        self::assertNotContains('tag=user_', $tags);
    }
}
