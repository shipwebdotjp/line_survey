<?php

declare(strict_types=1);

namespace App\Presentation\Http\Survey;

use App\Application\Survey\SaveResponseDraftUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

final class SaveResponseDraftAction
{
    use ActionHelperTrait;

    public function __construct(
        private SaveResponseDraftUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $publicId = $args['public_id'] ?? '';
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'Request body must be an object', null, 422);
        }

        $answerJson = $body['answer_json'] ?? null;

        if ($answerJson === null) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'answer_json is required', null, 422);
        }

        if (!is_array($answerJson) || (!empty($answerJson) && array_is_list($answerJson))) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'answer_json must be an object', null, 422);
        }

        try {
            $respondent = $request->getAttribute('respondent');
            $result = $this->useCase->execute($publicId, $respondent, $answerJson);
            return JsonResponse::success($response, ['draft' => $result]);
        } catch (Throwable $e) {
            return $this->handleUseCaseException($e, $response);
        }
    }
}
