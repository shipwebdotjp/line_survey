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
    use SurveyResolutionTrait;

    public function __construct(
        private RespondentRepository $respondentRepository,
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository
    ) {
    }

    /**
     * @param string $publicId
     * @param array $respondent
     * @return array
     * @throws RuntimeException
     */
    public function execute(string $publicId, array $respondent): array
    {
        $respondent = $this->resolveRespondent($respondent);
        $survey = $this->resolveSurveyByPublicId($publicId);

        $responses = $this->responseRepository->findBy([
            'survey_id' => $survey['id'],
            'respondent_id' => $respondent['id']
        ]);

        if (empty($responses)) {
            throw new RuntimeException('Response not found', 404);
        }

        usort($responses, fn($a, $b) => strcmp($b['submitted_at'], $a['submitted_at']) ?: ($b['id'] <=> $a['id']));

        return $responses[0];
    }
}
