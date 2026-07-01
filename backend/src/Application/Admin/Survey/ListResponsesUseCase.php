<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Support\DateTimeHelper;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;

class ListResponsesUseCase
{
    public function __construct(
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository
    ) {
    }

    public function execute(int $surveyId, int $ownerUserId, Request $request): array
    {
        $survey = $this->surveyRepository->findById($surveyId, $ownerUserId);
        if (!$survey) {
            throw new HttpNotFoundException($request, 'Survey not found');
        }

        $responses = $this->responseRepository->findBySurveyIdWithRespondent($surveyId);

        return array_map(function ($response) {
            return [
                'id' => $response['id'],
                'respondent_name' => $response['respondent_name'] ?? '',
                'respondent_email' => $response['respondent_email'] ?? '',
                'respondent_line_display_name' => $response['respondent_line_display_name'] ?? '',
                'respondent_honorific' => $response['respondent_honorific'] ?? '',
                'submitted_at' => DateTimeHelper::formatTokyo(DateTimeHelper::parseTokyo($response['submitted_at'])),
                'updated_at' => DateTimeHelper::formatTokyo(DateTimeHelper::parseTokyo($response['updated_at'])),
                'email_sent_at' => DateTimeHelper::formatTokyo($response['email_sent_at'] ? DateTimeHelper::parseTokyo($response['email_sent_at']) : null),
                'email_error' => $response['email_error'],
            ];
        }, $responses);
    }
}
