<?php

namespace App\Core;

use App\Config\Database;

abstract class Model
{
    protected static string $table = '';
    protected static array  $fillable = [];
    protected static string $primaryKey = 'id';

    public static function find(string $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function all(string $orderBy = 'created_date DESC', int $limit = 1000): array
    {
        return Database::pdo()
            ->query('SELECT * FROM `' . static::$table . '` ORDER BY ' . $orderBy . ' LIMIT ' . $limit)
            ->fetchAll();
    }

    public static function where(array $conditions, string $orderBy = 'created_date DESC', int $limit = 1000): array
    {
        $where = [];
        $params = [];
        foreach ($conditions as $col => $val) {
            if ($val === null) {
                $where[] = "`{$col}` IS NULL";
                continue;
            }
            $where[] = "`{$col}` = ?";
            $params[] = $val;
        }
        $sql = 'SELECT * FROM `' . static::$table . '`'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY ' . $orderBy . ' LIMIT ' . $limit;
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function first(array $conditions): ?array
    {
        $rows = static::where($conditions, 'created_date DESC', 1);
        return $rows[0] ?? null;
    }

    public static function create(array $data): array
    {
        $data = static::filter($data);
        if (empty($data[static::$primaryKey])) {
            $data[static::$primaryKey] = static::uuid();
        }
        $cols = array_keys($data);
        $ph = array_map(fn($c) => ':' . $c, $cols);
        $sql = 'INSERT INTO `' . static::$table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', $ph) . ')';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($data);
        return static::find($data[static::$primaryKey]);
    }

    public static function update(string $id, array $data): ?array
    {
        $data = static::filter($data);
        unset($data[static::$primaryKey]);
        if (!$data) return static::find($id);
        $set = [];
        foreach (array_keys($data) as $c) $set[] = "`{$c}` = :{$c}";
        $sql = 'UPDATE `' . static::$table . '` SET ' . implode(', ', $set)
            . ' WHERE `' . static::$primaryKey . '` = :__id';
        $data['__id'] = $id;
        Database::pdo()->prepare($sql)->execute($data);
        return static::find($id);
    }

    public static function delete(string $id): bool
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ?'
        );
        return $stmt->execute([$id]);
    }

    protected static function filter(array $data): array
    {
        if (!static::$fillable) return $data;
        return array_intersect_key($data, array_flip(static::$fillable));
    }

    public static function uuid(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}
