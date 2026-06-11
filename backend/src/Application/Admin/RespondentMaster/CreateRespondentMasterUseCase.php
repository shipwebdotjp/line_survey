<?php

declare(strict_types=1);

namespace App\Application\Admin\RespondentMaster;

use App\Infrastructure\Database\RespondentMasterRepository;
use Exception;

final class CreateRespondentMasterUseCase
{
    public function __construct(
        private RespondentMasterRepository $respondentMasterRepository
    ) {
    }

    /**
     * @param array $data
     * @return int
     * @throws Exception
     */
    public function execute(array $data): int
    {
        $errors = [];

        // Uniqueness check for master_code
        $existingByCode = $this->respondentMasterRepository->findBy(['master_code' => $data['master_code']]);
        if (!empty($existingByCode)) {
            $errors['master_code'] = 'このマスターコードは既に使用されています。';
        }

        // Uniqueness check for line_display_name
        $existingByName = $this->respondentMasterRepository->findBy(['line_display_name' => $data['line_display_name']]);
        if (!empty($existingByName)) {
            $errors['line_display_name'] = 'このLINE表示名は既に使用されています。';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Normalization
        $data['honorific'] = !empty($data['honorific']) ? $data['honorific'] : null;
        $data['note'] = !empty($data['note']) ? $data['note'] : null;

        return $this->respondentMasterRepository->save($data);
    }
}
