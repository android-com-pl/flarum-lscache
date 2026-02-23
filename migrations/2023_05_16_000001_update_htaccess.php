<?php

use Acpl\FlarumLSCache\Utility\HtaccessManager;
use Flarum\Foundation\Paths;
use Flarum\Http\CookieFactory;
use Flarum\Settings\SettingsRepositoryInterface;

function lsCacheGetHtaccessManager(): HtaccessManager
{
    $paths = resolve(Paths::class);
    $cookie = resolve(CookieFactory::class);
    $settings = resolve(SettingsRepositoryInterface::class);

    return new HtaccessManager($paths, $cookie, $settings);
}

return [
    'up' => function (): void {
        $htaccessManager = lsCacheGetHtaccessManager();
        $htaccessManager->updateHtaccess();
    },
    'down' => function (): void {
        $htaccessManager = lsCacheGetHtaccessManager();
        $htaccessManager->removeLsCacheBlock();
    },
];
