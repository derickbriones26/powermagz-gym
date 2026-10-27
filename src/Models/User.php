<?php

namespace App\Models;

use App\Core\Model;
use App\Config\Database;

class User extends Model
{
    protected static string $table = 'users';

    protected static array $fillable = [
        'id',
        'email',
        'password_hash',
        'full_name',
        'role',
        'avatar_url',
        'email_verified',
        'otp_code',
        'otp_expires_at',
        'title',
        'gender',
        'gender_self_describe',
        'phone',
        'mfa_enabled',
        'mfa_secret',
        'mfa_recovery_codes',
        'mfa_verified_at',
        'last_login_at',
        'idle_timeout_minutes',
    ];

    /* =============================================================
     |  LOOKUPS
     ============================================================= */

    public static function findByEmail(string $email): ?array
    {
        return self::first(['email' => $email]);
    }

    /**
     * Enforce unique email (spec §1.6 — clear "Email already exists" error).
     * Pass $exceptId when editing.
     */
    public static function emailExists(string $email, ?string $exceptId = null): bool
    {
        $pdo    = Database::pdo();
        $sql    = 'SELECT COUNT(*) FROM `users` WHERE `email` = ?';
        $params = [$email];

        if ($exceptId !== null && $exceptId !== '') {
            $sql .= ' AND `id` <> ?';
            $params[] = $exceptId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Fetch a user with their active enrollment + 30-day attendance.
     * Returns null if no user found.
     */
    public static function findWithStats(string $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT
                u.*,
                e.id            AS active_enrollment_id,
                e.tier          AS active_tier,
                e.program_name  AS active_program_name,
                e.start_date    AS active_start_date,
                e.end_date      AS active_end_date,
                e.balance       AS active_balance,
                e.status        AS active_status,
                COALESCE(a.checkins_30d, 0) AS attendance_30d,
                COALESCE(a.total_hours,   0) AS total_hours
            FROM `users` u
            LEFT JOIN `enrollments` e
              ON e.`user_id` = u.`id`
             AND e.`status` IN ('active', 'pending')
            LEFT JOIN (
                SELECT
                    `user_id`,
                    COUNT(*)               AS checkins_30d,
                    COALESCE(SUM(`hours`), 0) AS total_hours
                FROM `attendance`
                WHERE `date` >= (CURDATE() - INTERVAL 30 DAY)
                GROUP BY `user_id`
            ) a ON a.`user_id` = u.`id`
            WHERE u.`id` = ?
            ORDER BY e.`end_date` DESC
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /* =============================================================
     |  LISTING  (spec §1.2 — Members tab)
     ============================================================= */

    /**
     * Rich listing used by the Members admin tab.
     * Applies role, status, tier, join-date-range, and text search filters
     * in SQL so pagination works even with thousands of rows.
     *
     * @param array{
     *   role?:string, status?:string, tier?:string,
     *   joined_from?:string, joined_to?:string,
     *   search?:string, limit?:int, offset?:int
     * } $filters
     */
    public static function listWithStats(array $filters = []): array
    {
        $pdo    = Database::pdo();
        $where  = [];
        $params = [];

        /* -------- Role filter -------- */
        $role = $filters['role'] ?? '';
        if ($role !== '' && $role !== 'all' && in_array($role, ['admin', 'staff', 'partner', 'user'], true)) {
            $where[]  = 'u.`role` = ?';
            $params[] = $role;
        }

        /* -------- Joined date range -------- */
        if (!empty($filters['joined_from'])) {
            $where[]  = 'u.`created_date` >= ?';
            $params[] = $filters['joined_from'] . ' 00:00:00';
        }
        if (!empty($filters['joined_to'])) {
            $where[]  = 'u.`created_date` <= ?';
            $params[] = $filters['joined_to'] . ' 23:59:59';
        }

        /* -------- Search (name OR email) -------- */
        if (!empty($filters['search'])) {
            $q = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']) . '%';
            $where[]  = '(u.`full_name` LIKE ? OR u.`email` LIKE ?)';
            $params[] = $q;
            $params[] = $q;
        }

        /* -------- Enrollment status / tier filters (post-join) -------- */
        $havingStatus = null;
        $havingTier   = null;

        $status = $filters['status'] ?? '';
        if ($status !== '' && $status !== 'all') {
            $havingStatus = $status;
        }
        $tier = $filters['tier'] ?? '';
        if ($tier !== '' && $tier !== 'all') {
            $havingTier = $tier;
        }

        $limit  = (int)($filters['limit']  ?? 500);
        $offset = (int)($filters['offset'] ?? 0);
        $limit  = max(1, min($limit, 1000));
        $offset = max(0, $offset);

        /* -------- Main query -------- */
        $sql = "
            SELECT
                u.`id`, u.`email`, u.`full_name`, u.`role`,
                u.`email_verified`, u.`created_date`, u.`last_login_at`,
                e.`id`           AS enrollment_id,
                e.`tier`         AS enrollment_tier,
                e.`status`       AS enrollment_status,
                e.`program_name` AS enrollment_program,
                e.`start_date`   AS enrollment_start,
                e.`end_date`     AS enrollment_end,
                e.`balance`      AS enrollment_balance,
                COALESCE(a.`checkins_30d`, 0) AS attendance_30d,
                COALESCE(p.`paid_total`,   0) AS paid_total
            FROM `users` u

            LEFT JOIN `enrollments` e
              ON e.`id` = (
                SELECT e2.`id` FROM `enrollments` e2
                WHERE e2.`user_id` = u.`id`
                ORDER BY
                  FIELD(e2.`status`, 'active', 'pending', 'paused', 'expired', 'cancelled'),
                  e2.`created_date` DESC
                LIMIT 1
              )

            LEFT JOIN (
                SELECT `user_id`, COUNT(*) AS checkins_30d
                FROM `attendance`
                WHERE `date` >= (CURDATE() - INTERVAL 30 DAY)
                GROUP BY `user_id`
            ) a ON a.`user_id` = u.`id`

            LEFT JOIN (
                SELECT `user_id`, SUM(`amount`) AS paid_total
                FROM `payments`
                WHERE `status` = 'paid'
                GROUP BY `user_id`
            ) p ON p.`user_id` = u.`id`
        ";

        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);

        /* Post-join filters */
        $haves = [];
        if ($havingStatus !== null) {
            $haves[]  = 'e.`status` = ?';
            $params[] = $havingStatus;
        }
        if ($havingTier !== null) {
            $haves[]  = 'e.`tier` = ?';
            $params[] = $havingTier;
        }
        if ($haves) {
            $sql .= ' HAVING ' . implode(' AND ', $haves);
        }

        $sql .= ' ORDER BY u.`created_date` DESC LIMIT ' . $limit . ' OFFSET ' . $offset;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('[User::listWithStats] ' . $e->getMessage());
            return [];
        }

        /* Add derived fields */
        $now = time();
        foreach ($rows as &$r) {
            $r['enrollment_days_left']  = self::daysLeft($r['enrollment_end'] ?? null, $now);
            $r['enrollment_urgency']    = self::urgency($r, $now);
        }
        unset($r);

        return $rows;
    }

    /**
     * Count users grouped by role. Feeds the pill badges.
     *
     * @return array<string,int> e.g. ['all'=>42,'user'=>30,'partner'=>10,'admin'=>1,'staff'=>1]
     */
    public static function roleCounts(): array
    {
        $counts = ['all' => 0, 'user' => 0, 'partner' => 0, 'admin' => 0, 'staff' => 0];

        try {
            foreach (
                Database::pdo()
                    ->query('SELECT `role`, COUNT(*) AS c FROM `users` GROUP BY `role`')
                    ->fetchAll() as $r
            ) {
                if (isset($counts[$r['role']])) $counts[$r['role']] = (int)$r['c'];
                $counts['all'] += (int)$r['c'];
            }
        } catch (\Throwable $e) {
            error_log('[User::roleCounts] ' . $e->getMessage());
        }

        return $counts;
    }

    /* =============================================================
     |  ATTENDANCE METRIC  (spec §1.2)
     ============================================================= */

    /**
     * Count a user's check-ins in the last N days.
     * Defaults to 30 per spec.
     */
    public static function attendance30d(string $userId, int $days = 30): int
    {
        try {
            $stmt = Database::pdo()->prepare("
                SELECT COUNT(*) FROM `attendance`
                WHERE `user_id` = ?
                  AND `date` >= (CURDATE() - INTERVAL ? DAY)
            ");
            $stmt->execute([$userId, max(1, $days)]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /* =============================================================
     |  MUTATIONS  (spec §1.2 — edit + delete from admin)
     ============================================================= */

    /**
     * Update a user with validation. Never touches password unless
     * $data['password'] is provided. Returns null on failure, updated
     * row on success.
     */
    public static function updateProfile(string $id, array $data): ?array
    {
        $existing = self::find($id);
        if (!$existing) return null;

        $payload = [];

        if (array_key_exists('full_name', $data)) {
            $payload['full_name'] = trim((string)$data['full_name']);
        }
        if (array_key_exists('email', $data)) {
            $payload['email'] = trim((string)$data['email']);
        }
        if (array_key_exists('role', $data) && in_array($data['role'], ['admin', 'staff', 'partner', 'user'], true)) {
            $payload['role'] = $data['role'];
        }
        if (array_key_exists('email_verified', $data)) {
            $payload['email_verified'] = $data['email_verified'] ? 1 : 0;
        }
        if (array_key_exists('title', $data)) {
            $payload['title'] = $data['title'];
        }
        if (array_key_exists('gender', $data)) {
            $payload['gender'] = $data['gender'];
        }
        if (array_key_exists('gender_self_describe', $data)) {
            $payload['gender_self_describe'] = $data['gender_self_describe'];
        }
        if (array_key_exists('phone', $data)) {
            $payload['phone'] = $data['phone'];
        }

        /* Password: only if provided AND long enough */
        if (!empty($data['password']) && strlen((string)$data['password']) >= 8) {
            $payload['password_hash'] = password_hash((string)$data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if (!$payload) return $existing;

        return self::update($id, $payload);
    }

    /**
     * Delete a user. Cascades to sessions, member_profiles,
     * partner_profiles, enrollments, b2b_clients, partner_sales,
     * payout_requests, password_resets (per FKs).
     * Attendance.user_id is set NULL (preserved for reporting).
     */
    public static function deleteUser(string $id): bool
    {
        $user = self::find($id);
        if (!$user) return false;

        // Safety: cannot delete the last remaining admin
        if ($user['role'] === 'admin') {
            $adminCount = (int)Database::pdo()
                ->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'")
                ->fetchColumn();
            if ($adminCount <= 1) {
                return false;
            }
        }

        return self::delete($id);
    }

    /* =============================================================
     |  DERIVED FIELDS
     ============================================================= */

    public static function daysLeft(?string $endDate, ?int $now = null): ?int
    {
        if (!$endDate) return null;
        $now = $now ?? time();
        $end = strtotime($endDate . ' 23:59:59');
        if ($end === false) return null;
        return (int)ceil(($end - $now) / 86400);
    }

    /**
     * Traffic-light classification for a listing row.
     * Returns: 'none' | 'pending' | 'paused' | 'expired' | 'cancelled' | 'urgent' | 'soon' | 'safe'
     */
    public static function urgency(array $row, ?int $now = null): string
    {
        $status = $row['enrollment_status'] ?? '';
        if ($status === '')          return 'none';
        if ($status === 'pending')   return 'pending';
        if ($status === 'paused')    return 'paused';
        if ($status === 'expired')   return 'expired';
        if ($status === 'cancelled') return 'cancelled';

        $days = $row['enrollment_days_left'] ?? null;
        if ($days === null) return 'safe';
        if ($days < 0)   return 'expired';
        if ($days <= 3)  return 'urgent';
        if ($days <= 7)  return 'soon';
        return 'safe';
    }

    /* =============================================================
     |  DISPLAY HELPERS
     ============================================================= */

    public static function roleIcon(string $role): string
    {
        return ['admin' => '🛡️', 'staff' => '👔', 'partner' => '🤝', 'user' => '🏃'][$role] ?? '👤';
    }

    public static function roleLabel(string $role): string
    {
        return ['admin' => 'Admin', 'staff' => 'Staff', 'partner' => 'Partner', 'user' => 'Member'][$role] ?? ucfirst($role);
    }

    public static function titleLabel(?string $title): string
    {
        return [
            'mr'    => 'Mr.',
            'ms'    => 'Ms.',
            'mx'    => 'Mx.',
            'dr'    => 'Dr.',
            'none'  => '',
        ][$title ?? 'none'] ?? '';
    }
}
