<?php

declare(strict_types=1);

namespace App\Application\Admin\RespondentMaster;

use App\Infrastructure\Database\RespondentMasterRepository;

final class GetRespondentMasterUseCase
{
    public function __construct(
        private RespondentMasterRepository $respondentMasterRepository
    ) {
    }

    public function execute(int $id, int $ownerUserId): ?array
    {
        return $this->respondentMasterRepository->findById($id, $ownerUserId);
    }
}
