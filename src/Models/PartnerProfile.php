<?php

namespace App\Models;

use App\Core\Model;
use App\Config\Database;

class PartnerProfile extends Model
{
    protected static string $table = 'partner_profiles';

    protected static array $fillable = [
        'id',
        'user_id',
        'business_name',
        'partner_type',
        'phone',
        'referral_code',
        'status',
        'commission_rate',
        'contact_email',
        'address',
        'notes',
        'acquisition_source',
    ];

    /* =============================================================
     |  LOOKUPS
     ============================================================= */

    public static function forUser(string $userId): ?array
    {
        return self::first(['user_id' => $userId]);
    }

    public static function findByReferral(string $code): ?array
    {
        return self::first(['referral_code' => $code]);
    }

    public static function generateReferralCode(): string
    {
        for ($i = 0; $i < 20; $i++) {
            $code = 'PMZ-' . strtoupper(bin2hex(random_bytes(3)));
            if (!self::first(['referral_code' => $code])) {
                return $code;
            }
        }
        return 'PMZ-' . strtoupper(bin2hex(random_bytes(5)));
    }

    /* =============================================================
     |  RICH LISTING  (spec §1.5)
     ============================================================= */

    public static function listWithMetrics(array $filters = []): array
    {
        $pdo    = Database::pdo();
        $where  = [];
        $params = [];

        $status = $filters['status'] ?? '';
        if (
            $status !== '' && $status !== 'all'
            && in_array($status, ['active', 'inactive'], true)
        ) {
            $where[]  = 'pp.`status` = ?';
            $params[] = $status;
        }

        $type = $filters['type'] ?? '';
        if (
            $type !== '' && $type !== 'all'
            && in_array($type, ['coach', 'supplier'], true)
        ) {
            $where[]  = 'pp.`partner_type` = ?';
            $params[] = $type;
        }

        if (!empty($filters['search'])) {
            $q = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']) . '%';
            $where[]  = '(pp.`business_name` LIKE ? OR pp.`referral_code` LIKE ?
                          OR u.`email` LIKE ? OR pp.`contact_email` LIKE ?)';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        $limit  = max(1, min((int)($filters['limit']  ?? 500), 5000));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = "
            SELECT
                pp.*,
                u.`email`     AS user_email,
                u.`full_name` AS user_name,

                COALESCE(b2b.total_clients,      0) AS total_clients,
                COALESCE(b2b.linked_clients,     0) AS linked_clients,
                COALESCE(b2b.pending_clients,    0) AS pending_clients,
                COALESCE(b2b.inactive_clients,   0) AS inactive_clients,

                COALESCE(sales.total_sales,      0) AS total_sales_count,
                COALESCE(sales.approved_count,   0) AS approved_sales_count,
                COALESCE(sales.pending_count,    0) AS pending_sales_count,
                COALESCE(sales.rejected_count,   0) AS rejected_sales_count,
                COALESCE(sales.approved_amount,  0) AS approved_amount,
                COALESCE(sales.pending_amount,   0) AS pending_amount,
                COALESCE(sales.commission_earned,0) AS commission_earned,
                sales.last_sale_date,

                COALESCE(payouts.paid_amount,    0) AS payouts_paid,
                COALESCE(payouts.pending_amount, 0) AS payouts_pending,
                COALESCE(payouts.approved_count, 0) AS payouts_approved_count,
                COALESCE(payouts.pending_count,  0) AS payouts_pending_count
            FROM `partner_profiles` pp
            LEFT JOIN `users` u ON u.`id` = pp.`user_id`

            LEFT JOIN (
                SELECT
                    `partner_user_id`,
                    COUNT(*) AS total_clients,
                    SUM(`membership_status` = 'linked')   AS linked_clients,
                    SUM(`membership_status` = 'pending')  AS pending_clients,
                    SUM(`membership_status` = 'inactive') AS inactive_clients
                FROM `b2b_clients`
                GROUP BY `partner_user_id`
            ) b2b ON b2b.`partner_user_id` = pp.`user_id`

            LEFT JOIN (
                SELECT
                    `partner_user_id`,
                    COUNT(*) AS total_sales,
                    SUM(`status` = 'approved') AS approved_count,
                    SUM(`status` = 'pending')  AS pending_count,
                    SUM(`status` = 'rejected') AS rejected_count,
                    COALESCE(SUM(CASE WHEN `status` = 'approved' THEN `amount` ELSE 0 END), 0) AS approved_amount,
                    COALESCE(SUM(CASE WHEN `status` = 'pending'  THEN `amount` ELSE 0 END), 0) AS pending_amount,
                    COALESCE(SUM(CASE WHEN `status` = 'approved'
                                      THEN COALESCE(`commission_amount`, `amount`) ELSE 0 END), 0) AS commission_earned,
                    MAX(`sale_date`) AS last_sale_date
                FROM `partner_sales`
                GROUP BY `partner_user_id`
            ) sales ON sales.`partner_user_id` = pp.`user_id`

            LEFT JOIN (
                SELECT
                    `partner_user_id`,
                    COALESCE(SUM(CASE WHEN `status` = 'approved' THEN `amount` ELSE 0 END), 0) AS paid_amount,
                    COALESCE(SUM(CASE WHEN `status` = 'pending'  THEN `amount` ELSE 0 END), 0) AS pending_amount,
                    SUM(`status` = 'approved') AS approved_count,
                    SUM(`status` = 'pending')  AS pending_count
                FROM `payout_requests`
                GROUP BY `partner_user_id`
            ) payouts ON payouts.`partner_user_id` = pp.`user_id`
        ";

        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY pp.`status` ASC, pp.`business_name` ASC';
        $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('[PartnerProfile::listWithMetrics] ' . $e->getMessage());
            return [];
        }

        $now = time();
        foreach ($rows as &$r) {
            $commission = (float)($r['commission_earned'] ?? 0);
            $paidOut    = (float)($r['payouts_paid']       ?? 0);
            $pending    = (float)($r['payouts_pending']    ?? 0);
            $r['available_balance'] = max(0, $commission - $paidOut - $pending);
            $r['is_churned']        = self::isChurned($r, $now);
        }
        unset($r);

        return $rows;
    }

    public static function isChurned(array $r, ?int $now = null): bool
    {
        if (($r['status'] ?? '') !== 'active') return false;
        $now = $now ?? time();

        $lastSale = $r['last_sale_date'] ?? null;
        if ($lastSale === null) {
            $created = $r['created_date'] ?? null;
            if ($created === null) return false;
            return (($now - strtotime($created)) / 86400) > 60;
        }

        return ((($now - strtotime($lastSale)) / 86400) > 60)
            && ((int)($r['payouts_pending_count'] ?? 0) === 0);
    }

    /* =============================================================
     |  SUMMARY
     ============================================================= */

    public static function summary(): array
    {
        $summary = [
            'total'                   => 0,
            'active'                  => 0,
            'inactive'                => 0,
            'churned'                 => 0,
            'coaches'                 => 0,
            'suppliers'               => 0,
            'total_referrals'         => 0,
            'linked_clients'          => 0,
            'total_revenue'           => 0.0,
            'total_commission'        => 0.0,
            'pending_commission'      => 0.0,
            'total_paid_out'          => 0.0,
            'avg_revenue_per_partner' => 0.0,
        ];

        try {
            $pdo = Database::pdo();

            foreach (
                $pdo->query("
                SELECT `partner_type`, `status`, COUNT(*) AS c
                FROM `partner_profiles`
                GROUP BY `partner_type`, `status`
            ")->fetchAll() as $r
            ) {
                $summary['total'] += (int)$r['c'];
                if ($r['partner_type'] === 'coach')    $summary['coaches']   += (int)$r['c'];
                if ($r['partner_type'] === 'supplier') $summary['suppliers'] += (int)$r['c'];
                if ($r['status'] === 'active')   $summary['active']   += (int)$r['c'];
                if ($r['status'] === 'inactive') $summary['inactive'] += (int)$r['c'];
            }

            $summary['total_referrals'] = (int)$pdo
                ->query("SELECT COUNT(*) FROM `b2b_clients`")
                ->fetchColumn();
            $summary['linked_clients'] = (int)$pdo
                ->query("SELECT COUNT(*) FROM `b2b_clients` WHERE `membership_status` = 'linked'")
                ->fetchColumn();

            foreach (
                $pdo->query("
                SELECT
                    `status`,
                    COALESCE(SUM(`amount`), 0) AS amt,
                    COALESCE(SUM(COALESCE(`commission_amount`, `amount` * 0.10)), 0) AS comm
                FROM `partner_sales`
                GROUP BY `status`
            ")->fetchAll() as $r
            ) {
                if ($r['status'] === 'approved') {
                    $summary['total_revenue']    += (float)$r['amt'];
                    $summary['total_commission'] += (float)$r['comm'];
                } elseif ($r['status'] === 'pending') {
                    $summary['pending_commission'] += (float)$r['comm'];
                }
            }

            $summary['total_paid_out'] = (float)$pdo
                ->query("SELECT COALESCE(SUM(`amount`), 0) FROM `payout_requests` WHERE `status` = 'approved'")
                ->fetchColumn();

            $all = self::listWithMetrics(['limit' => 5000]);
            $churnCount = 0;
            foreach ($all as $p) {
                if (!empty($p['is_churned'])) $churnCount++;
            }
            $summary['churned'] = $churnCount;

            if ($summary['total'] > 0) {
                $summary['avg_revenue_per_partner'] = round(
                    $summary['total_revenue'] / $summary['total'],
                    2
                );
            }
        } catch (\Throwable $e) {
            error_log('[PartnerProfile::summary] ' . $e->getMessage());
        }

        return $summary;
    }

    /* =============================================================
     |  ANALYTICS
     ============================================================= */

    public static function acquisitionTrend(int $months = 12): array
    {
        $months = max(3, min($months, 24));

        try {
            $rows = Database::pdo()->query("
                SELECT DATE_FORMAT(`created_date`, '%Y-%m') AS ym, COUNT(*) AS c
                FROM `partner_profiles`
                WHERE `created_date` >= (CURDATE() - INTERVAL " . $months . " MONTH)
                GROUP BY ym
                ORDER BY ym ASC
            ")->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }

        return array_map(fn($r) => [
            'month' => $r['ym'],
            'count' => (int)$r['c'],
        ], $rows);
    }

    public static function typeBreakdown(): array
    {
        try {
            $rows = Database::pdo()->query("
                SELECT
                    pp.`partner_type`,
                    COUNT(DISTINCT pp.`id`) AS count,
                    COALESCE(SUM(CASE WHEN ps.`status` = 'approved' THEN ps.`amount` ELSE 0 END), 0) AS revenue
                FROM `partner_profiles` pp
                LEFT JOIN `partner_sales` ps ON ps.`partner_user_id` = pp.`user_id`
                GROUP BY pp.`partner_type`
            ")->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }

        $labels = ['coach' => 'Coach', 'supplier' => 'Supplier'];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'type'    => $r['partner_type'],
                'label'   => $labels[$r['partner_type']] ?? ucfirst($r['partner_type']),
                'count'   => (int)$r['count'],
                'revenue' => (float)$r['revenue'],
            ];
        }
        return $out;
    }

    public static function topPerformers(int $limit = 8): array
    {
        try {
            $rows = Database::pdo()->query(
                "
                SELECT
                    pp.`business_name`,
                    pp.`user_id`,
                    pp.`partner_type`,
                    COALESCE(SUM(CASE WHEN ps.`status` = 'approved' THEN ps.`amount` ELSE 0 END), 0) AS revenue,
                    SUM(ps.`status` = 'approved') AS sales
                FROM `partner_profiles` pp
                LEFT JOIN `partner_sales` ps ON ps.`partner_user_id` = pp.`user_id`
                GROUP BY pp.`id`, pp.`business_name`, pp.`user_id`, pp.`partner_type`
                HAVING revenue > 0
                ORDER BY revenue DESC
                LIMIT " . max(1, min($limit, 50))
            )->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }

        return array_map(fn($r) => [
            'business_name' => $r['business_name'],
            'user_id'       => $r['user_id'],
            'partner_type'  => $r['partner_type'],
            'revenue'       => (float)$r['revenue'],
            'sales'         => (int)$r['sales'],
        ], $rows);
    }

    /* =============================================================
     |  MUTATIONS
     ============================================================= */

    public static function updateProfile(string $id, array $data): ?array
    {
        $p = self::find($id);
        if (!$p) return null;

        $payload = [];

        if (array_key_exists('business_name', $data)) {
            $payload['business_name'] = trim((string)$data['business_name']);
        }
        if (
            array_key_exists('partner_type', $data)
            && in_array($data['partner_type'], ['coach', 'supplier'], true)
        ) {
            $payload['partner_type'] = $data['partner_type'];
        }
        if (array_key_exists('phone', $data)) {
            $payload['phone'] = $data['phone'];
        }
        if (array_key_exists('contact_email', $data)) {
            $payload['contact_email'] = $data['contact_email'];
        }
        if (array_key_exists('address', $data)) {
            $payload['address'] = $data['address'];
        }
        if (array_key_exists('notes', $data)) {
            $payload['notes'] = $data['notes'];
        }
        if (
            array_key_exists('status', $data)
            && in_array($data['status'], ['active', 'inactive'], true)
        ) {
            $payload['status'] = $data['status'];
        }
        if (array_key_exists('commission_rate', $data)) {
            $rate = (float)$data['commission_rate'];
            if ($rate >= 0 && $rate <= 50) {
                $payload['commission_rate'] = round($rate, 2);
            }
        }

        if (!$payload) return $p;
        return self::update($id, $payload);
    }

    public static function toggleStatus(string $id): ?array
    {
        $p = self::find($id);
        if (!$p) return null;
        $new = $p['status'] === 'active' ? 'inactive' : 'active';
        return self::update($id, ['status' => $new]);
    }
}
