<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\DuplicateSurveyUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DuplicateSurveyAction
{
    public function __construct(
        private DuplicateSurveyUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        try {
            $newId = $this->useCase->execute($id);
            return JsonResponse::success($response, ['id' => $newId], 201);
        } catch (\RuntimeException $e) {
            if ($e->getCode() === 404) {
                return JsonResponse::error($response, 'NOT_FOUND', $e->getMessage(), null, 404);
            }
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), null, 400);
        } catch (\Throwable $e) {
            error_log((string)$e);
            return JsonResponse::error($response, 'INTERNAL_ERROR', 'An internal error occurred', null, 500);
        }
    }
}
