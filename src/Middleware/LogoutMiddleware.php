<?php
namespace ACPL\FlarumCache\Middleware;

use Dflydev\FigCookies\FigResponseCookies;
use Flarum\Http\CookieFactory;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
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

    public function __construct(CookieFactory $cookie, ConfigRepository $config, UrlGenerator $url)
    {
        $this->cookie = $cookie;
        $this->config = $config->get('session');
        $this->url = $url;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $logoutUri = new Uri($this->url->to('forum')->path('/logout'));
        $response = $handler->handle($request);
        if ($request->getUri()->getPath() === $logoutUri->getPath()) {
            $user = RequestUtil::getActor($request);

            if ($user->isGuest()) {
                return $this->withExpiredVaryCookie($response, $request->getAttribute('session'));
            }
        }

        return $response;
    }

    private function withExpiredVaryCookie(Response $response, Session $session): Response
    {
        $response = FigResponseCookies::remove($response, 'lscache_vary');
        return FigResponseCookies::set($response, $this->cookie->make('lscache_vary', $session->token())->expire());
    }
}
