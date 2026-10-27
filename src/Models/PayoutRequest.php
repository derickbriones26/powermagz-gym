<?php

namespace App\Models;

use App\Core\Model;
use App\Config\Database;

class PayoutRequest extends Model
{
    protected static string $table = 'payout_requests';

    protected static array $fillable = [
        'id',
        'partner_user_id',
        'amount',
        'status',
        'requested_date',
        'note',
        'method',
        'method_details',
        'batch_id',
        'processed_date',
        'processed_by',
        'reference_no',
        'reject_reason',
    ];

    /* =============================================================
     |  LISTING
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
            $where[]  = 'pr.`status` = ?';
            $params[] = $status;
        }

        $method = $filters['method'] ?? '';
        if (
            $method !== '' && $method !== 'all'
            && in_array($method, ['gcash', 'bank_transfer', 'cash'], true)
        ) {
            $where[]  = 'pr.`method` = ?';
            $params[] = $method;
        }

        $partnerId = $filters['partner_user_id'] ?? '';
        if ($partnerId !== '') {
            $where[]  = 'pr.`partner_user_id` = ?';
            $params[] = $partnerId;
        }

        $batchId = $filters['batch_id'] ?? '';
        if ($batchId !== '') {
            $where[]  = 'pr.`batch_id` = ?';
            $params[] = $batchId;
        }

        if (!empty($filters['from'])) {
            $where[]  = 'pr.`requested_date` >= ?';
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[]  = 'pr.`requested_date` <= ?';
            $params[] = $filters['to'];
        }
        if (!empty($filters['search'])) {
            $q = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']) . '%';
            $where[]  = '(pr.`note` LIKE ? OR pp.`business_name` LIKE ?
                          OR pr.`reference_no` LIKE ?)';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        $limit  = max(1, min((int)($filters['limit']  ?? 500), 5000));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = "
            SELECT
                pr.*,
                pp.`business_name`,
                pp.`partner_type`,
                pp.`phone` AS partner_phone,
                pp.`contact_email`,
                u.`email` AS partner_email,
                u.`full_name` AS partner_name,
                pb.`status` AS batch_status,
                pb.`created_date` AS batch_created_date
            FROM `payout_requests` pr
            LEFT JOIN `partner_profiles` pp ON pp.`user_id` = pr.`partner_user_id`
            LEFT JOIN `users` u             ON u.`id`      = pr.`partner_user_id`
            LEFT JOIN `payout_batches` pb   ON pb.`id`      = pr.`batch_id`
        ";

        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY pr.`requested_date` DESC, pr.`created_date` DESC';
        $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('[PayoutRequest::listWithFilters] ' . $e->getMessage());
            return [];
        }
    }

    public static function summary(): array
    {
        $summary = [
            'total'           => 0,
            'pending'         => 0,
            'approved'        => 0,
            'rejected'        => 0,
            'total_amount'    => 0.0,
            'pending_amount'  => 0.0,
            'approved_amount' => 0.0,
            'avg_payout'      => 0.0,
            'gcash_pending'   => 0.0,
            'bank_pending'    => 0.0,
            'cash_pending'    => 0.0,
        ];

        try {
            $pdo = Database::pdo();

            foreach (
                $pdo->query("
                SELECT `status`, COUNT(*) AS c, COALESCE(SUM(`amount`), 0) AS amt
                FROM `payout_requests` GROUP BY `status`
            ")->fetchAll() as $r
            ) {
                $key = $r['status'];
                if (isset($summary[$key])) $summary[$key] = (int)$r['c'];
                $summary['total'] += (int)$r['c'];
                $summary['total_amount'] += (float)$r['amt'];
                if ($key === 'pending')  $summary['pending_amount']  = (float)$r['amt'];
                if ($key === 'approved') $summary['approved_amount'] = (float)$r['amt'];
            }

            if ($summary['approved'] > 0) {
                $summary['avg_payout'] = round($summary['approved_amount'] / $summary['approved'], 2);
            }

            foreach (
                $pdo->query("
                SELECT `method`, COALESCE(SUM(`amount`), 0) AS amt
                FROM `payout_requests`
                WHERE `status` = 'pending'
                GROUP BY `method`
            ")->fetchAll() as $r
            ) {
                $key = ($r['method'] ?? 'gcash') . '_pending';
                if (array_key_exists($key, $summary)) {
                    $summary[$key] = (float)$r['amt'];
                }
            }
        } catch (\Throwable $e) {
            error_log('[PayoutRequest::summary] ' . $e->getMessage());
        }

        return $summary;
    }

    /* =============================================================
     |  MUTATIONS
     ============================================================= */

    public static function requestPayout(
        string $partnerUserId,
        float $amount,
        string $method = 'gcash',
        ?string $details = null,
        ?string $note = null
    ): ?array {
        $validMethods = ['gcash', 'bank_transfer', 'cash'];
        if (!in_array($method, $validMethods, true)) $method = 'gcash';

        return self::create([
            'partner_user_id' => $partnerUserId,
            'amount'          => round($amount, 2),
            'status'          => 'pending',
            'requested_date'  => date('Y-m-d'),
            'method'          => $method,
            'method_details'  => $details ? mb_substr($details, 0, 255) : null,
            'note'            => $note ? mb_substr($note, 0, 255) : null,
        ]);
    }

    public static function markApproved(
        string $id,
        ?string $referenceNo,
        ?string $actorId
    ): ?array {
        $p = self::find($id);
        if (!$p || $p['status'] !== 'pending') return $p;

        self::update($id, [
            'status'         => 'approved',
            'reference_no'   => $referenceNo ? mb_substr($referenceNo, 0, 80) : null,
            'processed_by'   => $actorId,
            'processed_date' => date('Y-m-d H:i:s'),
        ]);
        return self::find($id);
    }

    public static function reject(string $id, string $reason, ?string $actorId = null): ?array
    {
        $p = self::find($id);
        if (!$p || $p['status'] !== 'pending') return $p;

        self::update($id, [
            'status'         => 'rejected',
            'reject_reason'  => mb_substr($reason, 0, 255),
            'processed_by'   => $actorId,
            'processed_date' => date('Y-m-d H:i:s'),
        ]);
        return self::find($id);
    }

    /* =============================================================
     |  BATCH PROCESSING  (spec §1.5)
     ============================================================= */

    public static function createBatch(array $ids, ?string $actorId = null, ?string $notes = null): ?array
    {
        $pdo = Database::pdo();

        $cleanIds = [];
        $total    = 0.0;
        $methods  = [];

        foreach ($ids as $id) {
            $id = trim((string)$id);
            if ($id === '') continue;

            $p = self::find($id);
            if (!$p || $p['status'] !== 'pending') continue;

            $cleanIds[] = $id;
            $total += (float)$p['amount'];
            $methods[$p['method'] ?? 'gcash'] = true;
        }

        if (empty($cleanIds)) return null;

        $methodList  = array_keys($methods);
        $batchMethod = count($methodList) === 1 ? $methodList[0] : 'mixed';

        $batchId = Model::uuid();
        $pdo->prepare("
            INSERT INTO `payout_batches`
              (`id`, `processed_by`, `method`, `total_amount`, `request_count`,
               `status`, `notes`, `created_date`, `updated_date`)
            VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())
        ")->execute([
            $batchId,
            $actorId,
            $batchMethod,
            $total,
            count($cleanIds),
            $notes ? mb_substr($notes, 0, 255) : null,
        ]);

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $stmt = $pdo->prepare("
            UPDATE `payout_requests`
            SET    `batch_id` = ?
            WHERE  `id` IN ($placeholders)
              AND  `status` = 'pending'
        ");
        $stmt->execute(array_merge([$batchId], $cleanIds));

        return self::findBatch($batchId);
    }

    public static function processBatch(string $batchId, ?string $actorId = null): ?array
    {
        $pdo = Database::pdo();

        $batch = self::findBatch($batchId);
        if (!$batch) return null;

        $stmt = $pdo->prepare("
            SELECT * FROM `payout_requests`
            WHERE `batch_id` = ? AND `status` = 'pending'
        ");
        $stmt->execute([$batchId]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            $pdo->prepare("
                UPDATE `payout_batches` SET `status` = 'completed', `processed_date` = NOW()
                WHERE `id` = ?
            ")->execute([$batchId]);
            return self::findBatch($batchId);
        }

        foreach ($rows as $p) {
            self::update($p['id'], [
                'status'         => 'approved',
                'processed_by'   => $actorId,
                'processed_date' => date('Y-m-d H:i:s'),
            ]);
        }

        $pdo->prepare("
            UPDATE `payout_batches`
            SET    `status` = 'completed', `processed_date` = NOW(), `updated_date` = NOW()
            WHERE  `id` = ?
        ")->execute([$batchId]);

        return self::findBatch($batchId);
    }

    public static function findBatch(string $id): ?array
    {
        try {
            $stmt = Database::pdo()->prepare(
                'SELECT * FROM `payout_batches` WHERE `id` = ? LIMIT 1'
            );
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function listBatches(int $limit = 100): array
    {
        try {
            $stmt = Database::pdo()->prepare(
                "
                SELECT
                    pb.*,
                    u.`full_name` AS processed_by_name
                FROM `payout_batches` pb
                LEFT JOIN `users` u ON u.`id` = pb.`processed_by`
                ORDER BY pb.`created_date` DESC
                LIMIT " . max(1, min($limit, 500))
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function monthlyReconciliation(int $months = 12): array
    {
        $months = max(3, min($months, 24));

        try {
            $rows = Database::pdo()->query("
                SELECT
                    DATE_FORMAT(`processed_date`, '%Y-%m') AS ym,
                    COUNT(*) AS c,
                    COALESCE(SUM(`amount`), 0) AS total
                FROM `payout_requests`
                WHERE `status` = 'approved'
                  AND `processed_date` >= (CURDATE() - INTERVAL " . $months . " MONTH)
                GROUP BY ym
                ORDER BY ym ASC
            ")->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }

        return array_map(fn($r) => [
            'month' => $r['ym'],
            'count' => (int)$r['c'],
            'total' => (float)$r['total'],
        ], $rows);
    }
}
