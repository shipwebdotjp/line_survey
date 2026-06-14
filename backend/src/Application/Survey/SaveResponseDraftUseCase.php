<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\ResponseDraftRepository;
use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Support\DateTimeHelper;
use RuntimeException;

final class SaveResponseDraftUseCase
{
    use SurveyResolutionTrait;

    public function __construct(
        private SurveyRepository $surveyRepository,
        private ResponseDraftRepository $responseDraftRepository
    ) {
    }

    public function execute(string $publicId, array $respondent, array $answerJson): array
    {
        $respondent = $this->resolveRespondent($respondent);
        $survey = $this->resolveSurveyByPublicId($publicId);

        $this->validateSurveyAvailability($survey);

        $existingDraft = $this->responseDraftRepository->findBySurveyAndRespondent($survey['id'], $respondent['id']);

        if ($existingDraft) {
            $this->responseDraftRepository->updateBySurveyAndRespondent($survey['id'], $respondent['id'], [
                'answer_json' => $answerJson,
            ]);
        } else {
            $this->responseDraftRepository->save([
                'survey_id' => $survey['id'],
                'respondent_id' => $respondent['id'],
                'answer_json' => $answerJson,
            ]);
        }

        $draft = $this->responseDraftRepository->findBySurveyAndRespondent($survey['id'], $respondent['id']);

        return [
            'survey_public_id' => $publicId,
            'respondent_id' => $respondent['id'],
            'answer_json' => $draft['answer_json'],
            'created_at' => $draft['created_at'],
            'updated_at' => $draft['updated_at'],
        ];
    }

    private function validateSurveyAvailability(array $survey): void
    {
        if ($survey['status'] !== 'published') {
            throw new RuntimeException('Survey is not published', 403);
        }

        $now = DateTimeHelper::nowTokyo();
        $startsAt = $survey['starts_at'] ? DateTimeHelper::parseTokyo($survey['starts_at']) : null;
        $endsAt = $survey['ends_at'] ? DateTimeHelper::parseTokyo($survey['ends_at']) : null;

        if ($startsAt && $now < $startsAt) {
            throw new RuntimeException('Survey has not started yet', 403);
        }

        if ($endsAt && $now > $endsAt) {
            throw new RuntimeException('Survey has already ended', 403);
        }
    }
}
