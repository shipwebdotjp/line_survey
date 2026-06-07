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

    public function execute(int $id, array $data): bool
    {
        // Check if survey exists
        $survey = $this->surveyRepository->findById($id);
        if (!$survey) {
            throw new \RuntimeException('Survey not found', 404);
        }

        // public_id should not be updated
        unset($data['public_id']);

        return $this->surveyRepository->update($id, $data);
    }
}
