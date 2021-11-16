<?php

namespace ACPL\FlarumCache\Listener;

use Exception;
use Flarum\Foundation\Console\CacheClearCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ClearingCacheListener
{
    protected CacheClearCommand $command;

    public function __construct(CacheClearCommand $command)
    {
        $this->command = $command;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        //TODO Input userId
        $this->command->run(new ArrayInput([]), new NullOutput());
    }
}
