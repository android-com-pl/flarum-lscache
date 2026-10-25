<?php

namespace Acpl\FlarumLSCache\Api\Controller;

use Acpl\FlarumLSCache\CacheHeader;
use Acpl\FlarumLSCache\CachePolicy;
use Acpl\FlarumLSCache\Event\LSCachePurged;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

readonly class PurgeLSCacheController implements RequestHandlerInterface
{
    public function __construct(private SettingsRepositoryInterface $settings, private Dispatcher $events)
    {
    }

    /**
     * @throws PermissionDeniedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $canPurge = RequestUtil::getActor($request)->can('lscache.purge');

        // Authenticate CLI requests with the shared purge key because they have no logged-in user.
        if (! $canPurge) {
            $key = $this->settings->get('acpl-lscache.purgeKey');
            $reqKey = $request->getHeaderLine('LSCachePurgeKey');
            if (! empty($key) && ! empty($reqKey) && hash_equals($key, $reqKey)) {
                $canPurge = true;
            }
        }

        if (! $canPurge) {
            throw new PermissionDeniedException();
        }

        $purgeParams = $this->settings->get('acpl-lscache.serve_stale') ? ['stale'] : [];

        /** @var array<string>|null $paths */
        $paths = Arr::get($request->getQueryParams(), 'paths');
        /** @var array<string>|null $tags */
        $tags = Arr::get($request->getQueryParams(), 'tags');

        if (empty($paths) && empty($tags)) {
            $purgeParams[] = '*';
            $paths = ['*'];
        } else {
            if (! empty($paths)) {
                $paths = array_map(static function (string $path): string {
                    $url = parse_url($path);
                    if ($url === false) {
                        return $path;
                    }

                    return ($url['path'] ?? '/').(isset($url['query']) ? '?'.$url['query'] : '');
                }, $paths);
                $purgeParams = array_merge($purgeParams, $paths);
            }
            if (! empty($tags)) {
                $purgeParams = array_merge(
                    $purgeParams,
                    array_map(fn ($tag): string => "tag=$tag", $tags),
                );
            }
        }

        $response = (new EmptyResponse())
            ->withHeader(CacheHeader::PURGE, implode(',', $purgeParams))
            ->withHeader(CacheHeader::CACHE_CONTROL, CachePolicy::NO_CACHE->value);

        $this->events->dispatch(new LSCachePurged([
            'paths' => $paths ?: [],
            'tags' => $tags ?: [],
        ], RequestUtil::getActor($request)));

        return $response;
    }
}
