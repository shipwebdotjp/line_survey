<?php

declare(strict_types=1);

namespace App\Application\Admin\RespondentMaster;

use App\Infrastructure\Database\RespondentMasterRepository;

final class ImportRespondentMastersUseCase
{
    public function __construct(
        private RespondentMasterRepository $respondentMasterRepository
    ) {
    }

    /**
     * @param string $csvContent
     * @return array{imported: int, errors: array}
     */
    public function execute(string $csvContent): array
    {
        // Strip BOM if present
        if (str_starts_with($csvContent, "\xEF\xBB\xBF")) {
            $csvContent = substr($csvContent, 3);
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $header = null;
        $imported = 0;
        $errors = [];

        // Pre-fetch all existing data to check for line_display_name uniqueness across the batch
        $allMasters = $this->respondentMasterRepository->findBy([]);
        $masterCodeMap = [];
        $lineDisplayNameMap = [];

        foreach ($allMasters as $m) {
            $masterCodeMap[$m['master_code']] = $m;
            $lineDisplayNameMap[$m['line_display_name']] = $m['master_code'];
        }

        $rowIndex = 0;
        while (($data = fgetcsv($stream)) !== false) {
            $rowIndex++;

            // Skip empty lines (fgetcsv returns [null] for empty lines in some configurations, or [false] which is handled by while)
            if (empty($data) || (count($data) === 1 && $data[0] === null)) {
                continue;
            }

            if ($header === null) {
                $header = $data;
                // Basic header validation
                // Expected: master_code,line_display_name,name,honorific,email,note
                if (count($data) < 6) {
                    $errors[] = ['row' => $rowIndex, 'reason' => 'Invalid header format. Expected at least 6 columns.'];
                    fclose($stream);
                    return ['imported' => 0, 'errors' => $errors];
                }
                continue;
            }

            if (count($data) < 6) {
                $errors[] = ['row' => $rowIndex, 'reason' => 'Insufficient columns.'];
                continue;
            }

            $record = [
                'master_code' => trim((string)$data[0]),
                'line_display_name' => trim((string)$data[1]),
                'name' => trim((string)$data[2]),
                'honorific' => trim((string)$data[3]),
                'email' => trim((string)$data[4]),
                'note' => trim((string)$data[5]),
            ];

            // Validation
            if ($record['master_code'] === '') {
                $errors[] = ['row' => $rowIndex, 'reason' => 'master_code is required.'];
                continue;
            }
            if ($record['line_display_name'] === '') {
                $errors[] = ['row' => $rowIndex, 'reason' => 'line_display_name is required.'];
                continue;
            }
            if ($record['name'] === '') {
                $errors[] = ['row' => $rowIndex, 'reason' => 'name is required.'];
                continue;
            }
            if ($record['email'] === '') {
                $errors[] = ['row' => $rowIndex, 'reason' => 'email is required.'];
                continue;
            }
            if (!filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['row' => $rowIndex, 'reason' => 'Invalid email format.'];
                continue;
            }

            // Uniqueness check for line_display_name
            if (isset($lineDisplayNameMap[$record['line_display_name']]) && $lineDisplayNameMap[$record['line_display_name']] !== $record['master_code']) {
                $errors[] = ['row' => $rowIndex, 'reason' => sprintf('line_display_name "%s" is already used by another master_code.', $record['line_display_name'])];
                continue;
            }

            // Upsert
            try {
                $existing = $masterCodeMap[$record['master_code']] ?? null;
                if ($existing) {
                    $this->respondentMasterRepository->update((int)$existing['id'], $record);
                } else {
                    $this->respondentMasterRepository->save($record);
                }

                // Update maps for subsequent rows in the same CSV
                $masterCodeMap[$record['master_code']] = $record;
                $lineDisplayNameMap[$record['line_display_name']] = $record['master_code'];

                $imported++;
            } catch (\Exception $e) {
                $errors[] = ['row' => $rowIndex, 'reason' => 'Database error: ' . $e->getMessage()];
            }
        }

        fclose($stream);

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }
}
