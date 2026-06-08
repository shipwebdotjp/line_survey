<?php

declare(strict_types=1);

namespace App\Presentation\Http\Survey;

use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

trait ActionHelperTrait
{
    /**
     * @param Request $request
     * @return string
     * @throws \RuntimeException
     */
    protected function extractTokenFromHeader(Request $request): string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (empty($authHeader)) {
            throw new \RuntimeException('Authorization header is missing', 401);
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new \RuntimeException('Invalid Authorization header format', 401);
        }

        return substr($authHeader, 7);
    }

    protected function handleUseCaseException(Throwable $e, Response $response): Response
    {
        $code = $e->getCode();
        $httpStatus = (is_int($code) && $code >= 400 && $code < 600) ? $code : 500;

        $errorCode = match ($httpStatus) {
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            default => 'INTERNAL_ERROR',
        };

        return JsonResponse::error($response, $errorCode, $e->getMessage(), null, $httpStatus);
    }
}
