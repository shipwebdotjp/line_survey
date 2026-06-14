<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\ResponseDraftRepository;

final class GetResponseDraftAdminUseCase
{
    public function __construct(
        private ResponseDraftRepository $responseDraftRepository
    ) {
    }

    public function execute(int $id): ?array
    {
        return $this->responseDraftRepository->findById($id);
    }
}
