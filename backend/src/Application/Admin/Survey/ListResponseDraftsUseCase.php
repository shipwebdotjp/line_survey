<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\ResponseDraftRepository;

final class ListResponseDraftsUseCase
{
    public function __construct(
        private ResponseDraftRepository $responseDraftRepository
    ) {
    }

    public function execute(): array
    {
        return $this->responseDraftRepository->findAll();
    }
}
