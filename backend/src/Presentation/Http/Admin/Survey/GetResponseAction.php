<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\GetResponseUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GetResponseAction
{
    public function __construct(
        private GetResponseUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $surveyIdStr = $args['id'] ?? '';
        $responseIdStr = $args['responseId'] ?? '';

        if (!is_numeric($surveyIdStr) || !is_numeric($responseIdStr)) {
            return JsonResponse::error($response, 'Invalid ID', 'VALIDATION_ERROR', 400);
        }

        $id = (int)$surveyIdStr;
        $responseId = (int)$responseIdStr;

        if ($id <= 0 || $responseId <= 0) {
            return JsonResponse::error($response, 'Invalid ID', 'VALIDATION_ERROR', 400);
        }

        $data = $this->useCase->execute($id, $responseId, $request);

        return JsonResponse::success($response, $data);
    }
}
