<?php

namespace Acpl\FlarumLSCache\Tests\integration;

use Acpl\FlarumLSCache\Event\LSCachePurging;
use Acpl\FlarumLSCache\Utility\LSCachePurger;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
class BlogIntegrationTest extends ResourceTagsTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-lock', 'fof-blog');
        $this->setting('blog_redirects_enabled', 'disabled');
        $this->setting('blog_tags', '1');
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
        $this->prepareDatabase([
            'tags' => [['id' => 1, 'name' => 'Blog', 'slug' => 'blog', 'color' => '#123456']],
            'discussion_tag' => [['discussion_id' => 1, 'tag_id' => 1]],
            'blog_meta' => [[
                'id' => 7,
                'discussion_id' => 1,
                'summary' => 'Original summary',
                'is_featured' => false,
                'is_sized' => false,
                'is_pending_review' => false,
            ]],
        ]);
    }

    public static function resources(): array
    {
        return [
            'blog overview' => ['/blog', 'blog.overview'],
            'blog category' => ['/blog/category/blog', 'blog.category'],
            'blog article' => ['/blog/1-cache-test-discussion', 'blog.post,blog_1'],
        ];
    }

    public static function metadataChanges(): array
    {
        return [
            'summary' => [false, ['summary' => 'Updated summary']],
            'featured image' => [false, ['featuredImage' => 'https://example.com/image.png']],
            'feature' => [false, ['isFeatured' => true]],
            'unfeature' => [false, ['isFeatured' => false]],
            'approve' => [true, ['isPendingReview' => false]],
        ];
    }

    #[DataProvider('metadataChanges')]
    public function test_metadata_changes_purge_blog_and_discussion_caches(bool $pending, array $attributes): void
    {
        $this->prepareDatabase(['blog_meta' => [[
            'id' => 7,
            'is_featured' => isset($attributes['isFeatured']) && ! $attributes['isFeatured'],
            'is_pending_review' => $pending,
        ]]]);
        $response = $this->send($this->request('PATCH', '/api/blogMeta/7', [
            'authenticatedAs' => 1,
            'json' => ['data' => ['type' => 'blogMeta', 'id' => '7', 'attributes' => $attributes]],
        ]));

        self::assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $tags = explode(',', $response->getHeaderLine('X-LiteSpeed-Purge'));
        foreach (['blog.overview', 'blog.category', 'blog_1', 'discussion_1', 'discussions'] as $tag) {
            self::assertContains("tag=$tag", $tags);
        }
        self::assertNotContains('tag=blog_7', $tags);
    }

    public function test_discussion_purge_also_invalidates_blog_pages(): void
    {
        $response = $this->send($this->request('PATCH', '/api/discussions/1', [
            'authenticatedAs' => 1,
            'json' => ['data' => ['type' => 'discussions', 'id' => '1', 'attributes' => ['title' => 'Renamed article']]],
        ]));

        self::assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $tags = explode(',', $response->getHeaderLine('X-LiteSpeed-Purge'));
        foreach (['blog.overview', 'blog.category', 'blog_1'] as $tag) {
            self::assertContains("tag=$tag", $tags);
        }
    }

    public function test_creating_blog_metadata_invalidates_cached_discussion_and_blog(): void
    {
        $this->database()->table('blog_meta')->where('id', 7)->delete();
        $response = $this->send($this->request('POST', '/api/blogMeta', [
            'authenticatedAs' => 1,
            'json' => ['data' => [
                'type' => 'blogMeta',
                'attributes' => ['summary' => 'New article summary'],
                'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]],
            ]],
        ]));

        self::assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $tags = explode(',', $response->getHeaderLine('X-LiteSpeed-Purge'));
        foreach (['blog.overview', 'blog.category', 'blog_1', 'discussion_1', 'discussions'] as $tag) {
            self::assertContains("tag=$tag", $tags);
        }
    }

    public function test_only_discussion_tags_are_mapped_to_blog_articles(): void
    {
        $container = $this->app()->getContainer();
        $container->make(Dispatcher::class)->dispatch(new LSCachePurging([
            'paths' => [],
            'tags' => ['discussion_1', 'discussion_invalid', 'user_2'],
        ]));

        self::assertSame(['blog_1'], $container->make(LSCachePurger::class)->getPurgeData()['tags']);
    }
}
