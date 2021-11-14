<?php
namespace ACPL\FlarumCache\Middleware;

use ACPL\FlarumCache\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LSTagsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!in_array($request->getMethod(), ['GET', 'HEAD']) || $response->hasHeader('X-LiteSpeed-Tag')) {
            return $response;
        }

        $routeName = $request->getAttribute('routeName');
        $rootRouteName = Utils::extractRootRouteName($routeName);

        $params = $request->getAttribute('routeParameters');

        $lsTagsString = $routeName;

        if (!empty($params) && !empty($params['id'])) {
            $lsTagsString .= ",$rootRouteName{$params['id']}";
        }

        return $response->withHeader('X-LiteSpeed-Tag', $lsTagsString);
    }
}
