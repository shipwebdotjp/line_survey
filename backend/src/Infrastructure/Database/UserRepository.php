<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Infrastructure\Support\DateTimeHelper;
use Illuminate\Database\ConnectionInterface;

class UserRepository
{
    private const TABLE = 'users';

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

    public function findByLineUserId(string $lineUserId): ?array
    {
        $sql = sprintf('SELECT * FROM %s WHERE line_user_id = ? LIMIT 1', self::TABLE);
        $result = $this->db->selectOne($sql, [$lineUserId]);

        if (!$result) {
            return null;
        }

        return (array)$result;
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
