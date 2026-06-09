<?php

declare(strict_types=1);

namespace App\Application\Survey;

use RuntimeException;

trait SurveyResolutionTrait
{
    /**
     * @param array $respondent
     * @return array
     * @throws RuntimeException
     */
    protected function resolveRespondent(array $respondent): array
    {
        if (empty($respondent)) {
            throw new RuntimeException('Respondent not found', 404);
        }

        return $respondent;
    }

    /**
     * @param string $publicId
     * @return array
     * @throws RuntimeException
     */
    protected function resolveSurveyByPublicId(string $publicId): array
    {
        $survey = $this->surveyRepository->findByPublicId($publicId);
        if (!$survey) {
            throw new RuntimeException('Survey not found', 404);
        }

        return $survey;
    }
}
