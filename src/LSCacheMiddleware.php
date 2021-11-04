<?php
namespace ACPL\FlarumCache;

use Flarum\Http\RequestUtil;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LSCacheMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!in_array($request->getMethod(), ['GET', 'HEAD'])) {
            return $response;
        } elseif (!in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'])) {
            $route = $request->getAttribute('routeName');
            $response = $response->withHeader('X-LiteSpeed-Purge', $route);
        } else {
            return $response;
        }

        $lscacheString = ['max-age=300'];

        $user = RequestUtil::getActor($request);
        if ($user->isGuest()) {
            array_push($lscacheString, 'public');
        }

        //        $user->groups();

        if (!$response->hasHeader('X-LiteSpeed-Cache-Control')) {
            $response = $response->withHeader('X-LiteSpeed-Cache-Control', implode(',', $lscacheString));
        }

        return $response;
    }
}
