<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\RespondentRepository;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use RuntimeException;

final class GetEditResponseUseCase
{
    use SurveyResolutionTrait;

    public function __construct(
        private RespondentRepository $respondentRepository,
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository
    ) {
    }

    /**
     * @param string $publicId
     * @param string $editToken
     * @param array $respondent
     * @return array
     * @throws RuntimeException
     */
    public function execute(string $publicId, string $editToken, array $respondent): array
    {
        $respondent = $this->resolveRespondent($respondent);
        $survey = $this->resolveSurveyByPublicId($publicId);

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
