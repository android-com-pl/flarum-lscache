<?php

namespace ACPL\FlarumCache\Middleware;

use ACPL\FlarumCache\LSCache;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PurgeCacheMiddleware extends AbstractPurgeCacheMiddleware
{
    protected function processPurge(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response,
    ): ResponseInterface {
        $purgeParams = $this->getPurgeParamsFromCachePurger();
        $purgeParams = array_merge($purgeParams, $this->getPurgeParamsFromRoute($request));

        return $this->addPurgeParamsToResponse($response, array_unique($purgeParams));
    }

    private function getPurgeParamsFromCachePurger(): array
    {
        $purgeData = $this->cachePurger->getPurgeData();
        $paths = $purgeData['paths'] ?? [];
        $tags = array_map(fn ($tag) => "tag=$tag", $purgeData['tags'] ?? []);

        return array_merge($paths, $tags);
    }

    private function getPurgeParamsFromRoute(ServerRequestInterface $request): array
    {
        $routeName = $this->currentRouteName;
        $rootRouteName = LSCache::extractRootRouteName($routeName);
        $params = $this->getRouteParams($request);

        if (! empty($params['id']) && $this->shouldPurgeRoute($rootRouteName, $routeName)) {
            return ["tag={$rootRouteName}_{$params['id']}"];
        }

        return [];
    }

    private function shouldPurgeRoute(string $rootRouteName, string $routeName): bool
    {
        return ! $this->cachePurger::isResourceSupportedByEvent($rootRouteName)
            && Str::endsWith($routeName, ['.create', '.update', '.delete']);
    }
}
