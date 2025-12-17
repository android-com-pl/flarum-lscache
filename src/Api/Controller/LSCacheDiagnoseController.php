<?php

namespace ACPL\FlarumLSCache\Api\Controller;

use ACPL\FlarumLSCache\LiteSpeedServerType;
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

        $lscacheXHttp = Str::of($_SERVER['HTTP_X_LSCACHE'] ?? '');
        $lswsEdition = Str::of($_SERVER['LSWS_EDITION'] ?? '');
        $serverSoftware = Str::of($_SERVER['SERVER_SOFTWARE'] ?? '');

        $litespeedServerType = match (true) {
            $lscacheXHttp->isNotEmpty() => LiteSpeedServerType::ADC,
            $lswsEdition->lower()->startsWith('openlitespeed') => LiteSpeedServerType::OPEN_LITESPEED,
            $serverSoftware->lower()->startsWith('litespeed') => LiteSpeedServerType::LITESPEED,
            default => LiteSpeedServerType::NONE,
        };

        return new JsonResponse([
            'cacheSupported' => ! empty($_SERVER['X-LSCACHE']) || $litespeedServerType === LiteSpeedServerType::ADC,
            'litespeedServerType' => $litespeedServerType->value,
            'serverSoftware' => (string) $serverSoftware,
        ]);
    }
}
