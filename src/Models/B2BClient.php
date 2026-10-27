<?php

namespace App\Models;

use App\Core\Model;
use App\Config\Database;

class B2BClient extends Model
{
    protected static string $table = 'b2b_clients';

    protected static array $fillable = [
        'id',
        'partner_user_id',
        'client_user_id',
        'client_name',
        'client_email',
        'membership_status',
        'last_check_in',
        'contact_phone',
        'notes',
        'invited_at',
        'linked_at',
    ];

    /* =============================================================
     |  LISTING  (spec §1.5)
     |  Admin-wide client view with partner + last-checkin info.
     ============================================================= */

    public static function listWithFilters(array $filters = []): array
    {
        $pdo    = Database::pdo();
        $where  = [];
        $params = [];

        $partnerId = $filters['partner_user_id'] ?? '';
        if ($partnerId !== '') {
            $where[]  = 'bc.`partner_user_id` = ?';
            $params[] = $partnerId;
        }

        $status = $filters['status'] ?? '';
        if (
            $status !== '' && $status !== 'all'
            && in_array($status, ['linked', 'pending', 'inactive'], true)
        ) {
            $where[]  = 'bc.`membership_status` = ?';
            $params[] = $status;
        }

        if (!empty($filters['search'])) {
            $q = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']) . '%';
            $where[]  = '(bc.`client_name` LIKE ? OR bc.`client_email` LIKE ?
                          OR pp.`business_name` LIKE ?)';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        $limit  = max(1, min((int)($filters['limit']  ?? 500), 5000));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = "
            SELECT
                bc.*,
                pp.`business_name`,
                pp.`partner_type`,
                pp.`referral_code`,
                u.`email` AS partner_email,
                cu.`full_name` AS client_user_name,
                cu.`email` AS client_user_email,
                (SELECT MAX(`date`) FROM `attendance`
                 WHERE `user_id` = bc.`client_user_id`) AS recent_checkin
            FROM `b2b_clients` bc
            LEFT JOIN `partner_profiles` pp ON pp.`user_id` = bc.`partner_user_id`
            LEFT JOIN `users` u             ON u.`id`      = bc.`partner_user_id`
            LEFT JOIN `users` cu            ON cu.`id`     = bc.`client_user_id`
        ";

        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY bc.`created_date` DESC';
        $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('[B2BClient::listWithFilters] ' . $e->getMessage());
            return [];
        }
    }

    public static function summary(): array
    {
        $summary = ['total' => 0, 'linked' => 0, 'pending' => 0, 'inactive' => 0];
        try {
            foreach (
                Database::pdo()->query("
                SELECT `membership_status`, COUNT(*) AS c
                FROM `b2b_clients` GROUP BY `membership_status`
            ")->fetchAll() as $r
            ) {
                $key = $r['membership_status'];
                if (isset($summary[$key])) $summary[$key] = (int)$r['c'];
                $summary['total'] += (int)$r['c'];
            }
        } catch (\Throwable $e) {
            error_log('[B2BClient::summary] ' . $e->getMessage());
        }
        return $summary;
    }

    public static function countsByPartner(): array
    {
        try {
            $rows = Database::pdo()->query("
                SELECT
                    pp.`user_id`,
                    pp.`business_name`,
                    COUNT(bc.`id`) AS clients,
                    SUM(bc.`membership_status` = 'linked') AS linked
                FROM `partner_profiles` pp
                LEFT JOIN `b2b_clients` bc ON bc.`partner_user_id` = pp.`user_id`
                GROUP BY pp.`user_id`, pp.`business_name`
                ORDER BY clients DESC
                LIMIT 100
            ")->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }

        return array_map(fn($r) => [
            'user_id'       => $r['user_id'],
            'business_name' => $r['business_name'],
            'clients'       => (int)$r['clients'],
            'linked'        => (int)$r['linked'],
        ], $rows);
    }

    /* =============================================================
     |  MUTATIONS
     ============================================================= */

    public static function updateClient(string $id, array $data): ?array
    {
        $c = self::find($id);
        if (!$c) return null;

        $payload = [];

        if (array_key_exists('client_name', $data))   $payload['client_name']  = trim((string)$data['client_name']);
        if (array_key_exists('client_email', $data))  $payload['client_email'] = trim((string)$data['client_email']);
        if (array_key_exists('contact_phone', $data)) $payload['contact_phone'] = $data['contact_phone'];
        if (array_key_exists('notes', $data))         $payload['notes']        = $data['notes'];

        if (
            array_key_exists('membership_status', $data)
            && in_array($data['membership_status'], ['linked', 'pending', 'inactive'], true)
        ) {
            $payload['membership_status'] = $data['membership_status'];

            if ($data['membership_status'] === 'linked' && empty($c['linked_at'])) {
                $payload['linked_at'] = date('Y-m-d H:i:s');
            }
        }

        if (!$payload) return $c;
        return self::update($id, $payload);
    }

    public static function linkToUser(string $id, string $userId): ?array
    {
        $c = self::find($id);
        if (!$c) return null;

        return self::update($id, [
            'client_user_id'    => $userId,
            'membership_status' => 'linked',
            'linked_at'         => date('Y-m-d H:i:s'),
        ]);
    }
}
