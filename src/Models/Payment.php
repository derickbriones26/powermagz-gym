<?php

namespace App\Models;

use App\Core\Model;
use App\Config\Database;

class Payment extends Model
{
    protected static string $table = 'payments';

    protected static array $fillable = [
        'id',
        'user_id',
        'user_name',
        'source',
        'tier',
        'amount',
        'method',
        'status',
        'paid_date',
        'description',
        'reference_no',
        'payment_token',
        'receipt_no',
        'due_date',
        'confirmed_by',
        'confirmed_date',
        'failed_reason',
        'refund_amount',
        'refund_reason',
        'refund_date',
        'refunded_by',
        'notes',
    ];

    /* =============================================================
     |  CONSTANTS
     ============================================================= */

    public const STATUSES = ['paid', 'pending', 'failed', 'refunded', 'partial_refund'];
    public const METHODS  = ['gcash', 'cash', 'credit_card', 'bank_transfer'];
    public const TIERS    = ['digital', 'standard', 'premium', 'b2b'];

    /* =============================================================
     |  FILTERED LISTING  (spec §1.3)
     ============================================================= */

    public static function listWithFilters(array $filters = []): array
    {
        $pdo    = Database::pdo();
        $where  = [];
        $params = [];

        /* Status */
        $status = $filters['status'] ?? '';
        if ($status !== '' && $status !== 'all') {
            if (in_array($status, self::STATUSES, true)) {
                $where[]  = '`status` = ?';
                $params[] = $status;
            } elseif ($status === 'not_paid') {
                $where[] = "`status` IN ('pending', 'failed')";
            }
        }

        /* Method */
        $method = $filters['method'] ?? '';
        if ($method !== '' && $method !== 'all' && in_array($method, self::METHODS, true)) {
            $where[]  = '`method` = ?';
            $params[] = $method;
        }

        /* Tier */
        $tier = $filters['tier'] ?? '';
        if ($tier !== '' && $tier !== 'all' && in_array($tier, self::TIERS, true)) {
            $where[]  = '`tier` = ?';
            $params[] = $tier;
        }

        /* Source (B2C / B2B) */
        $source = $filters['source'] ?? '';
        if ($source === 'b2c' || $source === 'b2b') {
            $where[]  = '`source` = ?';
            $params[] = $source;
        }

        /* Date range */
        if (!empty($filters['from'])) {
            $where[]  = '`paid_date` >= ?';
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[]  = '`paid_date` <= ?';
            $params[] = $filters['to'];
        }

        /* Search (member name or description or reference) */
        if (!empty($filters['search'])) {
            $q = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']) . '%';
            $where[]  = '(`user_name` LIKE ? OR `description` LIKE ? OR `reference_no` LIKE ?)';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        $limit  = max(1, min((int)($filters['limit']  ?? 500), 5000));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = 'SELECT * FROM `payments`';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY `paid_date` DESC, `created_date` DESC';
        $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('[Payment::listWithFilters] ' . $e->getMessage());
            return [];
        }

        /* Derived: aging bucket + due-date urgency */
        $now = time();
        foreach ($rows as &$r) {
            $r['aging_days']   = self::agingDays($r);
            $r['due_urgency']  = self::dueUrgency($r, $now);
        }
        unset($r);

        return $rows;
    }

    /* =============================================================
     |  SUMMARY  (spec §1.3 — KPI strip)
     ============================================================= */

    public static function statusSummary(): array
    {
        $summary = [
            'total'            => 0,
            'paid'             => 0,
            'pending'          => 0,
            'failed'           => 0,
            'refunded'         => 0,
            'partial_refund'   => 0,
            'revenue_paid'     => 0.0,
            'revenue_pending'  => 0.0,
            'revenue_refunded' => 0.0,
            'today'            => 0.0,
            'this_week'        => 0.0,
            'this_month'       => 0.0,
            'this_year'        => 0.0,
            'success_rate'     => 0.0,
            'failure_rate'     => 0.0,
        ];

        try {
            $pdo = Database::pdo();

            /* Counts per status */
            foreach (
                $pdo->query(
                    'SELECT `status`, COUNT(*) AS c FROM `payments` GROUP BY `status`'
                )->fetchAll() as $r
            ) {
                if (isset($summary[$r['status']])) $summary[$r['status']] = (int)$r['c'];
                $summary['total'] += (int)$r['c'];
            }

            /* Revenue by status */
            // 🔐 FIX: Pull both the gross amount and the refund amount
            foreach (
                $pdo->query("
                    SELECT 
                        `status`, 
                        COALESCE(SUM(`amount`), 0) AS gross,
                        COALESCE(SUM(`refund_amount`), 0) AS refunded
                    FROM `payments` 
                    GROUP BY `status`
                ")->fetchAll() as $r
            ) {
                if ($r['status'] === 'paid') {
                    $summary['revenue_paid'] = (float)$r['gross'];
                } elseif ($r['status'] === 'pending') {
                    $summary['revenue_pending'] = (float)$r['gross'];
                } elseif ($r['status'] === 'refunded') {
                    // Full refund: the whole amount is refunded
                    $summary['revenue_refunded'] += (float)$r['gross'];
                } elseif ($r['status'] === 'partial_refund') {
                    // Partial refund: ONLY add the actual refunded amount
                    $summary['revenue_refunded'] += (float)$r['refunded'];
                }
            }

            /* Period revenue (paid only) */
            $summary['today'] = (float)$pdo
                ->query("SELECT COALESCE(SUM(`amount`),0) FROM `payments`
                         WHERE `status`='paid' AND `paid_date` = CURDATE()")
                ->fetchColumn();

            $summary['this_week'] = (float)$pdo
                ->query("SELECT COALESCE(SUM(`amount`),0) FROM `payments`
                         WHERE `status`='paid' AND YEARWEEK(`paid_date`,1) = YEARWEEK(CURDATE(),1)")
                ->fetchColumn();

            $summary['this_month'] = (float)$pdo
                ->query("SELECT COALESCE(SUM(`amount`),0) FROM `payments`
                         WHERE `status`='paid'
                           AND YEAR(`paid_date`) = YEAR(CURDATE())
                           AND MONTH(`paid_date`) = MONTH(CURDATE())")
                ->fetchColumn();

            $summary['this_year'] = (float)$pdo
                ->query("SELECT COALESCE(SUM(`amount`),0) FROM `payments`
                         WHERE `status`='paid' AND YEAR(`paid_date`) = YEAR(CURDATE())")
                ->fetchColumn();

            /* Success / failure rate */
            $settled = $summary['paid'] + $summary['failed'];
            if ($settled > 0) {
                $summary['success_rate'] = round(($summary['paid'] / $settled) * 100, 1);
                $summary['failure_rate'] = round(($summary['failed'] / $settled) * 100, 1);
            }
        } catch (\Throwable $e) {
            error_log('[Payment::statusSummary] ' . $e->getMessage());
        }

        return $summary;
    }

    /**
     * ARPU — Average Revenue Per User, for a given period.
     */
    public static function arpu(string $from = '', string $to = ''): float
    {
        try {
            $pdo = Database::pdo();

            $sql    = "SELECT COALESCE(SUM(`amount`),0) FROM `payments` WHERE `status` = 'paid'";
            $params = [];
            if ($from !== '') {
                $sql .= ' AND `paid_date` >= ?';
                $params[] = $from;
            }
            if ($to   !== '') {
                $sql .= ' AND `paid_date` <= ?';
                $params[] = $to;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $total = (float)$stmt->fetchColumn();

            $users = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` != 'admin'")->fetchColumn();

            return $users > 0 ? round($total / $users, 2) : 0.0;
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /* =============================================================
     |  AGING REPORT  (spec §1.3)
     ============================================================= */

    public static function agingReport(int $limit = 200): array
    {
        $result = [
            'buckets' => [
                'current' => ['count' => 0, 'total' => 0.0],
                '1_7'     => ['count' => 0, 'total' => 0.0],
                '8_14'    => ['count' => 0, 'total' => 0.0],
                '15_30'   => ['count' => 0, 'total' => 0.0],
                '30_plus' => ['count' => 0, 'total' => 0.0],
            ],
            'rows' => [],
        ];

        try {
            $pdo = Database::pdo();

            $rows = $pdo->query(
                "
                SELECT *,
                       DATEDIFF(CURDATE(), COALESCE(`due_date`, `paid_date`)) AS aging_days
                FROM `payments`
                WHERE `status` IN ('pending', 'failed')
                ORDER BY aging_days DESC
                LIMIT " . max(1, min($limit, 1000))
            )->fetchAll();

            foreach ($rows as $r) {
                $days  = (int)($r['aging_days'] ?? 0);
                $amt   = (float)($r['amount'] ?? 0);

                if ($days <= 0)  $bucket = 'current';
                elseif ($days <= 7)  $bucket = '1_7';
                elseif ($days <= 14) $bucket = '8_14';
                elseif ($days <= 30) $bucket = '15_30';
                else                 $bucket = '30_plus';

                $result['buckets'][$bucket]['count']++;
                $result['buckets'][$bucket]['total'] += $amt;
                $r['aging_bucket'] = $bucket;
                $result['rows'][] = $r;
            }
        } catch (\Throwable $e) {
            error_log('[Payment::agingReport] ' . $e->getMessage());
        }

        return $result;
    }

    /* =============================================================
     |  BREAKDOWN HELPERS  (feed Analytics charts)
     ============================================================= */

    public static function revenueByTier(string $from = '', string $to = ''): array
    {
        return self::groupedRevenue('tier', $from, $to);
    }

    public static function revenueByMethod(string $from = '', string $to = ''): array
    {
        return self::groupedRevenue('method', $from, $to);
    }

    public static function revenueBySource(string $from = '', string $to = ''): array
    {
        return self::groupedRevenue('source', $from, $to);
    }

    public static function revenueDaily(int $days = 30): array
    {
        $days = max(1, min($days, 365));
        try {
            $rows = Database::pdo()->query("
                SELECT `paid_date` AS d, COALESCE(SUM(`amount`),0) AS total
                FROM `payments`
                WHERE `status` = 'paid'
                  AND `paid_date` >= (CURDATE() - INTERVAL " . $days . " DAY)
                GROUP BY `paid_date`
                ORDER BY `paid_date` ASC
            ")->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }

        $map = [];
        foreach ($rows as $r) $map[$r['d']] = (float)$r['total'];

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = ['date' => $d, 'total' => $map[$d] ?? 0.0];
        }
        return $series;
    }

    /* =============================================================
     |  MUTATIONS  (spec §1.3)
     ============================================================= */

    public static function markPaid(string $id, ?string $actorId = null): ?array
    {
        $p = self::find($id);
        if (!$p) return null;
        if ($p['status'] === 'paid') return $p;

        $update = [
            'status'         => 'paid',
            'confirmed_by'   => $actorId,
            'confirmed_date' => date('Y-m-d H:i:s'),
        ];

        if (empty($p['receipt_no'])) {
            $update['receipt_no'] = self::nextReceiptNo();
        }

        self::update($id, $update);
        return self::find($id);
    }

    public static function bulkConfirm(array $ids, ?string $actorId = null): array
    {
        $confirmed = 0;
        $skipped   = 0;
        $doneIds   = [];

        foreach ($ids as $id) {
            $id = trim((string)$id);
            if ($id === '') continue;

            $p = self::find($id);
            if (!$p || $p['status'] === 'paid') {
                $skipped++;
                continue;
            }

            self::markPaid($id, $actorId);
            $confirmed++;
            $doneIds[] = $id;
        }

        return ['confirmed' => $confirmed, 'skipped' => $skipped, 'ids' => $doneIds];
    }

    public static function refund(
        string $id,
        float $amount,
        string $reason,
        ?string $actorId = null
    ): ?array {
        $p = self::find($id);
        if (!$p) return null;
        if (!in_array($p['status'], ['paid', 'partial_refund'], true)) return $p;

        $original = (float)$p['amount'];
        $alreadyRefunded = (float)($p['refund_amount'] ?? 0);
        $totalRefund = min($alreadyRefunded + $amount, $original);
        $isFull = $totalRefund >= $original - 0.01;

        self::update($id, [
            'status'        => $isFull ? 'refunded' : 'partial_refund',
            'refund_amount' => $totalRefund,
            'refund_reason' => mb_substr($reason, 0, 255),
            'refund_date'   => date('Y-m-d H:i:s'),
            'refunded_by'   => $actorId,
        ]);

        return self::find($id);
    }

    public static function markFailed(string $id, string $reason, ?string $actorId = null): ?array
    {
        $p = self::find($id);
        if (!$p || $p['status'] === 'paid') return $p;

        self::update($id, [
            'status'        => 'failed',
            'failed_reason' => mb_substr($reason, 0, 255),
        ]);

        return self::find($id);
    }

    /* =============================================================
     |  RECEIPT NUMBER
     ============================================================= */

    public static function nextReceiptNo(): string
    {
        $year = date('Y');
        try {
            $pdo = Database::pdo();
            $last = $pdo->prepare("
                SELECT `receipt_no` FROM `payments`
                WHERE `receipt_no` LIKE ?
                ORDER BY `receipt_no` DESC
                LIMIT 1
            ");
            $last->execute(["PMZ-{$year}-%"]);
            $val = $last->fetchColumn();

            if ($val) {
                $parts = explode('-', $val);
                $n     = (int)end($parts);
                $n++;
            } else {
                $n = 1;
            }

            return sprintf('PMZ-%s-%05d', $year, $n);
        } catch (\Throwable $e) {
            return sprintf('PMZ-%s-%05d', $year, rand(1, 99999));
        }
    }

    /* =============================================================
     |  METHOD CONFIG  (spec §1.3)
     ============================================================= */

    public static function methodConfig(): array
    {
        try {
            $pdo = Database::pdo();

            $get = function (string $key, string $default) use ($pdo): string {
                $stmt = $pdo->prepare("SELECT `setting_value` FROM `system_settings`
                                       WHERE `setting_key` = ? LIMIT 1");
                $stmt->execute([$key]);
                $val = $stmt->fetchColumn();
                return $val !== false && $val !== null ? (string)$val : $default;
            };

            $methodsJson = $get('accepted_payment_methods', json_encode(self::METHODS));
            $methods = json_decode($methodsJson, true);
            if (!is_array($methods) || !$methods) $methods = self::METHODS;

            return [
                'accepted_methods'      => array_values(array_intersect($methods, self::METHODS)),
                'collection_frequency'  => $get('payment_collection_frequency', 'monthly'),
                'grace_days'            => (int)$get('late_payment_grace_days', '7'),
            ];
        } catch (\Throwable $e) {
            return [
                'accepted_methods'      => self::METHODS,
                'collection_frequency'  => 'monthly',
                'grace_days'            => 7,
            ];
        }
    }

    /* =============================================================
     |  DERIVED FIELDS
     ============================================================= */

    public static function agingDays(array $p): int
    {
        if (!in_array($p['status'] ?? '', ['pending', 'failed'], true)) return 0;
        $base = $p['due_date'] ?? $p['paid_date'] ?? null;
        if (!$base) return 0;
        return max(0, (int)floor((time() - strtotime($base)) / 86400));
    }

    public static function dueUrgency(array $p, ?int $now = null): string
    {
        if (!in_array($p['status'] ?? '', ['pending', 'failed'], true)) return 'n/a';
        if (empty($p['due_date'])) return 'ok';

        $now = $now ?? time();
        $due = strtotime($p['due_date'] . ' 23:59:59');
        if ($due === false) return 'ok';

        $diff = (int)ceil(($due - $now) / 86400);
        if ($diff < 0)  return 'overdue';
        if ($diff <= 3) return 'due_soon';
        return 'ok';
    }

    /* =============================================================
     |  DISPLAY HELPERS
     ============================================================= */

    public static function statusLabel(string $status): string
    {
        return [
            'paid'           => 'Paid',
            'pending'        => 'Pending',
            'failed'         => 'Failed',
            'refunded'       => 'Refunded',
            'partial_refund' => 'Partially Refunded',
        ][$status] ?? ucfirst($status);
    }

    public static function statusIcon(string $status): string
    {
        return [
            'paid'           => '✅',
            'pending'        => '⏳',
            'failed'         => '❌',
            'refunded'       => '↩️',
            'partial_refund' => '↩️',
        ][$status] ?? '•';
    }

    public static function statusClass(string $status): string
    {
        return [
            'paid'           => 'bg-green-100 text-green-700',
            'pending'        => 'bg-amber-100 text-amber-700',
            'failed'         => 'bg-red-100 text-[#D50000]',
            'refunded'       => 'bg-purple-100 text-purple-700',
            'partial_refund' => 'bg-purple-100 text-purple-700',
        ][$status] ?? 'bg-gray-100 text-gray-500';
    }

    public static function methodLabel(string $method): string
    {
        return [
            'gcash'         => 'GCash',
            'cash'          => 'Cash',
            'credit_card'   => 'Credit Card',
            'bank_transfer' => 'Bank Transfer',
        ][$method] ?? ucfirst($method);
    }

    public static function methodIcon(string $method): string
    {
        return [
            'gcash'         => '📱',
            'cash'          => '💵',
            'credit_card'   => '💳',
            'bank_transfer' => '🏦',
        ][$method] ?? '💰';
    }

    /* =============================================================
     |  INTERNAL
     ============================================================= */

    private static function groupedRevenue(string $column, string $from, string $to): array
    {
        if (!in_array($column, ['tier', 'method', 'source'], true)) return [];

        try {
            $sql    = "SELECT `{$column}` AS k, COALESCE(SUM(`amount`),0) AS total
                       FROM `payments` WHERE `status` = 'paid'";
            $params = [];
            if ($from !== '') {
                $sql .= ' AND `paid_date` >= ?';
                $params[] = $from;
            }
            if ($to   !== '') {
                $sql .= ' AND `paid_date` <= ?';
                $params[] = $to;
            }
            $sql .= " GROUP BY `{$column}` ORDER BY total DESC";

            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);

            $out = [];
            foreach ($stmt->fetchAll() as $r) {
                $out[$r['k']] = (float)$r['total'];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
