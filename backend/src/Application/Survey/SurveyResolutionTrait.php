<?php

declare(strict_types=1);

namespace App\Application\Survey;

use RuntimeException;

trait SurveyResolutionTrait
{
    /**
     * @param string $idToken
     * @return array
     * @throws RuntimeException
     */
    protected function resolveRespondentFromToken(string $idToken): array
    {
        $claims = $this->idTokenVerifier->verify($idToken);
        $lineUserId = $claims['sub'];

        $respondents = $this->respondentRepository->findBy(['line_user_id' => $lineUserId]);
        if (empty($respondents)) {
            throw new RuntimeException('Respondent not found', 404);
        }

        return $respondents[0];
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
