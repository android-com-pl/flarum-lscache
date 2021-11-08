<?php
namespace ACPL\FlarumCache\Middleware;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Flarum\Http\CookieFactory;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
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

    private function withVaryCookie(Response $response, Session $session, User $user): Response
    {
        if ($user->isGuest()) {
            return FigResponseCookies::set($response, $this->makeCookie($session)->expire());
        }

        return FigResponseCookies::set($response, $this->makeCookie($session, $this->getSessionLifetimeInSeconds()));
    }

    private function makeCookie(Session $session, $maxAge = null): SetCookie
    {
        return $this->cookie->make($this->getCookieName(), $session->getId(), $maxAge);
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
