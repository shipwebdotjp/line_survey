<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\DeleteSurveyUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteSurveyAction
{
    public function __construct(
        private DeleteSurveyUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);

        try {
            $this->useCase->execute($id);
            return JsonResponse::success($response, ['success' => true]);
        } catch (\RuntimeException $e) {
            if ($e->getCode() === 409) {
                return JsonResponse::error($response, 'CONFLICT', $e->getMessage(), null, 409);
            }
            if ($e->getCode() === 404) {
                return JsonResponse::error($response, 'NOT_FOUND', $e->getMessage(), null, 404);
            }
            error_log((string)$e);
            return JsonResponse::error($response, 'INTERNAL_ERROR', 'An unexpected error occurred', null, 500);
        } catch (\Throwable $e) {
            error_log((string)$e);
            return JsonResponse::error($response, 'INTERNAL_ERROR', 'An unexpected error occurred', null, 500);
        }
    }
}
