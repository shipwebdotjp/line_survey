<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;

class DeleteResponseUseCase
{
    public function __construct(
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository
    ) {
    }

    public function execute(int $surveyId, int $responseId, int $ownerUserId, Request $request): void
    {
        $survey = $this->surveyRepository->findById($surveyId, $ownerUserId);
        if (!$survey) {
            throw new HttpNotFoundException($request, 'Survey not found');
        }

        $response = $this->responseRepository->findById($responseId);
        if (!$response || (int)$response['survey_id'] !== $surveyId) {
            throw new HttpNotFoundException($request, 'Response not found');
        }

        $this->responseRepository->delete($responseId);
    }
}
