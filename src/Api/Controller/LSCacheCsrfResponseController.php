<?php

namespace Acpl\FlarumLSCache\Api\Controller;

use Acpl\FlarumLSCache\CacheHeader;
use Acpl\FlarumLSCache\CachePolicy;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This controller is used to respond to CSRF requests.
 */
class LSCacheCsrfResponseController implements RequestHandlerInterface
{
    public function __construct(protected ConfigRepository $config)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return (new EmptyResponse())
            ->withHeader(
                CacheHeader::CACHE_CONTROL,
                // Subtract 2 minutes (120 seconds)
                // from the session lifetime to set the cache to expire before the actual session does.
                // This is to prevent a potential issue where an expired CSRF token might be served from the cache.
                CachePolicy::PRIVATE->maxAge(($this->config->get('session.lifetime') * 60) - 120)
            );
    }
}
