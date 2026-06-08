<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Support\DateTimeHelper;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;

class GetResponseUseCase
{
    public function __construct(
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository
    ) {
    }

    public function execute(int $surveyId, int $responseId, Request $request): array
    {
        $survey = $this->surveyRepository->findById($surveyId);
        if (!$survey) {
            throw new HttpNotFoundException($request, 'Survey not found');
        }

        $response = $this->responseRepository->findByIdWithRespondent($responseId);
        if (!$response || (int)$response['survey_id'] !== $surveyId) {
            throw new HttpNotFoundException($request, 'Response not found');
        }

        return [
            'id' => $response['id'],
            'answer_json' => $response['answer_json'],
            'survey_snapshot_json' => $response['survey_snapshot_json'],
            'submitted_at' => DateTimeHelper::formatTokyo(DateTimeHelper::parseTokyo($response['submitted_at'])),
            'updated_at' => DateTimeHelper::formatTokyo(DateTimeHelper::parseTokyo($response['updated_at'])),
            'email_sent_at' => DateTimeHelper::formatTokyo($response['email_sent_at'] ? DateTimeHelper::parseTokyo($response['email_sent_at']) : null),
            'email_error' => $response['email_error'],
            'respondent' => [
                'name' => $response['respondent_name'] ?? '',
                'email' => $response['respondent_email'] ?? '',
                'line_display_name' => $response['respondent_line_display_name'] ?? '',
                'honorific' => $response['respondent_honorific'] ?? '',
                'is_manually_entered' => (bool)($response['respondent_is_manually_entered'] ?? false),
                'respondent_master_id' => $response['respondent_respondent_master_id'] ?? null,
            ],
        ];
    }
}
