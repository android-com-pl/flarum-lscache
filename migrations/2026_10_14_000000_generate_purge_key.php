<?php

use Flarum\Database\Migration;
use Illuminate\Support\Str;

return Migration::addSettings([
    'acpl-lscache.purgeKey' => Str::password(),
]);
