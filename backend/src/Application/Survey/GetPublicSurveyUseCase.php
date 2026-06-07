<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Support\DateTimeHelper;
use DateTimeImmutable;

final class GetPublicSurveyUseCase
{
    public function __construct(
        private SurveyRepository $surveyRepository
    ) {
    }

    public function execute(string $publicId): ?array
    {
        $survey = $this->surveyRepository->findByPublicId($publicId);

        if (!$survey) {
            return null;
        }

        $now = DateTimeHelper::nowTokyo();

        $canAnswer = true;
        $reason = null;

        $startsAt = $survey['starts_at'] ? DateTimeHelper::parseTokyo($survey['starts_at']) : null;
        $endsAt = $survey['ends_at'] ? DateTimeHelper::parseTokyo($survey['ends_at']) : null;

        if ($survey['status'] !== 'published') {
            $canAnswer = false;
            $reason = 'not_published';
        } else {
            if ($startsAt && $now < $startsAt) {
                $canAnswer = false;
                $reason = 'not_started';
            } elseif ($endsAt && $now > $endsAt) {
                $canAnswer = false;
                $reason = 'closed';
            }
        }

        return [
            'can_answer' => $canAnswer,
            'reason' => $reason,
            'survey' => [
                'title' => $survey['title'],
                'description' => $survey['description'],
                'questions_json' => $survey['questions_json'],
                'allow_multiple' => (bool)$survey['allow_multiple'],
                'allow_edit' => (bool)$survey['allow_edit'],
                'starts_at' => DateTimeHelper::formatTokyo($startsAt),
                'ends_at' => DateTimeHelper::formatTokyo($endsAt),
            ],
        ];
    }
}
