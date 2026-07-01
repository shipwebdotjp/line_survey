<?php

declare(strict_types=1);

namespace App\Application\Admin\RespondentMaster;

use App\Infrastructure\Database\RespondentMasterRepository;

final class DeleteRespondentMasterUseCase
{
    public function __construct(
        private RespondentMasterRepository $respondentMasterRepository
    ) {
    }

    public function execute(int $id, int $ownerUserId): bool
    {
        return $this->respondentMasterRepository->delete($id, $ownerUserId);
    }
}
