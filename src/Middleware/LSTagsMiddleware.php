<?php

namespace ACPL\FlarumLSCache\Middleware;

use ACPL\FlarumLSCache\LSCache;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class LSTagsMiddleware extends AbstractCacheTagsMiddleware
{
    protected function processTags(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response,
    ): ResponseInterface {
        $routeName = $this->currentRouteName;
        $params = $request->getAttribute('routeParameters');

        $tagParams = [$routeName];

        if (! empty($params)) {
            $rootRouteName = LSCache::extractRootRouteName($routeName);

            // Discussion
            if (! empty($params['id'])) {
                // The id parameter contains the slug. We only need id (int)
                $id = explode('-', $params['id'], 2)[0];
                if (! empty($id)) {
                    $tagParams[] = "{$rootRouteName}_$id";
                }
            }

            // User profile
            if (! empty($params['username'])) {
                $tagParams[] = "{$rootRouteName}_{$params['username']}";
            }

            // Slugs, eg. tag slug
            if (! empty($params['slug'])) {
                $tagParams[] = "{$rootRouteName}_{$params['slug']}";
            }
        }

        return $this->addLSCacheTagsToResponse($response, $tagParams);
    }
}
