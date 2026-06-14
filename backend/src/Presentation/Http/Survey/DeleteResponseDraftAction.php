<?php

declare(strict_types=1);

namespace App\Presentation\Http\Survey;

use App\Application\Survey\DeleteResponseDraftUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

final class DeleteResponseDraftAction
{
    use ActionHelperTrait;

    public function __construct(
        private DeleteResponseDraftUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $publicId = $args['public_id'] ?? '';

        try {
            $respondent = $request->getAttribute('respondent');
            $this->useCase->execute($publicId, $respondent);
            return JsonResponse::success($response, null, 204);
        } catch (Throwable $e) {
            return $this->handleUseCaseException($e, $response);
        }
    }
}
