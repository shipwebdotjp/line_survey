<?php

declare(strict_types=1);

namespace App\Application\Admin\Respondent;

use App\Infrastructure\Database\RespondentRepository;

class ListRespondentsUseCase
{
    public function __construct(
        private RespondentRepository $respondentRepository
    ) {
    }

    public function execute(int $ownerUserId): array
    {
        return $this->respondentRepository->findAllWithSummary($ownerUserId);
    }
}
