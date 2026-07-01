<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\UpdateSurveyUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class UpdateSurveyAction
{
    use QuestionsJsonValidatorTrait;

    public function __construct(
        private UpdateSurveyUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        $data = $request->getParsedBody() ?? [];

        $errorResponse = $this->validateAndDecodeQuestionsJson($data, $response);
        if ($errorResponse) {
            return $errorResponse;
        }

        try {
            $ownerUser = $request->getAttribute('owner_user');
            if (!$ownerUser || !isset($ownerUser['id']) || (int)$ownerUser['id'] <= 0) {
                return JsonResponse::error($response, 'OWNER_SESSION_REQUIRED', 'Admin session is required', null, 401);
            }
            $this->useCase->execute($id, $data, (int)$ownerUser['id']);
            return JsonResponse::success($response, ['success' => true]);
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
