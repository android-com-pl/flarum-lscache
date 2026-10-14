<?php

namespace Acpl\FlarumLSCache\Command;

use Flarum\Console\AbstractCommand;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Str;

class RotatePurgeKeyCommand extends AbstractCommand
{
    public function __construct(private readonly SettingsRepositoryInterface $settings)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('lscache:rotate-purge-key')
            ->setDescription('Rotate the shared key used to purge LiteSpeed cache from CLI commands and queue workers');
    }

    protected function fire(): int
    {
        $this->settings->set('acpl-lscache.purgeKey', Str::password());
        $this->info('LiteSpeed cache purge key rotated.');

        return self::SUCCESS;
    }
}
