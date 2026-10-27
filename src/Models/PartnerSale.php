<?php

namespace App\Models;

use App\Core\Model;
use App\Config\Database;

class PartnerSale extends Model
{
    protected static string $table = 'partner_sales';

    protected static array $fillable = [
        'id',
        'partner_user_id',
        'type',
        'description',
        'amount',
        'sale_date',
        'status',
        'commission_rate',
        'commission_amount',
        'approved_by',
        'approved_date',
        'rejected_reason',
        'client_id',
    ];

    /* =============================================================
     |  LISTING  (spec §1.5)
     ============================================================= */

    public static function listWithFilters(array $filters = []): array
    {
        $pdo    = Database::pdo();
        $where  = [];
        $params = [];

        $status = $filters['status'] ?? '';
        if (
            $status !== '' && $status !== 'all'
            && in_array($status, ['pending', 'approved', 'rejected'], true)
        ) {
            $where[]  = 'ps.`status` = ?';
            $params[] = $status;
        }

        $partnerId = $filters['partner_user_id'] ?? '';
        if ($partnerId !== '') {
            $where[]  = 'ps.`partner_user_id` = ?';
            $params[] = $partnerId;
        }

        $type = $filters['type'] ?? '';
        if (
            $type !== '' && $type !== 'all'
            && in_array($type, ['product', 'client_payment'], true)
        ) {
            $where[]  = 'ps.`type` = ?';
            $params[] = $type;
        }

        if (!empty($filters['from'])) {
            $where[]  = 'ps.`sale_date` >= ?';
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[]  = 'ps.`sale_date` <= ?';
            $params[] = $filters['to'];
        }
        if (!empty($filters['search'])) {
            $q = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']) . '%';
            $where[]  = '(ps.`description` LIKE ? OR pp.`business_name` LIKE ?)';
            $params[] = $q;
            $params[] = $q;
        }

        $limit  = max(1, min((int)($filters['limit']  ?? 500), 5000));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = "
            SELECT
                ps.*,
                pp.`business_name`,
                pp.`partner_type`,
                pp.`commission_rate` AS partner_commission_rate,
                u.`email` AS partner_email
            FROM `partner_sales` ps
            LEFT JOIN `partner_profiles` pp ON pp.`user_id` = ps.`partner_user_id`
            LEFT JOIN `users` u             ON u.`id`      = ps.`partner_user_id`
        ";

        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY ps.`sale_date` DESC, ps.`created_date` DESC';
        $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('[PartnerSale::listWithFilters] ' . $e->getMessage());
            return [];
        }
    }

    public static function summary(): array
    {
        $summary = [
            'total'               => 0,
            'pending'             => 0,
            'approved'            => 0,
            'rejected'            => 0,
            'total_amount'        => 0.0,
            'pending_amount'      => 0.0,
            'approved_amount'     => 0.0,
            'rejected_amount'     => 0.0,
            'commission_pending'  => 0.0,
            'commission_approved' => 0.0,
        ];

        try {
            foreach (
                Database::pdo()->query("
                SELECT
                    `status`,
                    COUNT(*) AS c,
                    COALESCE(SUM(`amount`), 0) AS amt,
                    COALESCE(SUM(COALESCE(`commission_amount`, `amount` * 0.10)), 0) AS comm
                FROM `partner_sales`
                GROUP BY `status`
            ")->fetchAll() as $r
            ) {
                $key = $r['status'];
                if (isset($summary[$key])) $summary[$key] = (int)$r['c'];
                $summary['total'] += (int)$r['c'];
                $summary['total_amount'] += (float)$r['amt'];

                if ($key === 'pending') {
                    $summary['pending_amount']     = (float)$r['amt'];
                    $summary['commission_pending'] = (float)$r['comm'];
                } elseif ($key === 'approved') {
                    $summary['approved_amount']     = (float)$r['amt'];
                    $summary['commission_approved'] = (float)$r['comm'];
                } elseif ($key === 'rejected') {
                    $summary['rejected_amount'] = (float)$r['amt'];
                }
            }
        } catch (\Throwable $e) {
            error_log('[PartnerSale::summary] ' . $e->getMessage());
        }

        return $summary;
    }

    /* =============================================================
     |  COMMISSION CALCULATION  (spec §1.5)
     ============================================================= */

    public static function calculateCommission(string $partnerUserId, float $amount): array
    {
        $rate = null;

        try {
            $stmt = Database::pdo()->prepare(
                'SELECT `commission_rate` FROM `partner_profiles` WHERE `user_id` = ? LIMIT 1'
            );
            $stmt->execute([$partnerUserId]);
            $val = $stmt->fetchColumn();
            if ($val !== false && $val !== null) {
                $rate = (float)$val;
            }
        } catch (\Throwable $e) { /* fall through */
        }

        if ($rate === null) {
            try {
                $stmt = Database::pdo()->query(
                    "SELECT `setting_value` FROM `system_settings`
                     WHERE `setting_key` = 'default_commission_rate' LIMIT 1"
                );
                $val = $stmt->fetchColumn();
                if ($val !== false && $val !== null) {
                    $rate = (float)$val;
                }
            } catch (\Throwable $e) { /* fall through */
            }
        }

        if ($rate === null || $rate <= 0) $rate = 10.0;

        return [
            'rate'   => $rate,
            'amount' => round($amount * ($rate / 100), 2),
        ];
    }

    /* =============================================================
     |  MUTATIONS
     ============================================================= */

    public static function logSale(
        string $partnerUserId,
        string $description,
        float $amount,
        string $type = 'product',
        ?string $clientId = null,
        ?string $actorId = null
    ): ?array {
        $commission = self::calculateCommission($partnerUserId, $amount);

        return self::create([
            'partner_user_id'   => $partnerUserId,
            'type'              => in_array($type, ['product', 'client_payment'], true) ? $type : 'product',
            'description'       => mb_substr($description, 0, 255),
            'amount'            => round($amount, 2),
            'sale_date'         => date('Y-m-d'),
            'status'            => 'pending',
            'commission_rate'   => $commission['rate'],
            'commission_amount' => $commission['amount'],
            'client_id'         => $clientId,
        ]);
    }

    public static function approve(string $id, ?string $actorId = null): ?array
    {
        $sale = self::find($id);
        if (!$sale || $sale['status'] === 'approved') return $sale;

        $update = [
            'status'        => 'approved',
            'approved_by'   => $actorId,
            'approved_date' => date('Y-m-d H:i:s'),
        ];

        if (empty($sale['commission_amount'])) {
            $comm = self::calculateCommission($sale['partner_user_id'], (float)$sale['amount']);
            $update['commission_rate']   = $comm['rate'];
            $update['commission_amount'] = $comm['amount'];
        }

        self::update($id, $update);
        return self::find($id);
    }

    public static function reject(string $id, string $reason, ?string $actorId = null): ?array
    {
        $sale = self::find($id);
        if (!$sale || $sale['status'] === 'rejected') return $sale;

        self::update($id, [
            'status'          => 'rejected',
            'rejected_reason' => mb_substr($reason, 0, 255),
        ]);
        return self::find($id);
    }
}
