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

        if ($survey['status'] !== 'published') {
            $canAnswer = false;
            $reason = 'not_published';
        } else {
            $startsAt = $survey['starts_at'] ? new DateTimeImmutable($survey['starts_at']) : null;
            $endsAt = $survey['ends_at'] ? new DateTimeImmutable($survey['ends_at']) : null;

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
                'starts_at' => DateTimeHelper::formatTokyo($survey['starts_at'] ? new DateTimeImmutable($survey['starts_at']) : null),
                'ends_at' => DateTimeHelper::formatTokyo($survey['ends_at'] ? new DateTimeImmutable($survey['ends_at']) : null),
            ],
        ];
    }
}
