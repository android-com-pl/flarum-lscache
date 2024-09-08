<?php

/*
 * This file is part of acpl/flarum-lscache.
 *
 * Copyright (c) android.com.pl.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace ACPL\FlarumCache;

use ACPL\FlarumCache\Api\Controller\LSCacheCsrfResponseController;
use ACPL\FlarumCache\Api\Controller\PurgeLSCacheController;
use ACPL\FlarumCache\Command\LSCacheClearCommand;
use ACPL\FlarumCache\Compatibility\Flarum\Likes\FlarumLikesEventSubscriber;
use ACPL\FlarumCache\Compatibility\Flarum\Tags\FlarumTagsEventSubscriber;
use ACPL\FlarumCache\Compatibility\FriendsOfFlarum\Masquerade\FofMasqueradePurgeCacheMiddleware;
use ACPL\FlarumCache\Compatibility\v17development\FlarumBlog\FlarumBlogEventSubscriber;
use ACPL\FlarumCache\Listener\ClearingCacheListener;
use ACPL\FlarumCache\Listener\DiscussionEventSubscriber;
use ACPL\FlarumCache\Listener\PostEventSubscriber;
use ACPL\FlarumCache\Listener\UserEventSubscriber;
use ACPL\FlarumCache\Middleware\CacheControlMiddleware;
use ACPL\FlarumCache\Middleware\LoginMiddleware;
use ACPL\FlarumCache\Middleware\LogoutMiddleware;
use ACPL\FlarumCache\Middleware\LSTagsMiddlewareAbstract;
use ACPL\FlarumCache\Middleware\PurgeCacheMiddleware;
use ACPL\FlarumCache\Middleware\VaryCookieMiddleware;
use Flarum\Extend;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Http\Middleware\CheckCsrfToken;
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
        ->default('acpl-lscache.drop_qs', implode("\n", LSCache::DEFAULT_DROP_QS)),
    (new Extend\Event())->listen(Saved::class, Listener\UpdateSettingsListener::class),

    // Vary cookie
    (new Extend\Middleware('forum'))->insertAfter(CheckCsrfToken::class, VaryCookieMiddleware::class),
    (new Extend\Middleware('admin'))->insertAfter(CheckCsrfToken::class, VaryCookieMiddleware::class),
    (new Extend\Middleware('api'))->insertAfter(CheckCsrfToken::class, VaryCookieMiddleware::class),
    // LogIn
    (new Extend\Middleware('forum'))->insertAfter(VaryCookieMiddleware::class, LoginMiddleware::class),
    // LogOut
    (new Extend\Middleware('forum'))->insertAfter(VaryCookieMiddleware::class, LogoutMiddleware::class),

    // Tag routes
    (new Extend\Middleware('forum'))->add(LSTagsMiddlewareAbstract::class),
    (new Extend\Middleware('api'))->add(LSTagsMiddlewareAbstract::class),

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
    (new Extend\Console)->command(LSCacheClearCommand::class),
    (new Extend\Event)->listen(ClearingCache::class, ClearingCacheListener::class),

    (new Extend\Event)->subscribe(DiscussionEventSubscriber::class),
    (new Extend\Event)->subscribe(PostEventSubscriber::class),
    (new Extend\Event)->subscribe(UserEventSubscriber::class),

    (new Extend\Conditional)
        ->whenExtensionEnabled('flarum-likes', [
            (new Extend\Event)->subscribe(FlarumLikesEventSubscriber::class),
        ])
        ->whenExtensionEnabled('flarum-tags', [
            (new Extend\Event)->subscribe(FlarumTagsEventSubscriber::class),
        ])
        ->whenExtensionEnabled('fof-masquerade', [
            (new Extend\Middleware('api'))->add(FofMasqueradePurgeCacheMiddleware::class),
        ])
        ->whenExtensionEnabled('v17development-blog', [
            (new Extend\Event)->subscribe(FlarumBlogEventSubscriber::class),
        ]),
];
