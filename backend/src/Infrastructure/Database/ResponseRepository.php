<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Infrastructure\Support\DateTimeHelper;
use Illuminate\Database\ConnectionInterface;
use stdClass;

class ResponseRepository
{
    private const TABLE = 'responses';

    public function __construct(
        private ConnectionInterface $db
    ) {
    }

    public function findById(int $id): ?array
    {
        $sql = sprintf('SELECT * FROM %s WHERE id = ? LIMIT 1', self::TABLE);
        $result = $this->db->selectOne($sql, [$id]);

        if (!$result) {
            return null;
        }

        return $this->mapToArray($result);
    }

    public function findByIdWithRespondent(int $id): ?array
    {
        $sql = sprintf(
            'SELECT
                r.*,
                res.name as respondent_name,
                res.email as respondent_email,
                res.line_display_name as respondent_line_display_name,
                res.honorific as respondent_honorific,
                res.is_manually_entered as respondent_is_manually_entered,
                res.respondent_master_id as respondent_master_id
             FROM %s r
             JOIN respondents res ON r.respondent_id = res.id
             WHERE r.id = ? LIMIT 1',
            self::TABLE
        );

        $result = $this->db->selectOne($sql, [$id]);

        if (!$result) {
            return null;
        }

        return $this->mapToArray($result);
    }

    public function findBy(array $criteria): array
    {
        $where = [];
        $bindings = [];
        foreach ($criteria as $column => $value) {
            $where[] = sprintf('%s = ?', $column);
            $bindings[] = $value;
        }

        $sql = sprintf('SELECT * FROM %s', self::TABLE);
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $results = $this->db->select($sql, $bindings);

        return array_map([$this, 'mapToArray'], $results);
    }

    public function countBySurveyId(int $surveyId): int
    {
        $sql = sprintf('SELECT COUNT(*) as count FROM %s WHERE survey_id = ?', self::TABLE);
        $result = $this->db->selectOne($sql, [$surveyId]);

        return (int)($result->count ?? 0);
    }

    /**
     * @return array[]
     */
    public function findBySurveyIdWithRespondent(int $surveyId): array
    {
        $sql = sprintf(
            'SELECT
                r.*,
                res.name as respondent_name,
                res.email as respondent_email,
                res.line_display_name as respondent_line_display_name,
                res.honorific as respondent_honorific,
                res.is_manually_entered as respondent_is_manually_entered,
                res.respondent_master_id as respondent_master_id
             FROM %s r
             JOIN respondents res ON r.respondent_id = res.id
             WHERE r.survey_id = ?
             ORDER BY r.submitted_at DESC, r.id DESC',
            self::TABLE
        );

        $results = $this->db->select($sql, [$surveyId]);

        return array_map([$this, 'mapToArray'], $results);
    }

    /**
     * @return array[]
     */
    public function findHistoryByRespondentId(int $respondentId): array
    {
        $sql = sprintf(
            'SELECT
                r.submitted_at,
                r.updated_at,
                r.edit_token,
                s.public_id as survey_public_id,
                s.title as survey_title
             FROM %s r
             LEFT JOIN surveys s ON r.survey_id = s.id
             WHERE r.respondent_id = ?
             ORDER BY r.submitted_at DESC, r.id DESC',
            self::TABLE
        );

        $results = $this->db->select($sql, [$respondentId]);

        return array_map(fn($item) => (array)$item, $results);
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

    public function update(int $id, array $data): bool
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
        $bindings[] = $id;

        $sql = sprintf(
            'UPDATE %s SET %s WHERE id = ?',
            self::TABLE,
            implode(', ', $sets)
        );

        $affected = $this->db->update($sql, $bindings);

        return $affected > 0;
    }

    private function encodeJsonColumns(array $data): array
    {
        $jsonColumns = ['answer_json', 'survey_snapshot_json'];
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
        $jsonColumns = ['answer_json', 'survey_snapshot_json'];
        foreach ($jsonColumns as $column) {
            if (isset($array[$column]) && is_string($array[$column])) {
                $array[$column] = json_decode($array[$column], true);
            }
        }
        return $array;
    }
}
