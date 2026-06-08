<?php

declare(strict_types=1);

namespace App\Presentation\Http\Survey;

use App\Application\Survey\UpdateResponseUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

final class UpdateResponseAction
{
    use ActionHelperTrait;

    public function __construct(
        private UpdateResponseUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $publicId = $args['public_id'] ?? '';
        $editToken = $args['edit_token'] ?? '';
        $body = $request->getParsedBody();

        $answerJson = $body['answer_json'] ?? null;

        if ($answerJson === null) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'Answer data is required', null, 400);
        }

        try {
            $idToken = $this->extractTokenFromHeader($request);
            $result = $this->useCase->execute($publicId, $editToken, $idToken, $answerJson);
            return JsonResponse::success($response, $result);
        } catch (Throwable $e) {
            return $this->handleUseCaseException($e, $response);
        }
    }
}
