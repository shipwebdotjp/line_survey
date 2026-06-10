<?php

declare(strict_types=1);

namespace App\Application\Admin\Respondent;

use App\Infrastructure\Database\RespondentRepository;

class UpdateRespondentUseCase
{
    public function __construct(
        private RespondentRepository $respondentRepository
    ) {
    }

    public function execute(int $id, array $data): bool
    {
        $respondent = $this->respondentRepository->findById($id);
        if (!$respondent) {
            return false;
        }

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'honorific' => (isset($data['honorific']) && $data['honorific'] !== '') ? $data['honorific'] : null,
        ];

        return $this->respondentRepository->update($id, $updateData);
    }
}
