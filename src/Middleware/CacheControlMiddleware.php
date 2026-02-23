<?php

namespace Acpl\FlarumLSCache\Middleware;

use Acpl\FlarumLSCache\CacheHeader;
use Acpl\FlarumLSCache\CachePolicy;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Str;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

readonly class CacheControlMiddleware implements MiddlewareInterface
{
    public function __construct(private SettingsRepositoryInterface $settings) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $method = $request->getMethod();

        if (! $this->settings->get('acpl-lscache.cache_enabled')) {
            return $this->withNoCacheHeader($response);
        }

        if (! in_array($method, ['GET', 'HEAD']) || $response->hasHeader(CacheHeader::CACHE_CONTROL)) {
            return $response;
        }

        $routeName = $request->getAttribute('routeName');

        // Exclude auth routes
        if (Str::startsWith($routeName, ['auth', 'fof-oauth']) || $routeName === 'resetPassword') {
            return $this->withNoCacheHeader($response);
        }

        // Exclude paths specified in settings
        $excludedPaths = Str::of($this->settings->get('acpl-lscache.cache_exclude'));
        if ($excludedPaths->isNotEmpty()) {
            $excludedPathsArr = $excludedPaths->explode("\n");
            $currentPath = Str::of($request->getUri()->getPath());

            foreach ($excludedPathsArr as $pattern) {
                if (! empty(trim($pattern)) && $currentPath->test('/'.addcslashes($pattern, '/').'/')) {
                    return $this->withNoCacheHeader($response);
                }
            }
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
