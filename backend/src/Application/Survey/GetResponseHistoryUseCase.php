<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\ResponseRepository;

final class GetResponseHistoryUseCase
{
    use SurveyResolutionTrait;

    public function __construct(
        private ResponseRepository $responseRepository
    ) {
    }

    /**
     * @param array $respondent
     * @return array
     */
    public function execute(array $respondent): array
    {
        $respondent = $this->resolveRespondent($respondent);

        return $this->responseRepository->findHistoryByRespondentId((int)$respondent['id']);
    }
}
