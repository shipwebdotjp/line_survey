<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\SurveyRepository;

final class UpdateSurveyUseCase
{
    public function __construct(
        private SurveyRepository $surveyRepository
    ) {
    }

    public function execute(int $id, array $data, int $ownerUserId): bool
    {
        // Check if survey exists and belongs to the owner
        $survey = $this->surveyRepository->findById($id, $ownerUserId);
        if (!$survey) {
            throw new \RuntimeException('Survey not found', 404);
        }

        // public_id and owner_user_id should not be updated
        unset($data['public_id'], $data['owner_user_id']);

        return $this->surveyRepository->update($id, $data, $ownerUserId);
    }
}
