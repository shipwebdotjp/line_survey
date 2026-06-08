<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\RespondentRepository;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Line\IdTokenVerifier;
use RuntimeException;

final class GetCurrentResponseUseCase
{
    public function __construct(
        private IdTokenVerifier $idTokenVerifier,
        private RespondentRepository $respondentRepository,
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository
    ) {
    }

    /**
     * @param string $publicId
     * @param string $idToken
     * @return array
     * @throws RuntimeException
     */
    public function execute(string $publicId, string $idToken): array
    {
        // 1. Verify ID Token
        $claims = $this->idTokenVerifier->verify($idToken);
        $lineUserId = $claims['sub'];

        // 2. Resolve respondent
        $respondents = $this->respondentRepository->findBy(['line_user_id' => $lineUserId]);
        if (empty($respondents)) {
            throw new RuntimeException('Respondent not found', 404);
        }
        $respondent = $respondents[0];

        // 3. Get survey
        $survey = $this->surveyRepository->findByPublicId($publicId);
        if (!$survey) {
            throw new RuntimeException('Survey not found', 404);
        }

        // 4. Find latest response
        // Using findBy and picking the first one (ResponseRepository::findBySurveyIdWithRespondent returns sorted by submitted_at DESC)
        $responses = $this->responseRepository->findBy([
            'survey_id' => $survey['id'],
            'respondent_id' => $respondent['id']
        ]);

        if (empty($responses)) {
            throw new RuntimeException('Response not found', 404);
        }

        // Picking the latest one. ResponseRepository::findBy doesn't guarantee order but usually it's by id.
        // Let's use findBySurveyIdWithRespondent which has ordering.
        // Wait, findBySurveyIdWithRespondent returns all for that survey, I only want for this respondent.

        // Let's sort manually if needed or add a better method to repository.
        usort($responses, fn($a, $b) => strcmp($b['submitted_at'], $a['submitted_at']) ?: ($b['id'] <=> $a['id']));

        return $responses[0];
    }
}
