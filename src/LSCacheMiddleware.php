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
        $method = $request->getMethod();

        if (!in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'DELETE'])) {
            return $response;
        }

        $currentRoute = $request->getUri()->getPath();

        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            //TODO purge homepage, discussion api, etc on post update
            return $response->withHeader('X-LiteSpeed-Purge', $currentRoute);
        }

        $lscacheString = ['max-age=300'];

        $user = RequestUtil::getActor($request);
        if ($user->isGuest()) {
            array_push($lscacheString, 'public');
        }
        //TODO User Group cache vary

        if (!$response->hasHeader('X-LiteSpeed-Cache-Control')) {
            $response = $response->withHeader('X-LiteSpeed-Cache-Control', implode(',', $lscacheString));
        }

        return $response;
    }
}
