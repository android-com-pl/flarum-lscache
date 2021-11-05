<?php

namespace ACPL\FlarumCache;

class Utils{
    public static function extractRootRouteName(string $name): string
    {
        return explode('.', $name, 2)[0];
    }
}
