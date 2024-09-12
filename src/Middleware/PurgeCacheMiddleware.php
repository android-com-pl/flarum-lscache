<?php

namespace ACPL\FlarumCache\Middleware;

use ACPL\FlarumCache\LSCache;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;

class PurgeCacheMiddleware extends AbstractPurgeCacheMiddleware
{
    protected function preparePurgeData(ServerRequestInterface $request): void
    {
        $routeName = $this->currentRouteName;
        $rootRouteName = LSCache::extractRootRouteName($routeName);
        $params = $this->getRouteParams($request);

        if (! empty($params['id']) && $this->shouldPurgeRoute($rootRouteName, $routeName)) {
            $this->cachePurger->addPurgeTag("tag={$rootRouteName}_{$params['id']}");
        }
    }

    private function shouldPurgeRoute(string $rootRouteName, string $routeName): bool
    {
        return ! $this->cachePurger::isResourceSupportedByEvent($rootRouteName)
            && Str::endsWith($routeName, ['.create', '.update', '.delete']);
    }
}
