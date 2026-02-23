<?php

namespace Acpl\FlarumLSCache\Api\Controller;

use Acpl\FlarumLSCache\CacheHeader;
use Acpl\FlarumLSCache\CachePolicy;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

readonly class PurgeLSCacheController implements RequestHandlerInterface
{
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    /**
     * @throws PermissionDeniedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $canPurge = RequestUtil::getActor($request)->can('lscache.purge');

        // If a command is used, use the temporary key because the user is not logged in
        if (! $canPurge) {
            $key = $this->settings->get('acpl-lscache.purgeKey');
            $reqKey = $request->getHeaderLine('LSCachePurgeKey');
            if (! empty($key) && ! empty($reqKey) && $key === $reqKey) {
                $canPurge = true;
            }
        }

        if (! $canPurge) {
            throw new PermissionDeniedException();
        }

        $purgeParams = $this->settings->get('acpl-lscache.serve_stale') ? ['stale'] : [];

        $paths = Arr::get($request->getQueryParams(), 'paths');
        $tags = Arr::get($request->getQueryParams(), 'tags');

        if (empty($paths) && empty($tags)) {
            $purgeParams[] = '*';
        } else {
            if (! empty($paths)) {
                $purgeParams = array_merge($purgeParams, $paths);
            }
            if (! empty($tags)) {
                $purgeParams = array_merge(
                    $purgeParams,
                    array_map(fn ($tag): string => "tag=$tag", $tags),
                );
            }
        }

        return (new EmptyResponse())
            ->withHeader(CacheHeader::PURGE, implode(',', $purgeParams))
            ->withHeader(CacheHeader::CACHE_CONTROL, CachePolicy::NO_CACHE->value);
    }
}
