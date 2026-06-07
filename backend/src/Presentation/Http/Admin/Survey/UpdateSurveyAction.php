<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\UpdateSurveyUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class UpdateSurveyAction
{
    public function __construct(
        private UpdateSurveyUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
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
            $this->useCase->execute($id, $data);
            return JsonResponse::success($response, ['success' => true]);
        } catch (\RuntimeException $e) {
            if ($e->getCode() === 404) {
                return JsonResponse::error($response, 'NOT_FOUND', $e->getMessage(), null, 404);
            }
            return JsonResponse::error($response, 'INTERNAL_ERROR', $e->getMessage(), null, 500);
        } catch (\Exception $e) {
            return JsonResponse::error($response, 'INTERNAL_ERROR', $e->getMessage(), null, 500);
        }
    }
}
