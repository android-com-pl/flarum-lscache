<?php
namespace ACPL\FlarumCache\Middleware;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Flarum\Http\CookieFactory;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AddVaryCookie implements MiddlewareInterface
{
    public function __construct(CookieFactory $cookie, ConfigRepository $config)
    {
        $this->cookie = $cookie;
        $this->config = $config->get('session');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $response = $handler->handle($request);

        $user = RequestUtil::getActor($request);

        return $this->withVaryCookie($response, $session, $user);
    }

    private function withVaryCookie(Response $response, Session $session, \Flarum\User\User $user): Response
    {
        $setCookie = $this->makeCookie($session);

        if ($user->isGuest()) {
            return FigResponseCookies::set($response, $setCookie->expire());
        }

        return FigResponseCookies::set($response, $setCookie);
    }

    private function makeCookie(Session $session): SetCookie
    {
        return $this->cookie->make($this->getCookieName(), $session->getId(), $this->getSessionLifetimeInSeconds());
    }

    private function getCookieName(): string
    {
        return 'lscache_vary';
    }

    private function getSessionLifetimeInSeconds(): int
    {
        return $this->config['lifetime'] * 60;
    }
}
