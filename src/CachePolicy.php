<?php

namespace Acpl\FlarumLSCache;

enum CachePolicy: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case NO_CACHE = 'no-cache';

    public function maxAge(int $seconds): string
    {
        if ($this === self::NO_CACHE) {
            throw new \LogicException('Cannot set max-age for NO_CACHE policy.');
        }

        return "$this->value,max-age=$seconds";
    }
}
