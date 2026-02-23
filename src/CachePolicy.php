<?php

namespace Acpl\FlarumLSCache;

enum CachePolicy: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case NO_CACHE = 'no-cache';

    public function maxAge(int $seconds): string
    {
        return "$this->value,max-age=$seconds";
    }
}
