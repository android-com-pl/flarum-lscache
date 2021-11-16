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

use ACPL\FlarumCache\Api\Controller\LsCacheCsrfResponseController;
use ACPL\FlarumCache\Api\Controller\PurgeLsCacheController;
use ACPL\FlarumCache\Command\LsCacheClearCommand;
use ACPL\FlarumCache\Listener\ClearingCacheListener;
use ACPL\FlarumCache\Middleware\LoginMiddleware;
use ACPL\FlarumCache\Middleware\LogoutMiddleware;
use ACPL\FlarumCache\Middleware\LSCacheMiddleware;
use ACPL\FlarumCache\Middleware\LSTagsMiddleware;
use ACPL\FlarumCache\Middleware\VaryCookieMiddleware;
use Flarum\Extend;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Http\Middleware\CheckCsrfToken;
use Flarum\Http\Middleware\StartSession;

return [
    (new Extend\Frontend('admin'))->js(__DIR__ . '/js/dist/admin.js'),
    (new Extend\Frontend('forum'))->js(__DIR__ . '/js/dist/forum.js'),
    new Extend\Locales(__DIR__ . '/locale'),

    // Vary cookie
    (new Extend\Middleware('forum'))->insertAfter(StartSession::class, VaryCookieMiddleware::class),
    (new Extend\Middleware('admin'))->insertAfter(StartSession::class, VaryCookieMiddleware::class),
    (new Extend\Middleware('api'))->insertAfter(StartSession::class, VaryCookieMiddleware::class),
    // LogIn
    (new Extend\Middleware('forum'))->insertAfter(VaryCookieMiddleware::class, LoginMiddleware::class),
    // LogOut
    (new Extend\Middleware('forum'))->insertAfter(VaryCookieMiddleware::class, LogoutMiddleware::class),

    // Tag routes
    (new Extend\Middleware('forum'))->add(LSTagsMiddleware::class),
    (new Extend\Middleware('api'))->add(LSTagsMiddleware::class),

    // Cache and purge routes
    (new Extend\Middleware('forum'))->insertAfter(CheckCsrfToken::class, LSCacheMiddleware::class),
    (new Extend\Middleware('api'))->insertAfter(CheckCsrfToken::class, LSCacheMiddleware::class),

    // A workaround for the CSRF cache issue. The JS script fetches this path to update the CSRF
    (new Extend\Routes('api'))->get('/lscache-csrf', 'lscache.csrf', LsCacheCsrfResponseController::class),

    // Purge cache
    (new Extend\Routes('api'))->delete('/lscache-purge', 'lscache.purge', PurgeLsCacheController::class),
    (new Extend\Console())->command(LsCacheClearCommand::class),
    // Purge LsCache while Flarum cache is being cleared
    (new Extend\Event())->listen(ClearingCache::class, ClearingCacheListener::class),
];
