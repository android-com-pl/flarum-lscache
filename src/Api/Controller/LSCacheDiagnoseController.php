<?php

namespace Acpl\FlarumLSCache\Api\Controller;

use Acpl\FlarumLSCache\LiteSpeedServerType;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Str;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LSCacheDiagnoseController implements RequestHandlerInterface
{
    /**
     * @throws PermissionDeniedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $serverParams = $request->getServerParams();
        $lscacheXHttp = Str::of($serverParams['HTTP_X_LSCACHE'] ?? '');
        $lswsEdition = Str::of($serverParams['LSWS_EDITION'] ?? '');
        $serverSoftware = Str::of($serverParams['SERVER_SOFTWARE'] ?? '');

        $litespeedServerType = match (true) {
            $lscacheXHttp->isNotEmpty() => LiteSpeedServerType::ADC,
            $lswsEdition->lower()->startsWith('openlitespeed') => LiteSpeedServerType::OPEN_LITESPEED,
            $serverSoftware->lower()->startsWith('litespeed') => LiteSpeedServerType::LITESPEED,
            default => LiteSpeedServerType::NONE,
        };

        return new JsonResponse([
            'cacheSupported' => ! empty($serverParams['X-LSCACHE']) || $litespeedServerType === LiteSpeedServerType::ADC,
            'litespeedServerType' => $litespeedServerType->value,
            'serverSoftware' => (string) $serverSoftware,
        ]);
    }
}
