<?php

namespace ACPL\FlarumCache\Middleware;

use ACPL\FlarumCache\LSCache;
use ACPL\FlarumCache\LSCacheHeadersEnum;
use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LSCachePurgeMiddleware implements MiddlewareInterface
{
    private SettingsRepositoryInterface $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (
            ! in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE']) ||
            $response->hasHeader(LSCacheHeadersEnum::PURGE) ||
            $response->getStatusCode() >= 400
        ) {
            return $response;
        }

        $routeName = $request->getAttribute('routeName');

        $isDiscussion = Str::startsWith($routeName, 'discussions');
        $body = $request->getParsedBody();

        // If this is just an update of the last read post, there is no point in clearing the public cache
        if ($isDiscussion && Arr::get($body, 'data.attributes.lastReadPostNumber')) {
            return $response;
        }

        $purgeParams = [];

        $params = $request->getAttribute('routeParameters');

        $isPost = Str::startsWith($routeName, 'posts');

        if ($isDiscussion || $isPost) {
            $purgeList = $this->settings->get('acpl-lscache.purge_on_discussion_update');
            if (! empty($purgeList)) {
                $purgeList = explode("\n", $purgeList);
                // Get only valid items
                $purgeList = array_filter($purgeList, fn ($item) => Str::startsWith($item, ['/', 'tag=']));
                $purgeParams = array_merge($purgeParams, $purgeList);
            }

            // If this is a post update, we don't need to clear the home page cache
            $isPostUpdate = $routeName === 'posts.update';
            if (($isPostUpdate && Arr::has($body, 'data.attributes.isHidden')) || ! $isPostUpdate) {
                array_push($purgeParams, 'tag=default', 'tag=index', 'tag=discussions.index');
            }
        }

        if ($isPost) {
            $discussionId = Arr::get($body, 'data.relationships.discussion.data.id');

            if (! $discussionId) {
                // When an existing post is edited or deleted
                $postId = Arr::get($body, 'data.id');

                if ($postId) {
                    $discussionId = Post::find($postId)->discussion_id;
                }
            }

            if ($discussionId) {
                array_push($purgeParams, "tag=discussions$discussionId", "tag=discussion$discussionId");
            }
        }

        if (Str::endsWith($routeName, ['.create', '.update', '.delete'])) {
            $rootRouteName = LSCache::extractRootRouteName($routeName);

            // discussions.index is handled earlier
            if (! $isDiscussion) {
                $purgeParams[] = "tag=$rootRouteName.index";
            }

            if (! empty($params) && ! empty($params['id'])) {
                $purgeParams[] = "tag=$rootRouteName{$params['id']}";
            }
        }

        //Clear user profile cache when updating FriendsOfFlarum/masquerade fields
        if ($routeName === 'masquerade.api.configure.save') {
            $user = RequestUtil::getActor($request);
            $purgeParams[] = "tag=users$user->id,tag=masquerade$user->id";
        }

        if (count($purgeParams) < 1) {
            return $response;
        }

        if ($this->settings->get('acpl-lscache.serve_stale')) {
            array_unshift($purgeParams, 'stale');
        }

        return $response->withHeader(LSCacheHeadersEnum::PURGE, implode(',', $purgeParams));
    }
}
