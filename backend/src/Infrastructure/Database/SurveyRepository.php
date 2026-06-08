<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Infrastructure\Support\DateTimeHelper;
use Illuminate\Database\ConnectionInterface;
use stdClass;

class SurveyRepository
{
    private const TABLE = 'surveys';

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

    public function findByPublicId(string $publicId): ?array
    {
        $sql = sprintf('SELECT * FROM %s WHERE public_id = ? LIMIT 1', self::TABLE);
        $result = $this->db->selectOne($sql, [$publicId]);

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

    public function findAllWithResponseCount(): array
    {
        $sql = sprintf(
            'SELECT
                s.id,
                s.public_id,
                s.title,
                s.description,
                s.questions_json,
                s.status,
                s.allow_multiple,
                s.allow_edit,
                s.starts_at,
                s.ends_at,
                s.created_at,
                s.updated_at,
                COALESCE(rc.response_count, 0) as response_count
             FROM %s s
             LEFT JOIN (
                SELECT survey_id, COUNT(*) as response_count
                FROM responses
                GROUP BY survey_id
             ) rc ON s.id = rc.survey_id
             ORDER BY s.created_at DESC',
            self::TABLE
        );

        $results = $this->db->select($sql);

        return array_map([$this, 'mapToArray'], $results);
    }

    public function findByIdWithResponseCount(int $id): ?array
    {
        $sql = sprintf(
            'SELECT
                s.id,
                s.public_id,
                s.title,
                s.description,
                s.questions_json,
                s.status,
                s.allow_multiple,
                s.allow_edit,
                s.starts_at,
                s.ends_at,
                s.created_at,
                s.updated_at,
                COALESCE(rc.response_count, 0) as response_count
             FROM %s s
             LEFT JOIN (
                SELECT survey_id, COUNT(*) as response_count
                FROM responses
                GROUP BY survey_id
             ) rc ON s.id = rc.survey_id
             WHERE s.id = ?
             LIMIT 1',
            self::TABLE
        );

        $result = $this->db->selectOne($sql, [$id]);

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

        if (isset($data['questions_json']) && !is_string($data['questions_json'])) {
            $data['questions_json'] = json_encode($data['questions_json'], JSON_UNESCAPED_UNICODE);
        }

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

        if (isset($data['questions_json']) && !is_string($data['questions_json'])) {
            $data['questions_json'] = json_encode($data['questions_json'], JSON_UNESCAPED_UNICODE);
        }

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

    public function delete(int $id): bool
    {
        $sql = sprintf('DELETE FROM %s WHERE id = ?', self::TABLE);
        $affected = $this->db->delete($sql, [$id]);

        return $affected > 0;
    }

    private function mapToArray(object|array $result): array
    {
        $array = (array)$result;
        if (isset($array['questions_json']) && is_string($array['questions_json'])) {
            $array['questions_json'] = json_decode($array['questions_json'], true);
        }
        return $array;
    }
}
