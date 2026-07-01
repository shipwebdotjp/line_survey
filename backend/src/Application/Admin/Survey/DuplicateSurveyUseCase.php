<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Support\IdGenerator;

class DuplicateSurveyUseCase
{
    public function __construct(
        private SurveyRepository $surveyRepository
    ) {
    }

    public function execute(int $id, int $ownerUserId): int
    {
        $sourceSurvey = $this->surveyRepository->findById($id, $ownerUserId);

        if (!$sourceSurvey) {
            throw new \RuntimeException('Survey not found', 404);
        }

        $title = trim($sourceSurvey['title'] ?? '');
        if ($title === '') {
            $title = 'Untitled Survey';
        }

        $newData = [
            'title' => $title,
            'description' => $sourceSurvey['description'],
            'questions_json' => $sourceSurvey['questions_json'],
            'status' => 'draft',
            'allow_multiple' => (bool)$sourceSurvey['allow_multiple'],
            'allow_edit' => (bool)$sourceSurvey['allow_edit'],
            'send_confirmation_email' => (bool)$sourceSurvey['send_confirmation_email'],
            'include_answers_in_email' => (bool)$sourceSurvey['include_answers_in_email'],
            'public_id' => IdGenerator::generatePublicId(),
            'owner_user_id' => $ownerUserId,
        ];

        return $this->surveyRepository->save($newData);
    }
}
