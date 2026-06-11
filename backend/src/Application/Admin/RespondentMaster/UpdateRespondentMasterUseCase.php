<?php

declare(strict_types=1);

namespace App\Application\Admin\RespondentMaster;

use App\Infrastructure\Database\RespondentMasterRepository;
use Exception;

final class UpdateRespondentMasterUseCase
{
    public function __construct(
        private RespondentMasterRepository $respondentMasterRepository
    ) {
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function execute(int $id, array $data): bool
    {
        $current = $this->respondentMasterRepository->findById($id);
        if (!$current) {
            return false;
        }

        $errors = [];

        // Uniqueness check for master_code
        $existingByCode = $this->respondentMasterRepository->findBy(['master_code' => $data['master_code']]);
        foreach ($existingByCode as $m) {
            if ($m['id'] !== $id) {
                $errors['master_code'] = 'このマスターコードは既に使用されています。';
                break;
            }
        }

        // Uniqueness check for line_display_name
        $existingByName = $this->respondentMasterRepository->findBy(['line_display_name' => $data['line_display_name']]);
        foreach ($existingByName as $m) {
            if ($m['id'] !== $id) {
                $errors['line_display_name'] = 'このLINE表示名は既に使用されています。';
                break;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Normalization
        $data['honorific'] = !empty($data['honorific']) ? $data['honorific'] : null;
        $data['note'] = !empty($data['note']) ? $data['note'] : null;

        return $this->respondentMasterRepository->update($id, $data);
    }
}
