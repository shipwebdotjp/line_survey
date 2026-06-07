<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Infrastructure\Support\DateTimeHelper;
use Illuminate\Database\ConnectionInterface;
use stdClass;

class RespondentMasterRepository
{
    private const TABLE = 'respondent_masters';

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

        return (array)$result;
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

        return array_map(fn($item) => (array)$item, $results);
    }

    public function save(array $data): int
    {
        $now = DateTimeHelper::nowTokyo()->format('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

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
}
