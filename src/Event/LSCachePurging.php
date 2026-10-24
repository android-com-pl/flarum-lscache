<?php

namespace Acpl\FlarumLSCache\Event;

use Flarum\User\User;

/**
 * The LSCache is going to be purged.
 */
class LSCachePurging
{
    /**
     * @param  array{paths: array<string>, tags: array<string>}  $data
     */
    public function __construct(public array $data, public ?User $actor = null)
    {
    }
}
