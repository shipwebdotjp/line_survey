<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\SurveyRepository;

final class ListSurveysUseCase
{
    public function __construct(
        private SurveyRepository $surveyRepository
    ) {
    }

    public function execute(): array
    {
        return $this->surveyRepository->findAllWithResponseCount();
    }
}
