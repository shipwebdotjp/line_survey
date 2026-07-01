<?php

declare(strict_types=1);

namespace App\Application\Admin\Respondent;

use App\Infrastructure\Database\RespondentRepository;
use App\Infrastructure\Database\ResponseRepository;

class GetRespondentUseCase
{
    public function __construct(
        private RespondentRepository $respondentRepository,
        private ResponseRepository $responseRepository
    ) {
    }

    public function execute(int $id, int $ownerUserId): ?array
    {
        $respondent = $this->respondentRepository->findById($id, $ownerUserId);
        if (!$respondent) {
            return null;
        }

        $respondent['responses'] = $this->responseRepository->findHistoryForAdmin($id);

        return $respondent;
    }
}
