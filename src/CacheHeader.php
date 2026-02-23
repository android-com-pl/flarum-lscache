<?php

namespace Acpl\FlarumLSCache;

final class CacheHeader
{
    const CACHE_CONTROL = 'X-LiteSpeed-Cache-Control';
    const PURGE = 'X-LiteSpeed-Purge';
    const TAG = 'X-LiteSpeed-Tag';
    const VARY = 'X-LiteSpeed-Vary';
}
