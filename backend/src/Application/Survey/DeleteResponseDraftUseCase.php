<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\ResponseDraftRepository;
use App\Infrastructure\Database\SurveyRepository;

final class DeleteResponseDraftUseCase
{
    use SurveyResolutionTrait;

    public function __construct(
        private SurveyRepository $surveyRepository,
        private ResponseDraftRepository $responseDraftRepository,
        private SurveyAvailabilityValidator $surveyAvailabilityValidator
    ) {
    }

    public function execute(string $publicId, array $respondent): void
    {
        $respondent = $this->resolveRespondent($respondent);
        $survey = $this->resolveSurveyByPublicId($publicId);

        $this->surveyAvailabilityValidator->assertCanRespond($survey);

        $this->responseDraftRepository->deleteBySurveyAndRespondent($survey['id'], $respondent['id']);
    }
}
