<?php

namespace Acpl\FlarumLSCache\Command;

use Flarum\Console\AbstractCommand;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\DatabaseSettingsRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class LSCachePurgeCommand extends AbstractCommand
{
    public function __construct(protected UrlGenerator $url, private readonly DatabaseSettingsRepository $settings)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setName('lscache:clear')
            ->setDescription('Purge LiteSpeed cache entries')
            ->addOption(
                'path',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Purge cache entries for this path. Repeat for multiple paths, e.g. --path=/test --path=/tags.',
            )
            ->addOption(
                'tag',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Purge entries with this cache tag (not a Flarum discussion tag). Repeat for multiple tags, e.g. --tag=discussions --tag=users.',
            );
    }

    /**
     * This integration purges the cache through X-LiteSpeed-Purge response headers.
     * CLI commands call the HTTP endpoint so the response passes through LiteSpeed.
     */
    protected function fire(): int
    {
        $this->info('Sending a request to purge LSCache entries...');

        // CLI requests have no logged-in user, so a secret key authorizes cache purges.
        $key = $this->settings->get('acpl-lscache.purgeKey');
        if (empty($key)) {
            $key = Str::password();
            $this->settings->set('acpl-lscache.purgeKey', $key);
        }

        $options = [
            'headers' => ['LSCachePurgeKey' => $key],
        ];

        $paths = $this->input->getOption('path');
        if (! empty($paths)) {
            $options['query']['paths'] = $paths;
        }

        $tags = $this->input->getOption('tag');
        if (! empty($tags)) {
            $options['query']['tags'] = $tags;
        }

        $client = new Client([
            'timeout' => 30,
        ]);

        try {
            // GET skips Flarum's CSRF check; POST would also need a CSRF token or a Flarum API key.
            $client->request('GET', $this->url->to('api')->route('lscache.purge'), $options);
        } catch (GuzzleException $exception) {
            $this->error('Something went wrong while sending the request.');
            $this->error($exception->getMessage());

            return 1;
        }

        $this->info('Notified LiteSpeed Web Server to purge'.((empty($paths) && empty($tags)) ? ' all' : '').' LSCache entries');

        return 0;
    }
}
