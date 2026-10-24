<?php

namespace Acpl\FlarumLSCache\Frontend;

use Flarum\Frontend\Document;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class DisableAssetsRevisionCheck
{
    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function __invoke(Document $document, ServerRequestInterface $request): void
    {
        if (
            $this->settings->get('acpl-lscache.cache_enabled')
            && RequestUtil::getActor($request)->isGuest()
            && (! $this->settings->get('acpl-lscache.clearing_cache_listener') || $this->settings->get('acpl-lscache.serve_stale'))
        ) {
            // Cached guest pages may retain an older revision even after a reload.
            unset($document->payload['assetsRevision']);
        }
    }
}
