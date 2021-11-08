<?php
namespace ACPL\FlarumCache\Middleware;

use Dflydev\FigCookies\FigResponseCookies;
use Flarum\Http\CookieFactory;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VaryCookieMiddleware implements MiddlewareInterface
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

        if ($user->isGuest()) {
            return $response;
        }

        return $this->withVaryCookie($response, $session);
    }

    private function withVaryCookie(Response $response, Session $session): Response
    {
        return FigResponseCookies::set($response, $this->cookie->make('lscache_vary', $session->token(), $this->config['lifetime'] * 60));
    }
}
