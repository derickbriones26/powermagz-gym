<?php

namespace App\Models;

use App\Core\Model;
use App\Config\Database;

class PasswordReset extends Model
{
    protected static string $table = 'password_resets';
    protected static array $fillable = ['id', 'user_id', 'token', 'expires_at', 'used'];

    /**
     * Issue a single-use reset token. Only the SHA-256 hash is persisted;
     * the raw token is returned once for the email link.
     */
    public static function issue(string $userId, int $ttlSeconds = 3600): string
    {
        $token = bin2hex(random_bytes(32));

        /* Any older unused link becomes invalid immediately. */
        Database::pdo()->prepare(
            'DELETE FROM `password_resets` WHERE `user_id` = ? AND `used` = 0'
        )->execute([$userId]);

        self::create([
            'user_id'    => $userId,
            'token'      => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + max(60, $ttlSeconds)),
            'used'       => 0,
        ]);

        return $token;
    }

    /** Look up an unused, unexpired reset row from the raw email token. */
    public static function findValidByRawToken(string $rawToken): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return null;
        }

        $row = self::first([
            'token' => hash('sha256', $rawToken),
            'used'  => 0,
        ]);

        if (!$row) return null;
        if (empty($row['expires_at']) || strtotime((string)$row['expires_at']) < time()) {
            return null;
        }

        return $row;
    }

    /** Mark a token used and remove every other outstanding token. */
    public static function consume(array $reset): void
    {
        self::update($reset['id'], ['used' => 1]);

        Database::pdo()->prepare(
            'DELETE FROM `password_resets` WHERE `user_id` = ? AND `used` = 0'
        )->execute([$reset['user_id']]);
    }
}
