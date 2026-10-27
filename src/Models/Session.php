<?php

namespace App\Models;

use App\Core\Model;
use App\Config\Database;

class Session extends Model
{
    protected static string $table = 'sessions';
    protected static array $fillable = ['id', 'user_id', 'token', 'user_agent', 'ip_address', 'expires_at'];
    public static function findValid(string $token): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM sessions WHERE token=? AND expires_at>NOW() LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }
    public static function deleteByToken(string $token): bool
    {
        return Database::pdo()->prepare('DELETE FROM sessions WHERE token=?')->execute([$token]);
    }
}
