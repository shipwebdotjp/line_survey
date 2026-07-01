<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\CreateSurveyUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateSurveyAction
{
    use QuestionsJsonValidatorTrait;

    public function __construct(
        private CreateSurveyUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody() ?? [];

        $errorResponse = $this->validateAndDecodeQuestionsJson($data, $response);
        if ($errorResponse) {
            return $errorResponse;
        }

        try {
            $ownerUser = $request->getAttribute('owner_user');
            $id = $this->useCase->execute($data, (int)$ownerUser['id']);
            return JsonResponse::success($response, ['id' => $id], 201);
        } catch (\RuntimeException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), null, 400);
        } catch (\Throwable $e) {
            error_log((string)$e);
            return JsonResponse::error($response, 'INTERNAL_ERROR', 'An internal error occurred', null, 500);
        }
    }
}
