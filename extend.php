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

use ACPL\FlarumCache\Middleware\AddLSCacheHeader;
use ACPL\FlarumCache\Middleware\AddLSTagsHeader;
use ACPL\FlarumCache\Middleware\AddVaryCookie;
use Flarum\Extend;
use Flarum\Http\Middleware\CheckCsrfToken;
use Flarum\Http\Middleware\StartSession;

return [
    //    (new Extend\Frontend('admin'))->js(__DIR__ . '/js/dist/admin.js')->css(__DIR__ . '/less/admin.less'),
    //    new Extend\Locales(__DIR__ . '/locale'),

    // Vary cookie
    (new Extend\Middleware('forum'))->insertAfter(StartSession::class, AddVaryCookie::class),
    (new Extend\Middleware('api'))->insertAfter(StartSession::class, AddVaryCookie::class),

    // Tag routes
    (new Extend\Middleware('forum'))->add(AddLSTagsHeader::class),
    (new Extend\Middleware('api'))->add(AddLSTagsHeader::class),

    // Cache and purge routes
    (new Extend\Middleware('forum'))->insertAfter(CheckCsrfToken::class, AddLSCacheHeader::class),
    (new Extend\Middleware('api'))->insertAfter(CheckCsrfToken::class, AddLSCacheHeader::class),
];
