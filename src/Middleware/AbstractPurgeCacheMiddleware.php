<?php

namespace ACPL\FlarumCache\Middleware;

use ACPL\FlarumCache\LSCacheHeader;
use ACPL\FlarumCache\Utility\LSCachePurger;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractPurgeCacheMiddleware implements MiddlewareInterface
{
    protected string $currentRouteName;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected LSCachePurger $cachePurger,
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (
            ! in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'])
            || $response->getStatusCode() >= 400
        ) {
            return $response;
        }

        $this->currentRouteName = $request->getAttribute('routeName');

        return $this->processPurge($request, $handler, $response);
    }

    abstract protected function processPurge(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response,
    ): ResponseInterface;

    protected function addPurgeParamsToResponse(ResponseInterface $response, array $newPurgeParams): ResponseInterface
    {
        if ($response->hasHeader(LSCacheHeader::PURGE)) {
            $existingPurgeParams = explode(',', $response->getHeaderLine(LSCacheHeader::PURGE));
            $newPurgeParams = array_unique(array_merge($existingPurgeParams, $newPurgeParams));
        }

        if (count($newPurgeParams) < 1) {
            return $response;
        }

        if ($this->settings->get('acpl-lscache.serve_stale') && ! array_key_exists('stale', $newPurgeParams)) {
            array_unshift($newPurgeParams, 'stale');
        }

        return $response->withHeader(LSCacheHeader::PURGE, implode(',', $newPurgeParams));
    }

    protected function getRouteParams(ServerRequestInterface $request): array
    {
        return $request->getAttribute('routeParameters');
    }
}
