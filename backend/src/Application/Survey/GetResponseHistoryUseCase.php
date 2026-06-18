<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;

final class GetResponseHistoryUseCase
{
    use SurveyResolutionTrait;

    public function __construct(
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository
    ) {
    }

    /**
     * @param array $respondent
     * @param string|null $surveyPublicId
     * @return array
     */
    public function execute(array $respondent, ?string $surveyPublicId = null): array
    {
        $respondent = $this->resolveRespondent($respondent);

        $surveyId = null;
        if ($surveyPublicId) {
            $survey = $this->resolveSurveyByPublicId($surveyPublicId);
            $surveyId = (int)$survey['id'];
        }

        return $this->responseRepository->findHistoryByRespondentId((int)$respondent['id'], $surveyId);
    }
}
