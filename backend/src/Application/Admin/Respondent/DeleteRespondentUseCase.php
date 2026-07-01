<?php

declare(strict_types=1);

namespace App\Application\Admin\Respondent;

use App\Infrastructure\Database\RespondentRepository;
use App\Infrastructure\Database\ResponseRepository;
use Illuminate\Database\ConnectionInterface;
use Exception;

class DeleteRespondentUseCase
{
    public function __construct(
        private ConnectionInterface $db,
        private RespondentRepository $respondentRepository,
        private ResponseRepository $responseRepository
    ) {
    }

    public function execute(int $id, int $ownerUserId): bool
    {
        $respondent = $this->respondentRepository->findById($id, $ownerUserId);
        if (!$respondent) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            // Delete associated responses first
            $this->responseRepository->deleteByRespondentId($id);

            // Delete respondent
            $success = $this->respondentRepository->delete($id, $ownerUserId);

            if (!$success) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
