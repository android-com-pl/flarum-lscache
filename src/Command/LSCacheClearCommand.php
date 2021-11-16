<?php

namespace ACPL\FlarumCache\Command;

use Flarum\Api\ApiKey;
use Flarum\Console\AbstractCommand;
use Flarum\Http\UrlGenerator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputOption;

class LSCacheClearCommand extends AbstractCommand
{
    protected UrlGenerator $url;

    public function __construct(UrlGenerator $url)
    {
        parent::__construct();
        $this->url = $url;
    }

    public function configure()
    {
        $this->setName('lscache:clear')
            ->setDescription('Purge all LsCache.')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED, 'The ID of the user who is an admin. If not specified, ID 1 is used.', 1);
    }

    protected function fire()
    {
        $this->info('Sending a request to purge all LSCache entries...');
        $userId = $this->input->getOption('userId');

        // Create a temporary API key to authorize a request
        $apiKey = new ApiKey();
        $keyStr = $apiKey::generate()->key;
        $apiKey->key = $keyStr;
        $apiKey->user_id = $userId;
        $apiKey->touch();

        $client = new Client();
        try {
            $client->request('DELETE', $this->url->to('api')->route('lscache.purge'), [
                //TODO Is it possible to authorize with just a key?
                'headers' => ['Authorization' => "Token $keyStr; userId=$userId"],
            ]);
        } catch (GuzzleException $exception) {
            $apiKey->delete();
            $this->error(
                "Something went wrong while sending the request. Check if the user with ID $userId is an administrator. Use the `--userId [id]` option.",
            );

            return 1;
        }

        $apiKey->delete();
        $this->info('Notified LiteSpeed Web Server to purge all LSCache entries');
    }
}
