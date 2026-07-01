<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Infrastructure\Support\DateTimeHelper;
use Illuminate\Database\ConnectionInterface;

class ResponseDraftRepository
{
    private const TABLE = 'response_drafts';

    private const ALLOWED_COLUMNS = [
        'survey_id',
        'respondent_id',
        'answer_json',
        'created_at',
        'updated_at',
    ];

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

    public function findById(int $id, ?int $ownerUserId = null): ?array
    {
        $where = ['rd.id = ?'];
        $bindings = [$id];

        if ($ownerUserId !== null) {
            $where[] = 's.owner_user_id = ?';
            $bindings[] = $ownerUserId;
        }

        $sql = sprintf(
            'SELECT
                rd.*,
                s.title as survey_title,
                s.public_id as survey_public_id,
                s.questions_json as survey_questions_json,
                res.name as respondent_name,
                res.email as respondent_email
             FROM %s rd
             JOIN surveys s ON rd.survey_id = s.id
             JOIN respondents res ON rd.respondent_id = res.id
             WHERE %s LIMIT 1',
            self::TABLE,
            implode(' AND ', $where)
        );

        $result = $this->db->selectOne($sql, $bindings);

        if (!$result) {
            return null;
        }

        return $this->mapWithSurveyQuestions($result);
    }

    public function findAll(?int $ownerUserId = null): array
    {
        $where = [];
        $bindings = [];

        if ($ownerUserId !== null) {
            $where[] = 's.owner_user_id = ?';
            $bindings[] = $ownerUserId;
        }

        $sql = sprintf(
            'SELECT
                rd.*,
                s.title as survey_title,
                s.public_id as survey_public_id,
                res.name as respondent_name,
                res.email as respondent_email
             FROM %s rd
             JOIN surveys s ON rd.survey_id = s.id
             JOIN respondents res ON rd.respondent_id = res.id
             %s
             ORDER BY rd.updated_at DESC, rd.id DESC',
            self::TABLE,
            !empty($where) ? 'WHERE ' . implode(' AND ', $where) : ''
        );

        $results = $this->db->select($sql, $bindings);

        return array_map([$this, 'mapToArray'], $results);
    }

    public function save(array $data): int
    {
        $now = DateTimeHelper::nowTokyo()->format('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

        $data = $this->encodeJsonColumns($data);

        $filteredData = array_intersect_key($data, array_flip(self::ALLOWED_COLUMNS));
        $columns = array_keys($filteredData);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::TABLE,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->db->insert($sql, array_values($filteredData));

        return (int)$this->db->getPdo()->lastInsertId();
    }

    public function upsertBySurveyAndRespondent(array $data): void
    {
        $now = DateTimeHelper::nowTokyo()->format('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

        $data = $this->encodeJsonColumns($data);

        $filteredData = array_intersect_key($data, array_flip(self::ALLOWED_COLUMNS));
        $columns = array_keys($filteredData);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE answer_json = VALUES(answer_json), updated_at = VALUES(updated_at)',
            self::TABLE,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->db->insert($sql, array_values($filteredData));
    }

    public function updateBySurveyAndRespondent(int $surveyId, int $respondentId, array $data): bool
    {
        $now = DateTimeHelper::nowTokyo()->format('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? $now;

        $data = $this->encodeJsonColumns($data);

        $filteredData = array_intersect_key($data, array_flip(self::ALLOWED_COLUMNS));
        unset($filteredData['survey_id'], $filteredData['respondent_id']);

        $sets = [];
        $bindings = [];
        foreach ($filteredData as $column => $value) {
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

    public function deleteExpiredBefore(\DateTimeInterface $before, ?int $ownerUserId = null): int
    {
        if ($ownerUserId !== null) {
            // Using a subquery or join for DELETE with owner_user_id filter
            $sql = sprintf(
                'DELETE FROM %s WHERE updated_at < ? AND survey_id IN (SELECT id FROM surveys WHERE owner_user_id = ?)',
                self::TABLE
            );
            return $this->db->delete($sql, [DateTimeHelper::formatTokyo($before), $ownerUserId]);
        }

        $sql = sprintf('DELETE FROM %s WHERE updated_at < ?', self::TABLE);
        return $this->db->delete($sql, [DateTimeHelper::formatTokyo($before)]);
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

    private function mapWithSurveyQuestions(object|array $result): array
    {
        $array = $this->mapToArray($result);
        if (isset($array['survey_questions_json']) && is_string($array['survey_questions_json'])) {
            $array['survey_questions_json'] = json_decode($array['survey_questions_json'], true);
        }
        return $array;
    }
}
