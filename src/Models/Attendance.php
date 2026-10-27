<?php

namespace App\Models;

use App\Core\Model;
use App\Config\Database;

class Attendance extends Model
{
    protected static string $table = 'attendance';

    protected static array $fillable = [
        'id',
        'user_id',
        'user_name',
        'date',
        'time_in',
        'time_out',
        'hours',
        'tier',
        'source',
        'manual_override_by',
        'notes',
    ];

    /* =============================================================
     |  EXISTING HELPERS (kept for backward compatibility)
     ============================================================= */

    public static function openToday(string $userId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM `attendance`
             WHERE `user_id` = ? AND `date` = CURDATE() AND `time_out` IS NULL
             ORDER BY `time_in` DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public static function todayAll(): array
    {
        return self::where(['date' => date('Y-m-d')], 'time_in DESC');
    }

    /* =============================================================
     |  FILTERED LISTING  (spec §1.4 — search by name/ID, date range, tier)
     ============================================================= */

    /**
     * @param array{
     *   from?:string, to?:string, member?:string, user_id?:string,
     *   tier?:string, source?:string, open_only?:bool,
     *   limit?:int, offset?:int
     * } $filters
     */
    public static function listWithFilters(array $filters = []): array
    {
        $pdo    = Database::pdo();
        $where  = [];
        $params = [];

        if (!empty($filters['from'])) {
            $where[]  = '`date` >= ?';
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[]  = '`date` <= ?';
            $params[] = $filters['to'];
        }
        if (!empty($filters['member'])) {
            $q = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['member']) . '%';
            $where[]  = '(`user_name` LIKE ? OR `user_id` LIKE ?)';
            $params[] = $q;
            $params[] = $q;
        }
        if (!empty($filters['user_id'])) {
            $where[]  = '`user_id` = ?';
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['tier']) && $filters['tier'] !== 'all') {
            $where[]  = '`tier` = ?';
            $params[] = $filters['tier'];
        }
        if (!empty($filters['source']) && $filters['source'] !== 'all') {
            $where[]  = '`source` = ?';
            $params[] = $filters['source'];
        }
        if (!empty($filters['open_only'])) {
            $where[] = '`time_out` IS NULL';
        }

        $limit  = max(1, min((int)($filters['limit']  ?? 500), 5000));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = 'SELECT * FROM `attendance`';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY `time_in` DESC LIMIT ' . $limit . ' OFFSET ' . $offset;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('[Attendance::listWithFilters] ' . $e->getMessage());
            return [];
        }

        /* Enrich with current tier from active enrollment */
        $tierMap = self::tierByUser();
        foreach ($rows as &$r) {
            $r['current_tier']   = $tierMap[$r['user_id'] ?? ''] ?? ($r['tier'] ?? null);
            $r['is_open']        = empty($r['time_out']);
            $r['live_minutes']   = $r['is_open']
                ? max(0, (int)floor((time() - strtotime($r['time_in'])) / 60))
                : null;
            $r['session_label']  = self::sessionLabel($r);
        }
        unset($r);

        return $rows;
    }

    /**
     * Map of user_id => active tier (from active enrollments).
     */
    public static function tierByUser(): array
    {
        try {
            $rows = Database::pdo()->query("
                SELECT `user_id`, `tier`
                FROM `enrollments`
                WHERE `status` = 'active'
                ORDER BY `end_date` DESC
            ")->fetchAll();

            $map = [];
            foreach ($rows as $r) {
                if (!isset($map[$r['user_id']])) {
                    $map[$r['user_id']] = $r['tier'];
                }
            }
            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /* =============================================================
     |  CURRENTLY IN  (spec §1.4)
     ============================================================= */

    /**
     * Every open session right now, with user tier + status.
     */
    public static function currentlyIn(): array
    {
        try {
            $rows = Database::pdo()->query("
                SELECT a.*,
                       e.`tier`   AS active_tier,
                       e.`status` AS enrollment_status,
                       e.`end_date` AS enrollment_end
                FROM `attendance` a
                LEFT JOIN `enrollments` e
                  ON e.`user_id` = a.`user_id`
                 AND e.`status` IN ('active', 'pending')
                WHERE a.`time_out` IS NULL
                  AND a.`date` = CURDATE()
                ORDER BY a.`time_in` ASC
            ")->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }

        $now = time();
        foreach ($rows as &$r) {
            $mins = max(0, (int)floor(($now - strtotime($r['time_in'])) / 60));
            $r['live_minutes']  = $mins;
            $r['live_label']    = self::durationLabel($mins);
            $r['tier']          = $r['active_tier'] ?? $r['tier'] ?? null;
        }
        unset($r);

        return $rows;
    }

    /* =============================================================
     |  TODAY STATS  (spec §1.4)
     ============================================================= */

    /**
     * @return array{
     *   total:int, in_gym:int, hours:float, peak_hour:int|null, peak_count:int,
     *   capacity:int, utilization:float, completed:int, avg_minutes:int
     * }
     */
    public static function todayStats(): array
    {
        $capacity = defined('GYM_CAPACITY') ? (int)GYM_CAPACITY : 50;

        $stats = [
            'total'       => 0,
            'in_gym'      => 0,
            'hours'       => 0.0,
            'peak_hour'   => null,
            'peak_count'  => 0,
            'capacity'    => $capacity,
            'utilization' => 0.0,
            'completed'   => 0,
            'avg_minutes' => 0,
        ];

        try {
            $rows = Database::pdo()
                ->query("SELECT * FROM `attendance` WHERE `date` = CURDATE()")
                ->fetchAll();

            $stats['total'] = count($rows);

            $hourly     = [];
            $totalMins  = 0;
            $now        = time();

            foreach ($rows as $r) {
                if (empty($r['time_out'])) {
                    $stats['in_gym']++;
                    $mins = max(0, (int)floor(($now - strtotime($r['time_in'])) / 60));
                    $stats['hours'] += $mins / 60;
                    $totalMins += $mins;
                } else {
                    $stats['completed']++;
                    $stats['hours'] += (float)($r['hours'] ?? 0);
                    $totalMins += (int)round((float)($r['hours'] ?? 0) * 60);
                }

                $h = (int)date('G', strtotime($r['time_in']));
                $hourly[$h] = ($hourly[$h] ?? 0) + 1;
            }

            foreach ($hourly as $h => $c) {
                if ($c > $stats['peak_count']) {
                    $stats['peak_count'] = $c;
                    $stats['peak_hour']  = $h;
                }
            }

            $stats['hours'] = round($stats['hours'], 1);
            $stats['avg_minutes'] = $stats['total'] > 0 ? (int)round($totalMins / $stats['total']) : 0;
            $stats['utilization'] = $capacity > 0
                ? round(($stats['in_gym'] / $capacity) * 100, 1)
                : 0.0;
        } catch (\Throwable $e) {
            error_log('[Attendance::todayStats] ' . $e->getMessage());
        }

        return $stats;
    }

    /* =============================================================
     |  HEATMAP  (spec §1.4)
     |  Grid: 7 days (Sun–Sat) × 15 hours (6 AM–8 PM)
     ============================================================= */

    /**
     * @return array{
     *   grid: array<int,array<int,int>>,  // [day_of_week][hour] = check-ins
     *   days: array<int,string>,          // day labels
     *   hours: array<int,string>,         // hour labels
     *   max: int
     * }
     */
    public static function heatmap(int $days = 30): array
    {
        $days = max(7, min($days, 365));

        /* Initialize grid */
        $grid = [];
        for ($d = 0; $d < 7; $d++) {
            $grid[$d] = [];
            for ($h = 6; $h <= 20; $h++) {
                $grid[$d][$h] = 0;
            }
        }

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("
                SELECT
                    DAYOFWEEK(`date`) - 1 AS dow,   /* 0=Sun ... 6=Sat */
                    HOUR(`time_in`)       AS hr,
                    COUNT(*)              AS c
                FROM `attendance`
                WHERE `date` >= (CURDATE() - INTERVAL ? DAY)
                  AND HOUR(`time_in`) BETWEEN 6 AND 20
                GROUP BY dow, hr
            ");
            $stmt->execute([$days]);

            foreach ($stmt->fetchAll() as $r) {
                $d = (int)$r['dow'];
                $h = (int)$r['hr'];
                if (isset($grid[$d][$h])) {
                    $grid[$d][$h] = (int)$r['c'];
                }
            }
        } catch (\Throwable $e) {
            error_log('[Attendance::heatmap] ' . $e->getMessage());
        }

        /* Compute max for scaling */
        $max = 0;
        foreach ($grid as $row) {
            $m = max($row);
            if ($m > $max) $max = $m;
        }

        $hours = [];
        for ($h = 6; $h <= 20; $h++) {
            $hours[$h] = sprintf('%02d', $h);
        }

        return [
            'grid'  => $grid,
            'days'  => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'hours' => $hours,
            'max'   => $max,
        ];
    }

    /* =============================================================
     |  STREAKS  (spec §1.4)
     |  Members with the longest current consecutive-day check-in run.
     ============================================================= */

    /**
     * @return array<int,array{user_id:string,user_name:string,streak:int,tier:?string}>
     */
    public static function topStreaks(int $limit = 10): array
    {
        try {
            $pdo = Database::pdo();

            /* Distinct visit dates per user (last 90 days is plenty for streaks) */
            $rows = $pdo->query("
                SELECT `user_id`, `user_name`, `date`
                FROM `attendance`
                WHERE `date` >= (CURDATE() - INTERVAL 90 DAY)
                GROUP BY `user_id`, `date`
                ORDER BY `user_id`, `date` DESC
            ")->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }

        $byUser = [];
        foreach ($rows as $r) {
            $uid = $r['user_id'] ?? '';
            if ($uid === '') continue;
            if (!isset($byUser[$uid])) {
                $byUser[$uid] = ['name' => $r['user_name'] ?? 'Member', 'dates' => []];
            }
            $byUser[$uid]['dates'][] = $r['date'];
        }

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $out = [];

        foreach ($byUser as $uid => $data) {
            $dates = $data['dates'];
            if (empty($dates)) continue;

            /* Current streak: starting from today (or yesterday) walking back */
            $streak = 0;
            $cursor = null;
            $first = $dates[0];

            if ($first === $today || $first === $yesterday) {
                $cursor = $first;
                foreach ($dates as $d) {
                    if ($d === $cursor) {
                        $streak++;
                        $cursor = date('Y-m-d', strtotime($cursor . ' -1 day'));
                    } else {
                        break;
                    }
                }
            }

            if ($streak > 0) {
                $out[] = [
                    'user_id'   => $uid,
                    'user_name' => $data['name'],
                    'streak'    => $streak,
                    'tier'      => null,
                ];
            }
        }

        usort($out, fn($a, $b) => $b['streak'] <=> $a['streak']);
        $out = array_slice($out, 0, $limit);

        /* Attach tier info */
        $tierMap = self::tierByUser();
        foreach ($out as &$r) {
            $r['tier'] = $tierMap[$r['user_id']] ?? null;
        }
        unset($r);

        return $out;
    }

    /* =============================================================
     |  INACTIVE MEMBERS  (spec §1.4)
     |  Members with NO check-ins in the last N days (default 7).
     ============================================================= */

    /**
     * @return array<int,array{user_id:string,user_name:string,email:string,tier:?string,last_visit:?string,days_since:?int}>
     */
    public static function inactiveMembers(int $days = 7, int $limit = 20): array
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare(
                "
                SELECT
                    u.`id`        AS user_id,
                    u.`full_name` AS user_name,
                    u.`email`,
                    MAX(a.`date`) AS last_visit
                FROM `users` u
                LEFT JOIN `attendance` a ON a.`user_id` = u.`id`
                WHERE u.`role` = 'user'
                GROUP BY u.`id`, u.`full_name`, u.`email`
                HAVING last_visit IS NULL
                    OR last_visit < (CURDATE() - INTERVAL ? DAY)
                ORDER BY last_visit ASC
                LIMIT " . max(1, min($limit, 200))
            );
            $stmt->execute([$days]);
            $rows = $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }

        $tierMap = self::tierByUser();
        $today   = time();

        foreach ($rows as &$r) {
            $r['tier'] = $tierMap[$r['user_id']] ?? null;
            $r['days_since'] = $r['last_visit']
                ? (int)floor(($today - strtotime($r['last_visit'])) / 86400)
                : null;
        }
        unset($r);

        return $rows;
    }

    /* =============================================================
     |  SEASONAL TRENDS  (spec §1.4)
     |  Monthly check-in totals over the last N months.
     ============================================================= */

    /**
     * @return array<int,array{month:string,count:int,unique_members:int,hours:float}>
     */
    public static function seasonalTrends(int $months = 12): array
    {
        $months = max(3, min($months, 24));

        try {
            $rows = Database::pdo()->query("
                SELECT
                    DATE_FORMAT(`date`, '%Y-%m') AS ym,
                    COUNT(*)                     AS count,
                    COUNT(DISTINCT `user_id`)    AS unique_members,
                    COALESCE(SUM(`hours`), 0)    AS hours
                FROM `attendance`
                WHERE `date` >= (CURDATE() - INTERVAL " . $months . " MONTH)
                GROUP BY ym
                ORDER BY ym ASC
            ")->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }

        return array_map(fn($r) => [
            'month'          => $r['ym'],
            'count'          => (int)$r['count'],
            'unique_members' => (int)$r['unique_members'],
            'hours'          => (float)$r['hours'],
        ], $rows);
    }

    /* =============================================================
     |  RETENTION CORRELATION  (spec §1.4)
     |  Buckets members by visit frequency and shows renewal rate.
     ============================================================= */

    /**
     * @return array<int,array{bucket:string,members:int,renewed:int,renewal_rate:float}>
     */
    public static function retentionCorrelation(): array
    {
        $buckets = [
            ['label' => '0 visits',   'min' => 0,  'max' => 0],
            ['label' => '1–3 visits', 'min' => 1,  'max' => 3],
            ['label' => '4–7 visits', 'min' => 4,  'max' => 7],
            ['label' => '8–14 visits', 'min' => 8,  'max' => 14],
            ['label' => '15+ visits', 'min' => 15, 'max' => PHP_INT_MAX],
        ];

        try {
            $pdo = Database::pdo();

            /* Per-user: visits in last 30 days + total lifetime visits + active enrollments */
            $rows = $pdo->query("
                SELECT
                    u.`id` AS user_id,
                    COALESCE(a30.visits_30d, 0) AS visits_30d,
                    COALESCE(en.any_enroll, 0)  AS has_enrolled,
                    COALESCE(en.active_enroll, 0) AS is_active,
                    COALESCE(en.renewed, 0)     AS has_renewed
                FROM `users` u
                LEFT JOIN (
                    SELECT `user_id`, COUNT(*) AS visits_30d
                    FROM `attendance`
                    WHERE `date` >= (CURDATE() - INTERVAL 30 DAY)
                    GROUP BY `user_id`
                ) a30 ON a30.`user_id` = u.`id`
                LEFT JOIN (
                    SELECT
                        `user_id`,
                        COUNT(*) AS any_enroll,
                        SUM(`status` = 'active') AS active_enroll,
                        SUM(`renewed_from_id` IS NOT NULL) AS renewed
                    FROM `enrollments`
                    GROUP BY `user_id`
                ) en ON en.`user_id` = u.`id`
                WHERE u.`role` = 'user'
            ")->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($buckets as $b) {
            $out[$b['label']] = [
                'bucket'       => $b['label'],
                'members'      => 0,
                'renewed'      => 0,
                'renewal_rate' => 0.0,
            ];
        }

        foreach ($rows as $r) {
            $visits = (int)$r['visits_30d'];
            foreach ($buckets as $b) {
                if ($visits >= $b['min'] && $visits <= $b['max']) {
                    $out[$b['label']]['members']++;
                    if ((int)$r['has_renewed'] > 0) {
                        $out[$b['label']]['renewed']++;
                    }
                    break;
                }
            }
        }

        foreach ($out as &$bucket) {
            $bucket['renewal_rate'] = $bucket['members'] > 0
                ? round(($bucket['renewed'] / $bucket['members']) * 100, 1)
                : 0.0;
        }
        unset($bucket);

        return array_values($out);
    }

    /* =============================================================
     |  MUTATIONS  (spec §1.4)
     ============================================================= */

    /**
     * Manual time-in from the admin panel (paper log / broken scanner).
     */
    public static function manualTimeIn(
        string $userId,
        ?string $timeIn = null,
        ?string $actorId = null,
        ?string $notes = null
    ): ?array {
        try {
            $user = User::find($userId);
            if (!$user) return null;

            $tierMap = self::tierByUser();

            $timeIn = $timeIn ?: date('Y-m-d H:i:s');
            $date   = substr($timeIn, 0, 10);

            return self::create([
                'user_id'            => $userId,
                'user_name'          => $user['full_name'] ?: $user['email'],
                'date'               => $date,
                'time_in'            => $timeIn,
                'hours'              => 0,
                'tier'               => $tierMap[$userId] ?? null,
                'source'             => 'manual',
                'manual_override_by' => $actorId,
                'notes'              => $notes,
            ]);
        } catch (\Throwable $e) {
            error_log('[Attendance::manualTimeIn] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update an attendance record (fix timestamps, notes).
     */
    public static function updateRecord(string $id, array $data): ?array
    {
        $rec = self::find($id);
        if (!$rec) return null;

        $payload = [];

        if (array_key_exists('time_in', $data) && $data['time_in']) {
            $payload['time_in'] = $data['time_in'];
            $payload['date']    = substr($data['time_in'], 0, 10);
        }
        if (array_key_exists('time_out', $data)) {
            $payload['time_out'] = $data['time_out'] ?: null;
        }
        if (array_key_exists('notes', $data)) {
            $payload['notes'] = $data['notes'];
        }
        if (array_key_exists('tier', $data)) {
            $payload['tier'] = $data['tier'] ?: null;
        }

        /* Recompute hours if both timestamps are present */
        $timeIn  = $payload['time_in']  ?? $rec['time_in'];
        $timeOut = $payload['time_out'] ?? $rec['time_out'];
        if ($timeIn && $timeOut) {
            $payload['hours'] = round(max(0, (strtotime($timeOut) - strtotime($timeIn)) / 3600), 1);
        } elseif ($timeIn && !$timeOut) {
            $payload['hours'] = 0;
        }

        return self::update($id, $payload);
    }

    /* =============================================================
     |  HELPERS
     ============================================================= */

    public static function durationLabel(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }

    public static function sessionLabel(array $r): string
    {
        if (empty($r['time_out'])) {
            $mins = max(0, (int)floor((time() - strtotime($r['time_in'])) / 60));
            return self::durationLabel($mins);
        }
        if (!empty($r['hours'])) {
            return $r['hours'] . 'h';
        }
        return '—';
    }

    public static function sourceLabel(string $source): string
    {
        return ['qr' => 'QR Scan', 'manual' => 'Manual'][$source] ?? ucfirst($source);
    }

    public static function sourceIcon(string $source): string
    {
        return ['qr' => '📷', 'manual' => '✋'][$source] ?? '•';
    }
}
