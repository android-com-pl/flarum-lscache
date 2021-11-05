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

use Flarum\Extend;

return [
    //    (new Extend\Frontend('admin'))->js(__DIR__ . '/js/dist/admin.js')->css(__DIR__ . '/less/admin.less'),
    //    new Extend\Locales(__DIR__ . '/locale'),

    // Tag routes
    (new Extend\Middleware('forum'))->add(LSTagsMiddleware::class),
    (new Extend\Middleware('api'))->add(LSTagsMiddleware::class),

    // Cache and purge routes
    (new Extend\Middleware('forum'))->add(LSCacheMiddleware::class),
    (new Extend\Middleware('api'))->add(LSCacheMiddleware::class),
];
