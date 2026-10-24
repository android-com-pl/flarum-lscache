<?php

namespace Acpl\FlarumLSCache\Event;

use Flarum\User\User;

/**
 * A purge was requested through the LSCache endpoint.
 */
class LSCachePurged
{
    /**
     * @param  array{paths: array<string>, tags: array<string>}  $data
     */
    public function __construct(public array $data, public ?User $actor = null)
    {
    }
}
