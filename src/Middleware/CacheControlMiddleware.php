<?php

namespace Acpl\FlarumLSCache\Middleware;

use Acpl\FlarumLSCache\CacheExclusions;
use Acpl\FlarumLSCache\CacheHeader;
use Acpl\FlarumLSCache\CachePolicy;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

readonly class CacheControlMiddleware implements MiddlewareInterface
{
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $method = $request->getMethod();

        if (! $this->settings->get('acpl-lscache.cache_enabled')) {
            return $this->withNoCacheHeader($response);
        }

        if (! in_array($method, ['GET', 'HEAD'])) {
            return $response;
        }

        if (CacheExclusions::matches($request, $this->settings->get('acpl-lscache.cache_exclude'))) {
            return $this->withNoCacheHeader($response);
        }

        if ($response->hasHeader(CacheHeader::CACHE_CONTROL)) {
            return $response;
        }

        // Guest-only cache
        $user = RequestUtil::getActor($request);
        if ($user->isGuest()) {
            return $response->withHeader(
                CacheHeader::CACHE_CONTROL,
                CachePolicy::PUBLIC->maxAge(((int) $this->settings->get('acpl-lscache.public_cache_ttl')) ?: 604_800)
            );
        }

        return $this->withNoCacheHeader($response);
    }

    private function withNoCacheHeader(ResponseInterface $response): ResponseInterface
    {
        return $response->withHeader(CacheHeader::CACHE_CONTROL, CachePolicy::NO_CACHE->value);
    }
}
