<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\ResponseDraftRepository;
use App\Infrastructure\Database\SurveyRepository;

final class GetResponseDraftUseCase
{
    use SurveyResolutionTrait;

    public function __construct(
        private SurveyRepository $surveyRepository,
        private ResponseDraftRepository $responseDraftRepository,
        private SurveyAvailabilityValidator $surveyAvailabilityValidator
    ) {
    }

    public function execute(string $publicId, array $respondent): ?array
    {
        $respondent = $this->resolveRespondent($respondent);
        $survey = $this->resolveSurveyByPublicId($publicId);

        $this->surveyAvailabilityValidator->assertCanRespond($survey);

        $draft = $this->responseDraftRepository->findBySurveyAndRespondent($survey['id'], $respondent['id']);

        if (!$draft) {
            return null;
        }

        return [
            'survey_public_id' => $publicId,
            'respondent_id' => $respondent['id'],
            'answer_json' => $draft['answer_json'],
            'created_at' => $draft['created_at'],
            'updated_at' => $draft['updated_at'],
        ];
    }
}
