<?php

namespace Acpl\FlarumLSCache;

use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;

class CacheExclusions
{
    public static function matches(ServerRequestInterface $request, ?string $excludedPaths): bool
    {
        $routeName = (string) $request->getAttribute('routeName', '');
        if (Str::startsWith($routeName, ['auth', 'fof-oauth']) || $routeName === 'resetPassword') {
            return true;
        }

        // Flarum strips the forum base path and API mount before these middleware run.
        $path = $request->getUri()->getPath();
        foreach ([
            '/auth',
            '/reset',
            '/logout',
            '/notifications',
            '/confirm',
            '/maintenance',
            '/gdpr',
            '/twofactor',
            '/__clockwork',
            '/fof/download',
            '/lscache-purge',
        ] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        foreach (explode("\n", $excludedPaths ?? '') as $pattern) {
            if (! empty(trim($pattern)) && @preg_match('/'.addcslashes($pattern, '/').'/', $path) === 1) {
                return true;
            }
        }

        return false;
    }
}
