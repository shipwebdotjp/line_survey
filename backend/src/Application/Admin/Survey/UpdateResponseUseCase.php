<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;

class UpdateResponseUseCase
{
    public function __construct(
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository
    ) {
    }

    public function execute(int $surveyId, int $responseId, array $answerJson, Request $request): array
    {
        $survey = $this->surveyRepository->findById($surveyId);
        if (!$survey) {
            throw new HttpNotFoundException($request, 'Survey not found');
        }

        $response = $this->responseRepository->findById($responseId);
        if (!$response || (int)$response['survey_id'] !== $surveyId) {
            throw new HttpNotFoundException($request, 'Response not found');
        }

        $updateData = [
            'answer_json' => $answerJson,
        ];

        $this->responseRepository->update($responseId, $updateData);

        return $this->responseRepository->findByIdWithRespondent($responseId);
    }
}
