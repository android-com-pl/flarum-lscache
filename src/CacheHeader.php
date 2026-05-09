<?php

namespace Acpl\FlarumLSCache;

final class CacheHeader
{
    const string CACHE_CONTROL = 'X-LiteSpeed-Cache-Control';
    const string PURGE = 'X-LiteSpeed-Purge';
    const string TAG = 'X-LiteSpeed-Tag';
    const string VARY = 'X-LiteSpeed-Vary';
}
