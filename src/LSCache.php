<?php

namespace ACPL\FlarumCache;

use Illuminate\Support\Str;

class LSCache
{
    const VARY_COOKIE = 'lscache_vary';
    const DEFAULT_DROP_QS = ['fbclid', 'gclid', 'utm*', '_ga'];

    public static function extractRootRouteName(string $name): string
    {
        return Str::singular(explode('.', $name, 2)[0]);
    }
}
