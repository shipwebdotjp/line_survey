<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\GetSurveyUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetSurveyAction
{
    public function __construct(
        private GetSurveyUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        $survey = $this->useCase->execute($id);

        if (!$survey) {
            return JsonResponse::error($response, 'NOT_FOUND', 'Survey not found', null, 404);
        }

        return JsonResponse::success($response, $survey);
    }
}
