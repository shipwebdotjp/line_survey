<?php

declare(strict_types=1);

namespace App\Application\Respondent;

use App\Infrastructure\Database\RespondentMasterRepository;
use App\Infrastructure\Database\RespondentRepository;

final class IdentifyService
{
    public const STATUS_EXISTING = 'existing';
    public const STATUS_MATCHED = 'matched';
    public const STATUS_MANUAL_REQUIRED = 'manual_required';
    public const STATUS_MANUAL_SAVED = 'manual_saved';

    public function __construct(
        private RespondentRepository $respondentRepository,
        private RespondentMasterRepository $masterRepository
    ) {
    }

    /**
     * Identify a respondent based on LINE user ID and display name.
     *
     * @param string $lineUserId
     * @param string $lineDisplayName
     * @param int $ownerUserId
     * @return array{status: string, respondent: array|null}
     */
    public function identify(string $lineUserId, string $lineDisplayName, int $ownerUserId): array
    {
        // 1. Check existing respondent by line_user_id and owner_user_id
        $existing = $this->respondentRepository->findBy([
            'line_user_id' => $lineUserId,
            'owner_user_id' => $ownerUserId,
        ]);
        if (!empty($existing)) {
            $respondent = $existing[0];
            // Update line_display_name to the latest value
            $this->respondentRepository->update((int)$respondent['id'], [
                'line_display_name' => $lineDisplayName
            ]);

            return [
                'status' => self::STATUS_EXISTING,
                'respondent' => $this->respondentRepository->findById((int)$respondent['id'])
            ];
        }

        // 2. Check respondent_masters by line_display_name (exact match) and owner_user_id
        $masters = $this->masterRepository->findBy([
            'line_display_name' => $lineDisplayName,
            'owner_user_id' => $ownerUserId,
        ]);
        if (!empty($masters)) {
            $master = $masters[0];

            if (empty($master['name']) || empty($master['email'])) {
                throw new \InvalidArgumentException('Master record is missing required fields (name or email).');
            }

            $newRespondentId = $this->respondentRepository->save([
                'owner_user_id' => $ownerUserId,
                'line_user_id' => $lineUserId,
                'line_display_name' => $lineDisplayName,
                'respondent_master_id' => $master['id'],
                'name' => $master['name'],
                'email' => $master['email'],
                'honorific' => $master['honorific'],
                'is_manually_entered' => false,
            ]);

            return [
                'status' => self::STATUS_MATCHED,
                'respondent' => $this->respondentRepository->findById($newRespondentId)
            ];
        }

        // 3. Manual entry required
        return [
            'status' => self::STATUS_MANUAL_REQUIRED,
            'respondent' => null
        ];
    }

    /**
     * Save manual entry for a respondent.
     *
     * @param string $lineUserId
     * @param string $lineDisplayName
     * @param array $data {name: string, email: string, honorific: string}
     * @param int $ownerUserId
     * @return array
     */
    public function saveManual(string $lineUserId, string $lineDisplayName, array $data, int $ownerUserId): array
    {
        // Check if already exists (to be safe/idempotent)
        $existing = $this->respondentRepository->findBy([
            'line_user_id' => $lineUserId,
            'owner_user_id' => $ownerUserId,
        ]);
        if (!empty($existing)) {
            $respondent = $existing[0];
            $updateData = [
                'line_display_name' => $lineDisplayName,
                'name' => $data['name'],
                'email' => $data['email'],
                'honorific' => $data['honorific'] ?? null,
                'is_manually_entered' => true,
            ];
            $this->respondentRepository->update((int)$respondent['id'], $updateData);
            return $this->respondentRepository->findById((int)$respondent['id']);
        }

        $newId = $this->respondentRepository->save([
            'owner_user_id' => $ownerUserId,
            'line_user_id' => $lineUserId,
            'line_display_name' => $lineDisplayName,
            'respondent_master_id' => null,
            'name' => $data['name'],
            'email' => $data['email'],
            'honorific' => $data['honorific'] ?? null,
            'is_manually_entered' => true,
        ]);

        return $this->respondentRepository->findById($newId);
    }
}
