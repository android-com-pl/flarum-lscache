<?php

/*
 * This file is part of acpl/flarum-lscache.
 *
 * Copyright (c) android.com.pl.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Acpl\FlarumLSCache;

use Acpl\FlarumLSCache\Api\Controller\{LSCacheCsrfResponseController,
    LSCacheDiagnoseController,
    PurgeLSCacheController};
use Acpl\FlarumLSCache\Command\LSCachePurgeCommand;
use Acpl\FlarumLSCache\Compatibility\{Flarum\LikesEventSubscriber,
    Flarum\TagsEventSubscriber,
    FoF\MergeDiscussionsEventSubscriber,
    FoF\MovePostsSubscriber,};
use Acpl\FlarumLSCache\Listener\{ClearingCacheListener,
    DiscussionEventSubscriber,
    PostEventSubscriber,
    UserEventSubscriber};
use Acpl\FlarumLSCache\Middleware\{CacheControlMiddleware,
    CacheTagsMiddleware,
    LoginMiddleware,
    LogoutMiddleware,
    PurgeCacheMiddleware,
    StatusCodesCacheMiddleware,
    VaryCookieMiddleware};
use Flarum\Api\Context;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Http\Middleware\CheckCsrfToken;
use Flarum\Http\Middleware\InjectActorReference;
use Flarum\Settings\Event\Saved;

return [
    (new Extend\Frontend('admin'))->js(__DIR__.'/js/dist/admin.js'),
    (new Extend\Frontend('forum'))->js(__DIR__.'/js/dist/forum.js'),
    new Extend\Locales(__DIR__.'/locale'),

    // Settings
    (new Extend\Settings())
        ->default('acpl-lscache.cache_enabled', true)
        ->default('acpl-lscache.public_cache_ttl', 604_800)
        ->default('acpl-lscache.clearing_cache_listener', true)
        ->default('acpl-lscache.drop_qs', implode("\n", LSCache::DEFAULT_DROP_QS))
        ->default('acpl-lscache.status_codes_cache', "404 3600\n403 3600\n500 120"),
    (new Extend\Event())->listen(Saved::class, Listener\UpdateSettingsListener::class),

    (new Extend\ApiResource(Resource\ForumResource::class))->fields(fn (): array => [
        Schema\Boolean::make('canPurgeLSCache')
            ->get(fn (object $forum, Context $context): bool => $context->getActor()->can('lscache.purge')),
    ]),

    // Vary cookie
    (new Extend\Middleware('forum'))->insertAfter(CheckCsrfToken::class, VaryCookieMiddleware::class),
    (new Extend\Middleware('admin'))->insertAfter(CheckCsrfToken::class, VaryCookieMiddleware::class),
    (new Extend\Middleware('api'))->insertAfter(CheckCsrfToken::class, VaryCookieMiddleware::class),
    // LogIn
    (new Extend\Middleware('forum'))->insertAfter(VaryCookieMiddleware::class, LoginMiddleware::class),
    // LogOut
    (new Extend\Middleware('forum'))->insertAfter(VaryCookieMiddleware::class, LogoutMiddleware::class),

    // Tag routes
    (new Extend\Middleware('forum'))->add(CacheTagsMiddleware::class),
    (new Extend\Middleware('api'))->add(CacheTagsMiddleware::class),

    // Cache status codes
    (new Extend\Middleware('forum'))->insertAfter(InjectActorReference::class, StatusCodesCacheMiddleware::class),
    (new Extend\Middleware('admin'))->insertAfter(InjectActorReference::class, StatusCodesCacheMiddleware::class),
    (new Extend\Middleware('api'))->insertAfter(InjectActorReference::class, StatusCodesCacheMiddleware::class),

    // Cache routes
    (new Extend\Middleware('forum'))->insertAfter(VaryCookieMiddleware::class, CacheControlMiddleware::class),
    (new Extend\Middleware('api'))->insertAfter(VaryCookieMiddleware::class, CacheControlMiddleware::class),

    // A workaround for the CSRF cache issue. The JS script fetches this path to update the CSRF
    (new Extend\Routes('api'))->get('/lscache-csrf', 'lscache.csrf', LSCacheCsrfResponseController::class),

    // Purge cache on update
    (new Extend\Middleware('forum'))->add(PurgeCacheMiddleware::class),
    (new Extend\Middleware('admin'))->add(PurgeCacheMiddleware::class),
    (new Extend\Middleware('api'))->add(PurgeCacheMiddleware::class),

    // Purge cache
    (new Extend\Routes('api'))->get('/lscache-purge', 'lscache.purge', PurgeLSCacheController::class),
    (new Extend\Console)->command(LSCachePurgeCommand::class),
    (new Extend\Event)->listen(ClearingCache::class, ClearingCacheListener::class),

    (new Extend\Event)->subscribe(DiscussionEventSubscriber::class),
    (new Extend\Event)->subscribe(PostEventSubscriber::class),
    (new Extend\Event)->subscribe(UserEventSubscriber::class),

    // Diagnose
    (new Extend\Routes('api'))->get('/lscache-diagnose', 'lscache.diagnose', LSCacheDiagnoseController::class),

    // Extensions
    (new Extend\Conditional)
        ->whenExtensionEnabled('flarum-likes', fn (): array => [
            (new Extend\Event)->subscribe(LikesEventSubscriber::class),
        ])
        ->whenExtensionEnabled('flarum-tags', fn (): array => [
            (new Extend\Event)->subscribe(TagsEventSubscriber::class),
        ])
        ->whenExtensionEnabled('fof-merge-discussions', fn (): array => [
            (new Extend\Event)->subscribe(MergeDiscussionsEventSubscriber::class),
        ])
        ->whenExtensionEnabled('fof-move-posts', fn (): array => [
            (new Extend\Event)->subscribe(MovePostsSubscriber::class),
        ]),
];
