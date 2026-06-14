<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\ResponseDraftRepository;
use App\Infrastructure\Database\SurveyRepository;

final class SaveResponseDraftUseCase
{
    use SurveyResolutionTrait;

    public function __construct(
        private SurveyRepository $surveyRepository,
        private ResponseDraftRepository $responseDraftRepository,
        private SurveyAvailabilityValidator $surveyAvailabilityValidator
    ) {
    }

    public function execute(string $publicId, array $respondent, array $answerJson): array
    {
        $respondent = $this->resolveRespondent($respondent);
        $survey = $this->resolveSurveyByPublicId($publicId);

        $this->surveyAvailabilityValidator->assertCanRespond($survey);

        $this->responseDraftRepository->upsertBySurveyAndRespondent([
            'survey_id' => $survey['id'],
            'respondent_id' => $respondent['id'],
            'answer_json' => $answerJson,
        ]);

        $draft = $this->responseDraftRepository->findBySurveyAndRespondent($survey['id'], $respondent['id']);

        return [
            'survey_public_id' => $publicId,
            'respondent_id' => $respondent['id'],
            'answer_json' => $draft['answer_json'],
            'created_at' => $draft['created_at'],
            'updated_at' => $draft['updated_at'],
        ];
    }
}
