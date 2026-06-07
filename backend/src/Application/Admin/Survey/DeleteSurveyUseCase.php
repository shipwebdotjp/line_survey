<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;

final class DeleteSurveyUseCase
{
    public function __construct(
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository
    ) {
    }

    /**
     * @param int $id
     * @return bool
     * @throws \RuntimeException if survey has responses
     */
    public function execute(int $id): bool
    {
        $responseCount = $this->responseRepository->countBySurveyId($id);
        if ($responseCount > 0) {
            throw new \RuntimeException('Cannot delete survey with responses', 409);
        }

        return $this->surveyRepository->delete($id);
    }
}
