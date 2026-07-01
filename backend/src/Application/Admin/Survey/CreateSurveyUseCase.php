<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Support\IdGenerator;

final class CreateSurveyUseCase
{
    public function __construct(
        private SurveyRepository $surveyRepository
    ) {
    }

    public function execute(array $data, int $ownerUserId): int
    {
        $data['public_id'] = IdGenerator::generatePublicId();
        $data['owner_user_id'] = $ownerUserId;

        return $this->surveyRepository->save($data);
    }
}
