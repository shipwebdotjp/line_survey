<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\SurveyRepository;

final class GetSurveyUseCase
{
    public function __construct(
        private SurveyRepository $surveyRepository
    ) {
    }

    public function execute(int $id, int $ownerUserId): ?array
    {
        return $this->surveyRepository->findByIdWithResponseCount($id, $ownerUserId);
    }
}
