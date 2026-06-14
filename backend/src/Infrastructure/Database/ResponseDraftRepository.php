<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Infrastructure\Support\DateTimeHelper;
use Illuminate\Database\ConnectionInterface;

class ResponseDraftRepository
{
    private const TABLE = 'response_drafts';

    public function __construct(
        private ConnectionInterface $db
    ) {
    }

    public function findBySurveyAndRespondent(int $surveyId, int $respondentId): ?array
    {
        $sql = sprintf('SELECT * FROM %s WHERE survey_id = ? AND respondent_id = ? LIMIT 1', self::TABLE);
        $result = $this->db->selectOne($sql, [$surveyId, $respondentId]);

        if (!$result) {
            return null;
        }

        return $this->mapToArray($result);
    }

    public function save(array $data): int
    {
        $now = DateTimeHelper::nowTokyo()->format('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

        $data = $this->encodeJsonColumns($data);

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::TABLE,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->db->insert($sql, array_values($data));

        return (int)$this->db->getPdo()->lastInsertId();
    }

    public function updateBySurveyAndRespondent(int $surveyId, int $respondentId, array $data): bool
    {
        $now = DateTimeHelper::nowTokyo()->format('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? $now;

        $data = $this->encodeJsonColumns($data);

        $sets = [];
        $bindings = [];
        foreach ($data as $column => $value) {
            $sets[] = sprintf('%s = ?', $column);
            $bindings[] = $value;
        }
        $bindings[] = $surveyId;
        $bindings[] = $respondentId;

        $sql = sprintf(
            'UPDATE %s SET %s WHERE survey_id = ? AND respondent_id = ?',
            self::TABLE,
            implode(', ', $sets)
        );

        $affected = $this->db->update($sql, $bindings);

        return $affected > 0;
    }

    public function deleteBySurveyAndRespondent(int $surveyId, int $respondentId): bool
    {
        $sql = sprintf('DELETE FROM %s WHERE survey_id = ? AND respondent_id = ?', self::TABLE);
        $affected = $this->db->delete($sql, [$surveyId, $respondentId]);
        return $affected > 0;
    }

    public function deleteExpiredBefore(\DateTimeInterface $before): int
    {
        $sql = sprintf('DELETE FROM %s WHERE updated_at < ?', self::TABLE);
        return $this->db->delete($sql, [$before->format('Y-m-d H:i:s')]);
    }

    private function encodeJsonColumns(array $data): array
    {
        $jsonColumns = ['answer_json'];
        foreach ($jsonColumns as $column) {
            if (isset($data[$column]) && !is_string($data[$column])) {
                $data[$column] = json_encode($data[$column], JSON_UNESCAPED_UNICODE);
            }
        }
        return $data;
    }

    private function mapToArray(object|array $result): array
    {
        $array = (array)$result;
        $jsonColumns = ['answer_json'];
        foreach ($jsonColumns as $column) {
            if (isset($array[$column]) && is_string($array[$column])) {
                $array[$column] = json_decode($array[$column], true);
            }
        }
        return $array;
    }
}
