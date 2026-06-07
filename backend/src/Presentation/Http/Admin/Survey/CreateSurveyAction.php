<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\CreateSurveyUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateSurveyAction
{
    public function __construct(
        private CreateSurveyUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (isset($data['questions_json'])) {
            $questions = is_string($data['questions_json'])
                ? json_decode($data['questions_json'], true)
                : $data['questions_json'];

            if ($questions === null && json_last_error() !== JSON_ERROR_NONE) {
                return JsonResponse::error($response, 'VALIDATION_ERROR', 'Invalid JSON in questions_json', null, 400);
            }
            $data['questions_json'] = $questions;
        }

        try {
            $id = $this->useCase->execute($data);
            return JsonResponse::success($response, ['id' => $id], 201);
        } catch (\Exception $e) {
            return JsonResponse::error($response, 'INTERNAL_ERROR', $e->getMessage(), null, 500);
        }
    }
}
