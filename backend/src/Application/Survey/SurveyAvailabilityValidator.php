<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Support\DateTimeHelper;
use RuntimeException;

final class SurveyAvailabilityValidator
{
    public function assertCanRespond(array $survey): void
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
