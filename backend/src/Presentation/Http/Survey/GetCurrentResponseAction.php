<?php

declare(strict_types=1);

namespace App\Presentation\Http\Survey;

use App\Application\Survey\GetCurrentResponseUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

final class GetCurrentResponseAction
{
    use ActionHelperTrait;

    public function __construct(
        private GetCurrentResponseUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $publicId = $args['public_id'] ?? '';

        try {
            $idToken = $this->extractTokenFromHeader($request);
            $result = $this->useCase->execute($publicId, $idToken);
            return JsonResponse::success($response, $result);
        } catch (Throwable $e) {
            return $this->handleUseCaseException($e, $response);
        }
    }
}
