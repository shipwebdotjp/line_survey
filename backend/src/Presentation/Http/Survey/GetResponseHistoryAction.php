<?php

declare(strict_types=1);

namespace App\Presentation\Http\Survey;

use App\Application\Survey\GetResponseHistoryUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

final class GetResponseHistoryAction
{
    use ActionHelperTrait;

    public function __construct(
        private GetResponseHistoryUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $surveyPublicId = $queryParams['survey_public_id'] ?? null;

            if ($surveyPublicId !== null && !is_string($surveyPublicId)) {
                return JsonResponse::error($response, 'BAD_REQUEST', 'Invalid survey_public_id', null, 400);
            }

            $respondent = $request->getAttribute('respondent');
            $result = $this->useCase->execute($respondent, $surveyPublicId);
            return JsonResponse::success($response, $result);
        } catch (Throwable $e) {
            return $this->handleUseCaseException($e, $response);
        }
    }
}
