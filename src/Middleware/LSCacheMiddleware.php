<?php
namespace ACPL\FlarumCache\Middleware;

use ACPL\FlarumCache\Utils;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LSCacheMiddleware implements MiddlewareInterface
{
    private SettingsRepositoryInterface $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $method = $request->getMethod();

        if (!in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'DELETE']) || $response->hasHeader('X-LiteSpeed-Cache-Control')) {
            return $response;
        }

        $currentRoute = $request->getUri()->getPath();
        $routeName = $request->getAttribute('routeName');
        $params = $request->getAttribute('routeParameters');

        if ($routeName === 'lscache.csrf') {
            return $response->withHeader('X-LiteSpeed-Cache-Control', 'no-cache');
        }

        //Purge cache
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            $lscachePurgeString = [$currentRoute];

            if (Str::endsWith($routeName, ['.create', '.update', '.delete'])) {
                $rootRouteName = Utils::extractRootRouteName($routeName);
                array_push($lscachePurgeString, "tag=$rootRouteName.index");

                if (!empty($params) && !empty($params['id'])) {
                    array_push($lscachePurgeString, "tag=$rootRouteName{$params['id']}");
                }
            }

            if (Str::startsWith($routeName, 'discussions') || Str::startsWith($routeName, 'posts')) {
                array_push($lscachePurgeString, 'tag=default', 'tag=index');

                $purgeList = $this->settings->get('acpl-lscache.purge_on_discussion_update');
                if (!empty($purgeList)) {
                    $purgeList = explode("\n", $purgeList);
                    $purgeList = array_filter($purgeList, fn($item) => Str::startsWith($item, ['/', 'tag=']));
                    $lscachePurgeString = array_merge($lscachePurgeString, $purgeList);
                }
            }

            return $response->withHeader('X-LiteSpeed-Purge', implode(',', $lscachePurgeString));
        }

        $lscacheString = [];

        //Guest only cache for now
        $user = RequestUtil::getActor($request);
        if ($user->isGuest()) {
            array_push($lscacheString, 'public');

            $publicTtl = $this->settings->get('acpl-lscache.public_cache_ttl') ?: 300;
            array_push($lscacheString, "max-age=$publicTtl");
        } else {
            array_push($lscacheString, 'private', 'no-cache');
        }

        //TODO user group cache vary https://docs.litespeedtech.com/lscache/devguide/#cache-vary
        //TODO private cache

        return $response->withHeader('X-LiteSpeed-Cache-Control', implode(',', $lscacheString));
    }
}
