<?php

/**
 * PowerMagz — Audit Logger  (spec §2.1)
 *
 * Global helper for recording admin/staff actions.
 * Fails silently — a logging failure must never block a user action.
 *
 * Companion to the private `AdminController::audit()` method.
 * Any controller may call `audit_log(...)` directly.
 */

use App\Config\Database;

if (!function_exists('audit_log')) {

    /**
     * Record an action in the `audit_logs` table.
     *
     * @param string      $action      Verb slug, e.g. "program.created"
     * @param string|null $entityType  e.g. "program"
     * @param string|null $entityId    UUID of the target row
     * @param array|null  $before      Snapshot before the change
     * @param array|null  $after       Snapshot after the change
     * @param string|null $notes       Free-form note
     * @param string|null $actorId     Override actor (rare — defaults to current user)
     * @param string|null $actorRole   Override role  (rare — defaults to current user)
     */
    function audit_log(
        string $action,
        ?string $entityType = null,
        ?string $entityId   = null,
        ?array  $before     = null,
        ?array  $after      = null,
        ?string $notes      = null,
        ?string $actorId    = null,
        ?string $actorRole  = null
    ): void {
        try {
            // Actor: prefer explicit overrides, else current user, else system
            if ($actorId === null || $actorRole === null) {
                $user = function_exists('current_user') ? current_user() : null;
                $actorId   = $actorId   ?? ($user['id']   ?? null);
                $actorRole = $actorRole ?? ($user['role'] ?? 'system');
            }

            Database::pdo()
                ->prepare(
                    'INSERT INTO `audit_logs`
                       (`user_id`, `user_role`, `action`, `entity_type`, `entity_id`,
                        `before_data`, `after_data`, `ip_address`, `user_agent`, `notes`)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )
                ->execute([
                    $actorId,
                    $actorRole,
                    mb_substr($action, 0, 100),
                    $entityType ? mb_substr($entityType, 0, 50) : null,
                    $entityId,
                    $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
                    $after  !== null ? json_encode($after,  JSON_UNESCAPED_UNICODE) : null,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                    $notes ? mb_substr($notes, 0, 255) : null,
                ]);
        } catch (\Throwable $e) {
            error_log('[audit_log] ' . $e->getMessage());
        }
    }

    /**
     * Fetch the newest audit entries — for the Audit tab viewer.
     *
     * @return array<int,array<string,mixed>>
     */
    function audit_recent(int $limit = 100, ?string $entityType = null, ?string $actionLike = null): array
    {
        try {
            $sql    = 'SELECT * FROM `audit_logs`';
            $where  = [];
            $params = [];

            if ($entityType !== null && $entityType !== '') {
                $where[]  = '`entity_type` = ?';
                $params[] = $entityType;
            }
            if ($actionLike !== null && $actionLike !== '') {
                $where[]  = '`action` LIKE ?';
                $params[] = $actionLike . '%';
            }
            if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);

            $sql .= ' ORDER BY `created_date` DESC LIMIT ' . max(1, min($limit, 1000));

            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('[audit_recent] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retention: delete logs older than N days (default 365 = 1 year,
     * per spec §2.1). Returns count deleted.
     */
    function audit_purge_old(int $days = 365): int
    {
        try {
            $stmt = Database::pdo()->prepare(
                'DELETE FROM `audit_logs` WHERE `created_date` < (NOW() - INTERVAL ? DAY)'
            );
            $stmt->execute([$days]);
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            error_log('[audit_purge_old] ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Aggregate counter — used by the Audit tab KPIs.
     *
     * @return array{total:int, today:int, by_role:array<string,int>, by_action:array<string,int>}
     */
    function audit_stats(): array
    {
        $stats = [
            'total'     => 0,
            'today'     => 0,
            'by_role'   => [],
            'by_action' => [],
        ];

        try {
            $pdo = Database::pdo();

            $stats['total'] = (int)$pdo
                ->query('SELECT COUNT(*) FROM `audit_logs`')
                ->fetchColumn();

            $stats['today'] = (int)$pdo
                ->query('SELECT COUNT(*) FROM `audit_logs` WHERE DATE(`created_date`) = CURDATE()')
                ->fetchColumn();

            foreach (
                $pdo->query(
                    'SELECT `user_role`, COUNT(*) AS c FROM `audit_logs` GROUP BY `user_role`'
                )->fetchAll() as $r
            ) {
                $stats['by_role'][$r['user_role']] = (int)$r['c'];
            }

            foreach (
                $pdo->query(
                    'SELECT SUBSTRING_INDEX(`action`, ".", 1) AS grp, COUNT(*) AS c
                   FROM `audit_logs` GROUP BY grp ORDER BY c DESC LIMIT 20'
                )->fetchAll() as $r
            ) {
                $stats['by_action'][$r['grp']] = (int)$r['c'];
            }
        } catch (\Throwable $e) {
            error_log('[audit_stats] ' . $e->getMessage());
        }

        return $stats;
    }
}
