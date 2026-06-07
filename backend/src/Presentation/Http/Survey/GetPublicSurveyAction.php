<?php

declare(strict_types=1);

namespace App\Presentation\Http\Survey;

use App\Application\Survey\GetPublicSurveyUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetPublicSurveyAction
{
    public function __construct(
        private GetPublicSurveyUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $publicId = $args['public_id'] ?? '';
        $result = $this->useCase->execute($publicId);

        if ($result === null) {
            return JsonResponse::error($response, 'NOT_FOUND', 'Survey not found', null, 404);
        }

        return JsonResponse::success($response, $result);
    }
}
