<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\RespondentRepository;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Line\IdTokenVerifier;
use RuntimeException;

final class GetEditResponseUseCase
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
     * @param string $editToken
     * @param string $idToken
     * @return array
     * @throws RuntimeException
     */
    public function execute(string $publicId, string $editToken, string $idToken): array
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

        // 4. Find and validate response
        $responses = $this->responseRepository->findBy(['edit_token' => $editToken]);
        if (empty($responses)) {
            throw new RuntimeException('Response not found', 404);
        }
        $response = $responses[0];

        if ((int)$response['respondent_id'] !== (int)$respondent['id']) {
            throw new RuntimeException('Unauthorized to edit this response', 403);
        }

        if ((int)$response['survey_id'] !== (int)$survey['id']) {
            throw new RuntimeException('Response does not belong to this survey', 400);
        }

        return $response;
    }
}
