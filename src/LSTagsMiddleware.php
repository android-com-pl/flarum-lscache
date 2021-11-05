<?php
namespace ACPL\FlarumCache;

use Flarum\Http\RequestUtil;
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


        $user = RequestUtil::getActor($request);
        if ($user->isGuest()) {
            $lsTagsString = 'public:' . $routeName;

            $params = $request->getAttribute('routeParameters');
            if (!empty($params) && !empty($params['id'])) {
                $lsTagsString .= ",public:$rootRouteName{$params['id']}";
            }
            return $response->withHeader('X-LiteSpeed-Tag', $lsTagsString);
        }

        //TODO private cache

        return $response;
    }
}
