<?php

namespace ACPL\FlarumCache\Middleware;

use ACPL\FlarumCache\LSCache;
use ACPL\FlarumCache\LSCacheHeadersEnum;
use Dflydev\FigCookies\FigResponseCookies;
use Flarum\Http\CookieFactory;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LogoutMiddleware implements MiddlewareInterface
{
    private CookieFactory $cookie;
    private UrlGenerator $url;

    public function __construct(CookieFactory $cookie, UrlGenerator $url)
    {
        $this->cookie = $cookie;
        $this->url = $url;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $logoutUri = new Uri($this->url->to('forum')->route('logout'));
        $response = $handler->handle($request);
        if ($request->getUri()->getPath() === $logoutUri->getPath() && $response instanceof RedirectResponse) {
            $response = $response->withHeader(LSCacheHeadersEnum::CACHE_CONTROL, 'no-cache');

            return $this->withExpiredVaryCookie($response, $request->getAttribute('session'));
        }

        return $response;
    }

    private function withExpiredVaryCookie(Response $response, Session $session): Response
    {
        $response = FigResponseCookies::remove($response, LSCache::VARY_COOKIE);

        return FigResponseCookies::set($response, $this->cookie->make(LSCache::VARY_COOKIE, $session->token())->expire());
    }
}
