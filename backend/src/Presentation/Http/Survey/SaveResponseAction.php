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
    use ActionHelperTrait;

    public function __construct(
        private SaveResponseUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $publicId = $args['public_id'] ?? '';
        $body = $request->getParsedBody();

        $answerJson = $body['answer_json'] ?? null;

        if ($answerJson === null) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'Answer data is required', null, 400);
        }

        try {
            $respondent = $request->getAttribute('respondent');
            $result = $this->useCase->execute($publicId, $respondent, $answerJson);
            return JsonResponse::success($response, $result);
        } catch (Throwable $e) {
            return $this->handleUseCaseException($e, $response);
        }
    }
}
