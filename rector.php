<?php

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths( [__DIR__.'/extend.php' ,__DIR__.'/migrations', __DIR__.'/src'])
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
    );
