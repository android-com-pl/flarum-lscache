<?php

namespace Acpl\FlarumLSCache\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ResourceTagsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('acpl-lscache');
        $this->prepareDatabase([
            'users' => [$this->normalUser()],
            'discussions' => [[
                'id' => 1,
                'title' => 'Cache test discussion',
                'slug' => 'cache-test-discussion',
                'created_at' => '2026-01-01 12:00:00',
                'last_posted_at' => '2026-01-01 12:00:00',
                'user_id' => 2,
                'first_post_id' => 1,
                'last_post_id' => 1,
                'last_post_number' => 1,
                'comment_count' => 1,
                'participant_count' => 1,
            ]],
            'posts' => [[
                'id' => 1,
                'number' => 1,
                'discussion_id' => 1,
                'created_at' => '2026-01-01 12:00:00',
                'user_id' => 2,
                'type' => 'comment',
                'content' => '<t><p>Cache test post</p></t>',
            ]],
        ]);
    }

    public static function resources(): array
    {
        return [
            'forum discussion slug' => ['/d/1-cache-test-discussion', 'discussion,discussion_1'],
            'API discussion' => ['/api/discussions/1', 'discussions.show,discussion_1'],
            'forum username' => ['/u/normal', 'user,user_normal'],
            'API user id' => ['/api/users/2', 'users.show,user_2'],
            'API post' => ['/api/posts/1', 'posts.show,post_1'],
        ];
    }

    #[DataProvider('resources')]
    public function test_resource_response_has_tags_for_targeted_invalidation(string $path, string $tags): void
    {
        $response = $this->send($this->request('GET', $path));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($tags, $response->getHeaderLine('X-LiteSpeed-Tag'));
        self::assertSame('public,max-age=604800', $response->getHeaderLine('X-LiteSpeed-Cache-Control'));
        self::assertFalse($response->hasHeader('X-LiteSpeed-Purge'));
    }
}
