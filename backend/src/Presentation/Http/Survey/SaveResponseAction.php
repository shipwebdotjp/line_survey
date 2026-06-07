<?php

declare(strict_types=1);

namespace App\Presentation\Http\Survey;

use App\Application\Survey\SaveResponseUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

final class SaveResponseAction
{
    public function __construct(
        private SaveResponseUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $publicId = $args['public_id'] ?? '';
        $body = $request->getParsedBody();

        $idToken = $body['id_token'] ?? '';
        $answerJson = $body['answer_json'] ?? null;

        if (empty($idToken)) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'ID Token is required', null, 400);
        }

        if ($answerJson === null) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'Answer data is required', null, 400);
        }

        try {
            $result = $this->useCase->execute($publicId, $idToken, $answerJson);
            return JsonResponse::success($response, $result);
        } catch (Throwable $e) {
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
}
