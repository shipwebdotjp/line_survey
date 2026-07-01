<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\ResponseDraftRepository;
use App\Infrastructure\Support\DateTimeHelper;

final class CleanupResponseDraftsUseCase
{
    public function __construct(
        private ResponseDraftRepository $responseDraftRepository
    ) {
    }

    public function execute(int $ownerUserId): int
    {
        $before = DateTimeHelper::nowTokyo()->modify('-30 days');
        return $this->responseDraftRepository->deleteExpiredBefore($before, $ownerUserId);
    }
}
