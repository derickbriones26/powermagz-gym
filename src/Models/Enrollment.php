<?php

namespace App\Models;

use App\Core\Model;
use App\Config\Database;

class Enrollment extends Model
{
    protected static string $table = 'enrollments';

    protected static array $fillable = [
        'id',
        'user_id',
        'user_name',
        'program_id',
        'program_name',
        'tier',
        'status',
        'start_date',
        'end_date',
        'balance',
        'cancel_reason',
        'cancelled_date',
        'cancelled_by',
        'paused_date',
        'paused_by',
        'resume_date',
        'previous_tier',
        'renewed_from_id',
        'auto_renew',
        'grace_until',
        'notes',
    ];

    /* =============================================================
     |  LOOKUPS
     ============================================================= */

    public static function forUser(string $userId, int $limit = 200): array
    {
        return self::where(['user_id' => $userId], 'created_date DESC', $limit);
    }

    public static function activeForUser(string $userId): ?array
    {
        $rows = self::where(['user_id' => $userId, 'status' => 'active'], 'end_date DESC', 5);
        return $rows[0] ?? null;
    }

    public static function pendingForUser(string $userId): ?array
    {
        $rows = self::where(['user_id' => $userId, 'status' => 'pending'], 'created_date DESC', 5);
        return $rows[0] ?? null;
    }

    public static function findWithDetails(string $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT
                e.*,
                u.email        AS user_email,
                u.full_name    AS user_full_name,
                u.role         AS user_role,
                p.icon         AS program_icon,
                p.duration_days,
                p.grace_period_days,
                p.price        AS program_price
            FROM `enrollments` e
            LEFT JOIN `users`    u ON u.id = e.user_id
            LEFT JOIN `programs` p ON p.id = e.program_id
            WHERE e.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /* =============================================================
     |  ANALYTICS
     ============================================================= */

    public static function allWithStats(int $limit = 500): array
    {
        $pdo = Database::pdo();
        $sql = "
            SELECT
                e.*,
                COALESCE(a.checkins_30d, 0) AS attendance_30d
            FROM `enrollments` e
            LEFT JOIN (
                SELECT `user_id`, COUNT(*) AS checkins_30d
                FROM `attendance`
                WHERE `date` >= (CURDATE() - INTERVAL 30 DAY)
                GROUP BY `user_id`
            ) a ON a.`user_id` = e.`user_id`
            ORDER BY e.`created_date` DESC
            LIMIT " . (int)$limit;

        $rows = $pdo->query($sql)->fetchAll();

        $now = time();
        foreach ($rows as &$r) {
            $r['days_remaining']    = self::daysRemaining($r, $now);
            $r['progress_percent']  = self::progressPercent($r, $now);
            $r['urgency']           = self::urgency($r, $now);
        }
        unset($r);

        return $rows;
    }

    public static function statusSummary(): array
    {
        $summary = [
            'active'        => 0,
            'pending'       => 0,
            'expired'       => 0,
            'paused'        => 0,
            'cancelled'     => 0,
            'expiring_soon' => 0,
            'total'         => 0,
        ];

        $pdo = Database::pdo();

        foreach (
            $pdo->query(
                'SELECT `status`, COUNT(*) AS c FROM `enrollments` GROUP BY `status`'
            )->fetchAll() as $r
        ) {
            if (isset($summary[$r['status']])) {
                $summary[$r['status']] = (int)$r['c'];
            }
            $summary['total'] += (int)$r['c'];
        }

        $summary['expiring_soon'] = (int)$pdo
            ->query("SELECT COUNT(*) FROM `enrollments`
                     WHERE `status` = 'active'
                       AND `end_date` IS NOT NULL
                       AND DATEDIFF(`end_date`, CURDATE()) BETWEEN 0 AND 3")
            ->fetchColumn();

        return $summary;
    }

    public static function tierCounts(): array
    {
        $counts = ['digital' => 0, 'standard' => 0, 'premium' => 0, 'b2b' => 0];
        $pdo    = Database::pdo();

        foreach (
            $pdo->query(
                'SELECT `tier`, COUNT(*) AS c FROM `enrollments` GROUP BY `tier`'
            )->fetchAll() as $r
        ) {
            if (isset($counts[$r['tier']])) $counts[$r['tier']] = (int)$r['c'];
        }

        return $counts;
    }

    public static function trends12m(): array
    {
        $pdo = Database::pdo();
        $rows = $pdo->query("
            SELECT
                DATE_FORMAT(`updated_date`, '%Y-%m') AS ym,
                SUM(`status` = 'expired')   AS expired,
                SUM(`status` = 'cancelled') AS cancelled
            FROM `enrollments`
            WHERE `updated_date` >= (CURDATE() - INTERVAL 12 MONTH)
            GROUP BY ym
            ORDER BY ym ASC
        ")->fetchAll();

        return array_map(fn($r) => [
            'month'     => $r['ym'],
            'expired'   => (int)$r['expired'],
            'cancelled' => (int)$r['cancelled'],
        ], $rows);
    }

    /* =============================================================
     |  DERIVED FIELDS  (pure functions)
     ============================================================= */

    public static function daysRemaining(array $e, ?int $now = null): ?int
    {
        if (empty($e['end_date'])) return null;
        $now = $now ?? time();
        $end = strtotime($e['end_date'] . ' 23:59:59');
        if ($end === false) return null;
        return (int)ceil(($end - $now) / 86400);
    }

    public static function progressPercent(array $e, ?int $now = null): ?int
    {
        if (empty($e['start_date']) || empty($e['end_date'])) return null;
        $now   = $now ?? time();
        $start = strtotime($e['start_date']);
        $end   = strtotime($e['end_date'] . ' 23:59:59');
        if ($start === false || $end === false || $end <= $start) return null;

        $total = max(1, $end - $start);
        $used  = max(0, $now - $start);
        return (int)min(100, round(($used / $total) * 100));
    }

    public static function urgency(array $e, ?int $now = null): string
    {
        if (($e['status'] ?? '') === 'expired') return 'expired';
        if (($e['status'] ?? '') === 'cancelled') return 'cancelled';
        if (($e['status'] ?? '') === 'paused') return 'paused';
        if (($e['status'] ?? '') === 'pending') return 'pending';

        $days = self::daysRemaining($e, $now);
        if ($days === null) return 'safe';
        if ($days < 0)  return 'expired';
        if ($days <= 3) return 'urgent';
        if ($days <= 7) return 'soon';
        return 'safe';
    }

    public static function isExpiringSoon(array $e, ?int $now = null): bool
    {
        $days = self::daysRemaining($e, $now);
        return $days !== null && $days >= 0 && $days <= 3
            && ($e['status'] ?? '') === 'active';
    }

    /* =============================================================
     |  LIFECYCLE TRANSITIONS  (spec §1.2)
     ============================================================= */

    /**
     * pending → active. 
     * 🔐 FIX: Calculate actual remaining balance instead of blindly zeroing it out.
     */
    public static function approve(string $id, ?string $actorId = null, ?string $notes = null): ?array
    {
        $e = self::find($id);
        if (!$e) return null;
        if ($e['status'] !== 'pending') return $e;

        // 1. Calculate expected price based on the program
        $expected = 0.0;
        if (!empty($e['program_id'])) {
            $program = Program::find($e['program_id']);
            $expected = (float)($program['price'] ?? 0);
        }

        // 2. Calculate how much has actually been paid for this tier
        $paid = 0.0;
        if ($expected > 0) {
            $stmt = Database::pdo()->prepare("
                SELECT COALESCE(SUM(`amount`), 0) FROM `payments`
                WHERE `user_id` = ? AND `tier` = ? AND `status` = 'paid'
            ");
            $stmt->execute([$e['user_id'], $e['tier']]);
            $paid = (float)$stmt->fetchColumn();
        }

        // 3. Balance is the difference (never below 0)
        $balance = max(0.0, $expected - $paid);

        self::update($id, [
            'status'  => 'active',
            'balance' => $balance,
        ]);

        self::logEvent($id, 'approved', 'pending', 'active', $e['tier'], $e['tier'], null, $actorId, $notes);
        return self::find($id);
    }

    public static function extend(string $id, int $days, ?string $actorId = null, ?string $notes = null): ?array
    {
        if ($days <= 0) return self::find($id);

        $e = self::find($id);
        if (!$e) return null;

        $base = $e['end_date'] ? strtotime($e['end_date']) : time();
        if ($base < time()) $base = time(); // extend from today if expired
        $newEnd = date('Y-m-d', $base + ($days * 86400));

        $update = ['end_date' => $newEnd];

        if (!empty($e['grace_until'])) {
            $gBase  = max(strtotime($e['grace_until']), strtotime($newEnd));
            $update['grace_until'] = date('Y-m-d', $gBase + ($days * 86400));
        }

        if ($e['status'] === 'expired' && strtotime($newEnd) > time()) {
            $update['status'] = 'active';
        }

        self::update($id, $update);

        $toStatus = $update['status'] ?? $e['status'];
        self::logEvent($id, 'extended', $e['status'], $toStatus, $e['tier'], $e['tier'], "+{$days}d", $actorId, $notes);

        return self::find($id);
    }

    public static function pause(string $id, ?string $actorId = null, ?string $notes = null): ?array
    {
        $e = self::find($id);
        if (!$e || $e['status'] !== 'active') return $e;

        self::update($id, [
            'status'      => 'paused',
            'paused_date' => date('Y-m-d H:i:s'),
            'paused_by'   => $actorId,
        ]);

        self::logEvent($id, 'paused', 'active', 'paused', $e['tier'], $e['tier'], null, $actorId, $notes);
        return self::find($id);
    }

    public static function resume(string $id, ?string $actorId = null, ?string $notes = null): ?array
    {
        $e = self::find($id);
        if (!$e || $e['status'] !== 'paused') return $e;

        $update = [
            'status'      => 'active',
            'resume_date' => date('Y-m-d H:i:s'),
        ];

        if (!empty($e['paused_date']) && !empty($e['end_date'])) {
            $pausedAt = strtotime($e['paused_date']);
            $now      = time();
            $delta    = max(0, $now - $pausedAt);
            if ($delta > 0) {
                $update['end_date'] = date('Y-m-d', strtotime($e['end_date']) + $delta);
            }
        }

        self::update($id, $update);
        self::logEvent($id, 'resumed', 'paused', 'active', $e['tier'], $e['tier'], null, $actorId, $notes);
        return self::find($id);
    }

    public static function cancel(string $id, string $reason, ?string $actorId = null, ?string $notes = null): ?array
    {
        $valid = ['member_request', 'non_payment', 'relocation', 'other'];
        if (!in_array($reason, $valid, true)) $reason = 'other';

        $e = self::find($id);
        if (!$e) return null;

        self::update($id, [
            'status'         => 'cancelled',
            'cancel_reason'  => $reason,
            'cancelled_date' => date('Y-m-d H:i:s'),
            'cancelled_by'   => $actorId,
        ]);

        self::logEvent($id, 'cancelled', $e['status'], 'cancelled', $e['tier'], $e['tier'], $reason, $actorId, $notes);
        return self::find($id);
    }

    public static function changeTier(
        string $id,
        string $newTier,
        float $newPrice,
        ?string $actorId = null,
        ?string $notes = null
    ): ?array {
        $valid = ['digital', 'standard', 'premium', 'b2b'];
        if (!in_array($newTier, $valid, true)) return self::find($id);

        $e = self::find($id);
        if (!$e) return null;
        $oldTier = $e['tier'];
        if ($oldTier === $newTier) return $e;

        $direction = self::tierRank($newTier) > self::tierRank($oldTier)
            ? 'upgrade'
            : 'downgrade';

        $prorata = 0.0;
        if (!empty($e['start_date']) && !empty($e['end_date'])) {
            $start  = strtotime($e['start_date']);
            $end    = strtotime($e['end_date']);
            $now    = time();
            $total  = max(1, $end - $start);
            $unused = max(0, $end - $now);
            $fraction = $unused / $total;

            $oldProgram = $e['program_id'] ? Program::find($e['program_id']) : null;
            $oldPrice   = (float)($oldProgram['price'] ?? 0);
            $diff       = $newPrice - $oldPrice;
            $prorata    = round($fraction * $diff, 2);
        }

        self::update($id, [
            'previous_tier' => $oldTier,
            'tier'          => $newTier,
            'balance'       => max(0, (float)($e['balance'] ?? 0) + $prorata),
        ]);

        self::logEvent(
            $id,
            $direction,
            $e['status'],
            $e['status'],
            $oldTier,
            $newTier,
            'Pro-rata ' . ($prorata >= 0 ? '+' : '') . number_format($prorata, 2),
            $actorId,
            $notes
        );

        return self::find($id);
    }

    public static function expire(string $id, ?string $actorId = null, ?string $notes = null): ?array
    {
        $e = self::find($id);
        if (!$e) return null;

        self::update($id, [
            'status'   => 'expired',
            'end_date' => $e['end_date'] && strtotime($e['end_date']) > time()
                ? date('Y-m-d')
                : $e['end_date'],
        ]);

        self::logEvent($id, 'expired', $e['status'], 'expired', $e['tier'], $e['tier'], null, $actorId, $notes);
        return self::find($id);
    }

    /**
     * 🔐 FIX: Ensure the new enrollment doesn't start in the past.
     */
    public static function renew(string $oldId, int $extraDays = 0, ?string $actorId = null): ?array
    {
        $old = self::find($oldId);
        if (!$old) return null;

        $program = $old['program_id'] ? Program::find($old['program_id']) : null;

        $duration = $program['duration_days'] ?? 30;
        $duration += $extraDays;

        // Start from today OR the old end date, whichever is LATER.
        $base = max(time(), strtotime($old['end_date'] ?? 'now'));
        $newStart = date('Y-m-d', $base);
        $newEnd   = date('Y-m-d', strtotime($newStart . " +{$duration} days"));

        $created = self::create([
            'user_id'          => $old['user_id'],
            'user_name'        => $old['user_name'],
            'program_id'       => $old['program_id'],
            'program_name'     => $old['program_name'],
            'tier'             => $old['tier'],
            'status'           => 'pending',
            'start_date'       => $newStart,
            'end_date'         => $newEnd,
            'balance'          => $program['price'] ?? 0,
            'renewed_from_id'  => $oldId,
            'auto_renew'       => $old['auto_renew'] ?? 0,
        ]);

        self::update($oldId, ['status' => 'expired']);
        self::logEvent(
            $oldId,
            'renewed',
            $old['status'],
            'expired',
            $old['tier'],
            $old['tier'],
            'Renewed as ' . $created['id'],
            $actorId,
            null
        );

        return $created;
    }

    public static function syncExpirations(): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            UPDATE `enrollments`
            SET    `status` = 'expired'
            WHERE  `status` = 'active'
              AND  (
                (`grace_until` IS NOT NULL AND `grace_until` < CURDATE())
                OR
                (`grace_until` IS NULL AND `end_date` IS NOT NULL AND `end_date` < (CURDATE() - INTERVAL 3 DAY))
              )
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /* =============================================================
     |  EVENT LOG  (spec §1.2)
     ============================================================= */

    public static function logEvent(
        string $enrollmentId,
        string $eventType,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $fromTier = null,
        ?string $toTier = null,
        ?string $reason = null,
        ?string $actorId = null,
        ?string $notes = null
    ): void {
        try {
            Database::pdo()->prepare("
                INSERT INTO `enrollment_events`
                  (`enrollment_id`, `event_type`, `from_status`, `to_status`,
                   `from_tier`, `to_tier`, `reason`, `performed_by`, `notes`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $enrollmentId,
                $eventType,
                $fromStatus,
                $toStatus,
                $fromTier,
                $toTier,
                $reason ? mb_substr($reason, 0, 255) : null,
                $actorId,
                $notes,
            ]);
        } catch (\Throwable $e) {
            error_log('[Enrollment::logEvent] ' . $e->getMessage());
        }
    }

    public static function events(string $enrollmentId, int $limit = 100): array
    {
        try {
            $stmt = Database::pdo()->prepare("
                SELECT * FROM `enrollment_events`
                WHERE `enrollment_id` = ?
                ORDER BY `created_date` DESC
                LIMIT " . (int)$limit);
            $stmt->execute([$enrollmentId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 🔐 FIX: Fetch events for multiple enrollments in a single query to avoid N+1.
     */
    public static function eventsForMany(array $ids, int $perEnrollment = 50): array
    {
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {
            // Use ROW_NUMBER() to limit the results per enrollment
            $stmt = Database::pdo()->prepare("
                SELECT * FROM (
                    SELECT *, 
                           ROW_NUMBER() OVER (PARTITION BY `enrollment_id` ORDER BY `created_date` DESC) AS rn
                    FROM `enrollment_events`
                    WHERE `enrollment_id` IN ($placeholders)
                ) sub
                WHERE rn <= ?
                ORDER BY `created_date` DESC
            ");

            $params = array_merge($ids, [$perEnrollment]);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $out = [];
            foreach ($rows as $r) {
                $out[$r['enrollment_id']][] = $r;
            }
            return $out;
        } catch (\Throwable $e) {
            error_log('[Enrollment::eventsForMany] ' . $e->getMessage());
            return [];
        }
    }

    /* =============================================================
     |  HELPERS
     ============================================================= */

    public static function tierRank(string $tier): int
    {
        return ['digital' => 1, 'standard' => 2, 'premium' => 3, 'b2b' => 4][$tier] ?? 0;
    }

    public static function statusLabel(string $status): string
    {
        return [
            'active'    => 'Active',
            'pending'   => 'Pending',
            'expired'   => 'Expired',
            'paused'    => 'Paused',
            'cancelled' => 'Cancelled',
        ][$status] ?? ucfirst($status);
    }

    public static function statusIcon(string $status): string
    {
        return [
            'active'    => '✅',
            'pending'   => '⏳',
            'expired'   => '❌',
            'paused'    => '⏸️',
            'cancelled' => '🚫',
        ][$status] ?? '📋';
    }

    public static function cancelReasonLabel(string $reason): string
    {
        return [
            'member_request' => 'Member request',
            'non_payment'    => 'Non-payment',
            'relocation'     => 'Relocation',
            'other'          => 'Other',
        ][$reason] ?? ucfirst(str_replace('_', ' ', $reason));
    }
}
