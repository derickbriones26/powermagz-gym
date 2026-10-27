<?php

/**
 * Admin — Audit Log Tab (2026 UI)
 *
 * Self-loading tab: reads audit_logs directly so it doesn't impact
 * the dashboard() hot path for admins who never open this tab.
 *
 * Backs the compliance requirement (spec §2.1): "who did what, when, from where".
 */

use App\Config\Database;

/* ================================================================
 |  FILTERS
 |  ================================================================ */

$filters = [
    'from'   => trim((string)($_GET['a_from']   ?? '')),
    'to'     => trim((string)($_GET['a_to']     ?? '')),
    'user'   => trim((string)($_GET['a_user']   ?? '')),
    'role'   => trim((string)($_GET['a_role']   ?? '')),
    'action' => trim((string)($_GET['a_action'] ?? '')),
    'entity' => trim((string)($_GET['a_entity'] ?? '')),
    'q'      => trim((string)($_GET['a_q']      ?? '')),
];

$limit  = 200;
$offset = max(0, (int)($_GET['a_offset'] ?? 0));

/* ================================================================
 |  BUILD QUERY
 |  ================================================================ */

$where  = [];
$params = [];

if ($filters['from'] !== '') {
    $where[]  = '`created_date` >= ?';
    $params[] = $filters['from'] . ' 00:00:00';
}
if ($filters['to'] !== '') {
    $where[]  = '`created_date` <= ?';
    $params[] = $filters['to'] . ' 23:59:59';
}
if ($filters['user'] !== '') {
    $where[]  = '`user_id` = ?';
    $params[] = $filters['user'];
}
if ($filters['role'] !== '' && in_array($filters['role'], ['admin', 'staff', 'partner', 'user', 'system'], true)) {
    $where[]  = '`user_role` = ?';
    $params[] = $filters['role'];
}
if ($filters['action'] !== '') {
    $where[]  = '`action` LIKE ?';
    $params[] = $filters['action'] . '%';
}
if ($filters['entity'] !== '') {
    $where[]  = '`entity_type` = ?';
    $params[] = $filters['entity'];
}
if ($filters['q'] !== '') {
    $like     = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';
    $where[]  = '(`action` LIKE ? OR `entity_id` LIKE ? OR `notes` LIKE ? OR `ip_address` LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ---------- Load rows ---------- */
$logs = [];
$hasMore = false;
try {
    $pdo  = Database::pdo();
    $stmt = $pdo->prepare(
        "SELECT * FROM `audit_logs`
         {$whereSql}
         ORDER BY `created_date` DESC
         LIMIT " . ($limit + 1) . " OFFSET " . $offset
    );
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    $hasMore = count($logs) > $limit;
    if ($hasMore) array_pop($logs);
} catch (\Throwable $e) {
    error_log('[audit_tab] ' . $e->getMessage());
}

/* ---------- Total matching the current filter ---------- */
$totalCount = 0;
try {
    $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM `audit_logs` {$whereSql}");
    $stmt->execute($params);
    $totalCount = (int)$stmt->fetchColumn();
} catch (\Throwable $e) { /* silent */
}

/* ---------- Lookups for filter dropdowns ---------- */
$actorOptions = [];
try {
    $actorOptions = Database::pdo()->query("
        SELECT DISTINCT al.`user_id`, al.`user_role`, u.`full_name`, u.`email`
        FROM `audit_logs` al
        LEFT JOIN `users` u ON u.`id` = al.`user_id`
        WHERE al.`user_id` IS NOT NULL
        ORDER BY u.`full_name` ASC
        LIMIT 200
    ")->fetchAll();
} catch (\Throwable $e) { /* silent */
}

$actionOptions = [];
try {
    $actionOptions = Database::pdo()->query("
        SELECT DISTINCT SUBSTRING_INDEX(`action`, '.', 1) AS prefix
        FROM `audit_logs`
        WHERE `action` <> ''
        ORDER BY prefix ASC
    ")->fetchAll();
} catch (\Throwable $e) { /* silent */
}

$entityOptions = [];
try {
    $entityOptions = Database::pdo()->query("
        SELECT DISTINCT `entity_type`
        FROM `audit_logs`
        WHERE `entity_type` IS NOT NULL AND `entity_type` <> ''
        ORDER BY `entity_type` ASC
    ")->fetchAll();
} catch (\Throwable $e) { /* silent */
}

/* ---------- Stats ---------- */
$stats = function_exists('audit_stats')
    ? audit_stats()
    : ['total' => 0, 'today' => 0, 'by_role' => [], 'by_action' => []];

/* ================================================================
 |  HELPERS
 |  ================================================================ */

$actionTone = function (string $action): array {
    $lower = strtolower($action);

    if (
        str_ends_with($lower, '.failed') || str_ends_with($lower, '.rejected')
        || str_contains($lower, '.deleted') || str_contains($lower, '.purged')
    ) {
        return [
            'bg' => 'bg-red-500/10',
            'text' => 'text-red-500',
            'border' => 'border-red-500/20',
            'svg' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
        ];
    }
    if (
        str_ends_with($lower, '.expired') || str_ends_with($lower, '.paused')
        || str_contains($lower, '.disabled')
    ) {
        return [
            'bg' => 'bg-amber-500/10',
            'text' => 'text-amber-500',
            'border' => 'border-amber-500/20',
            'svg' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
        ];
    }
    if (
        str_contains($lower, '.created') || str_contains($lower, '.approved')
        || str_contains($lower, '.verified') || str_contains($lower, '.enabled')
        || str_contains($lower, '.completed')
    ) {
        return [
            'bg' => 'bg-green-500/10',
            'text' => 'text-green-500',
            'border' => 'border-green-500/20',
            'svg' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
        ];
    }
    if (
        str_contains($lower, '.updated') || str_contains($lower, '.changed')
        || str_contains($lower, '.extended') || str_contains($lower, '.resumed')
    ) {
        return [
            'bg' => 'bg-amber-500/10',
            'text' => 'text-amber-500',
            'border' => 'border-amber-500/20',
            'svg' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'
        ];
    }
    if (str_starts_with($lower, 'auth.')) {
        return [
            'bg' => 'bg-blue-500/10',
            'text' => 'text-blue-400',
            'border' => 'border-blue-500/20',
            'svg' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'
        ];
    }
    if (str_contains($lower, '.exported') || str_contains($lower, '.viewed')) {
        return [
            'bg' => 'bg-purple-500/10',
            'text' => 'text-purple-400',
            'border' => 'border-purple-500/20',
            'svg' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'
        ];
    }
    return [
        'bg' => 'bg-white/5',
        'text' => 'text-gray-400',
        'border' => 'border-white/10',
        'svg' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
    ];
};

$entityIcon = function (?string $type): string {
    return match ($type) {
        'program'         => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'user'            => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'member'          => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'enrollment'      => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        'payment'         => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        'attendance'      => 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z',
        'partner_profile' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'partner_sale'    => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        'payout_request'  => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        'payout_batch'    => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'b2b_client'      => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'system'          => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        default           => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    };
};

$roleTone = function (string $role): array {
    return match ($role) {
        'admin'   => [
            'bg' => 'bg-red-500/10',
            'text' => 'text-red-500',
            'border' => 'border-red-500/20',
            'svg' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
        ],
        'staff'   => [
            'bg' => 'bg-purple-500/10',
            'text' => 'text-purple-400',
            'border' => 'border-purple-500/20',
            'svg' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
        ],
        'partner' => [
            'bg' => 'bg-[#00A6A6]/10',
            'text' => 'text-[#00A6A6]',
            'border' => 'border-[#00A6A6]/20',
            'svg' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
        ],
        'user'    => [
            'bg' => 'bg-white/5',
            'text' => 'text-gray-400',
            'border' => 'border-white/10',
            'svg' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
        ],
        'system'  => [
            'bg' => 'bg-gray-500/10',
            'text' => 'text-gray-400',
            'border' => 'border-gray-500/20',
            'svg' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'
        ],
        default   => [
            'bg' => 'bg-gray-500/10',
            'text' => 'text-gray-400',
            'border' => 'border-gray-500/20',
            'svg' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
        ],
    };
};

$humanTime = function (string $ts): string {
    $diff = time() - strtotime($ts);
    if ($diff < 60)         return 'just now';
    if ($diff < 3600)       return floor($diff / 60) . ' min ago';
    if ($diff < 86400)      return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800)     return floor($diff / 86400) . ' days ago';
    return date('M j, Y', strtotime($ts));
};

$qs = function (array $overrides = []) use ($filters, $offset): string {
    $merged = array_merge([
        'tab'      => 'audit',
        'a_from'   => $filters['from'],
        'a_to'     => $filters['to'],
        'a_user'   => $filters['user'],
        'a_role'   => $filters['role'],
        'a_action' => $filters['action'],
        'a_entity' => $filters['entity'],
        'a_q'      => $filters['q'],
        'a_offset' => $offset > 0 ? (string)$offset : '',
    ], $overrides);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== '0');
    return $merged ? '?' . http_build_query($merged) : '?tab=audit';
};

$hasFilters = $filters['from'] !== '' || $filters['to'] !== '' || $filters['user'] !== ''
    || $filters['role'] !== '' || $filters['action'] !== '' || $filters['entity'] !== ''
    || $filters['q'] !== '';
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                <svg class="w-3.5 h-3.5 text-[#00A6A6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Compliance</span>
            </div>
            <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                AUDIT LOG
            </h1>
            <p class="mt-2 max-w-2xl text-sm text-gray-400 sm:text-base">
                Every write action recorded with actor, IP, timestamp, and before/after diff. Retained for 365 days per spec §2.1.
            </p>
        </div>
    </div>

    <!-- ============================================================
         KPI STRIP
         ============================================================ -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Total -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Events</div>
                    <div class="mt-2 font-heading text-4xl text-[#00A6A6]"><?= number_format((int)($stats['total'] ?? 0)) ?></div>
                    <div class="mt-1 text-xs text-gray-400">All time</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today -->
        <div class="group relative overflow-hidden rounded-2xl border border-green-500/20 bg-green-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-green-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-green-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-green-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        Today
                    </div>
                    <div class="mt-2 font-heading text-4xl text-green-500"><?= number_format((int)($stats['today'] ?? 0)) ?></div>
                    <div class="mt-1 text-xs text-gray-400">Events since midnight</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-green-500/10 text-green-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Top Actor Role -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Top Actor Role</div>
                    <?php
                    $byRole = $stats['by_role'] ?? [];
                    arsort($byRole);
                    $topRole = array_key_first($byRole);
                    $topRoleCount = $topRole ? (int)$byRole[$topRole] : 0;
                    $roleBadge = $topRole ? $roleTone($topRole) : null;
                    ?>
                    <div class="mt-2 flex items-baseline gap-2">
                        <?php if ($roleBadge): ?>
                            <span class="font-heading text-2xl <?= $roleBadge['text'] ?>">
                                <?= e(ucfirst($topRole)) ?>
                            </span>
                        <?php else: ?>
                            <span class="font-heading text-2xl text-gray-600">—</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= number_format($topRoleCount) ?> event<?= $topRoleCount !== 1 ? 's' : '' ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-gray-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Retention -->
        <div class="group relative overflow-hidden rounded-2xl border border-amber-500/20 bg-amber-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-amber-500">Retention</div>
                    <div class="mt-2 font-heading text-4xl text-amber-500">365d</div>
                    <div class="mt-1 text-xs text-gray-400">Spec §2.1 minimum</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         FILTER BAR
         ============================================================ -->
    <form method="get" action="<?= url_e('/admin-dashboard') ?>"
        class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm space-y-4">

        <input type="hidden" name="tab" value="audit">

        <?= a11y_filter_open('Filter audit log by date, actor, role, action, entity, and search') ?>

        <!-- Row 1: date + actor + role -->
        <div class="grid gap-3 lg:grid-cols-4">
            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">From</span>
                <input type="date" name="a_from" value="<?= e($filters['from']) ?>"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">To</span>
                <input type="date" name="a_to" value="<?= e($filters['to']) ?>"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Actor</span>
                <select name="a_user"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <option value="">All actors</option>
                    <?php foreach ($actorOptions as $opt):
                        $label = $opt['full_name'] ?: $opt['email'] ?: 'Unknown';
                    ?>
                        <option value="<?= e($opt['user_id']) ?>" <?= $filters['user'] === $opt['user_id'] ? 'selected' : '' ?>>
                            <?= e($label) ?> (<?= e($opt['user_role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Role</span>
                <select name="a_role"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <option value="">All roles</option>
                    <?php foreach (['admin', 'staff', 'partner', 'user', 'system'] as $r): ?>
                        <option value="<?= $r ?>" <?= $filters['role'] === $r ? 'selected' : '' ?>>
                            <?= e(ucfirst($r)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <!-- Row 2: action + entity + search + buttons -->
        <div class="grid gap-3 lg:grid-cols-[1fr_1fr_2fr_auto]">
            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Action</span>
                <select name="a_action"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <option value="">All actions</option>
                    <?php foreach ($actionOptions as $opt): ?>
                        <option value="<?= e($opt['prefix']) ?>" <?= $filters['action'] === $opt['prefix'] ? 'selected' : '' ?>>
                            <?= e($opt['prefix']) ?>.*
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Entity</span>
                <select name="a_entity"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <option value="">All entities</option>
                    <?php foreach ($entityOptions as $opt): ?>
                        <option value="<?= e($opt['entity_type']) ?>" <?= $filters['entity'] === $opt['entity_type'] ? 'selected' : '' ?>>
                            <?= e(ucfirst(str_replace('_', ' ', $opt['entity_type']))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Search</span>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="a_q" value="<?= e($filters['q']) ?>"
                        placeholder="Action, entity ID, note, IP…"
                        class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] pl-11 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </div>
            </label>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="tap-effect inline-flex h-11 items-center gap-2 rounded-xl bg-[#00A6A6] px-5 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Apply
                </button>
                <a href="<?= url_e('/admin-dashboard/audit/export' . $qs(['a_offset' => ''])) ?>"
                    class="tap-effect inline-flex h-11 items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white"
                    download>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV
                </a>
            </div>
        </div>

        <?= a11y_filter_close() ?>

        <!-- Result summary -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-white/5 text-xs">
            <span class="text-gray-400" <?= a11y_live_region('polite') ?>>
                <?php if ($hasFilters): ?>
                    <strong class="text-white"><?= number_format($totalCount) ?></strong> matching event<?= $totalCount !== 1 ? 's' : '' ?> · showing <?= number_format(count($logs)) ?>
                <?php else: ?>
                    Showing the <strong class="text-white"><?= count($logs) ?></strong> most recent events
                <?php endif; ?>
            </span>

            <?php if ($hasFilters): ?>
                <a href="<?= url_e('/admin-dashboard?tab=audit') ?>"
                    class="font-bold text-[#00A6A6] transition-colors hover:text-white">
                    Clear filters
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- ============================================================
         LOG TABLE
         ============================================================ -->
    <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <caption class="sr-only">Audit log entries with actor, action, entity, IP address, and timestamp</caption>
                <thead>
                    <tr class="border-b border-white/5 bg-white/[0.02]">
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">When</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Actor</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Action</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Entity</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden xl:table-cell">Details</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">IP</th>
                        <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Diff</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $idx => $log):
                        $tone = $actionTone($log['action'] ?? '');
                        $rTone = $roleTone($log['user_role'] ?? 'system');
                        $entity = $log['entity_type'] ?? null;
                        $entityId = $log['entity_id'] ?? null;

                        $shortEntityId = $entityId ? substr($entityId, 0, 8) . '…' : null;

                        $hasBefore = !empty($log['before_data']) && $log['before_data'] !== 'null';
                        $hasAfter  = !empty($log['after_data'])  && $log['after_data']  !== 'null';
                        $hasDiff   = $hasBefore || $hasAfter;
                    ?>
                        <tr class="group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                            style="animation: row-enter 0.3s ease-out <?= min($idx * 15, 300) ?>ms backwards;">

                            <td class="p-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-white">
                                    <?= e(date('M j, g:i A', strtotime($log['created_date']))) ?>
                                </div>
                                <div class="text-[10px] text-gray-500" title="<?= e($log['created_date']) ?>">
                                    <?= e($humanTime($log['created_date'])) ?>
                                </div>
                            </td>

                            <td class="p-4">
                                <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full border <?= $rTone['bg'] ?> <?= $rTone['text'] ?> <?= $rTone['border'] ?> px-2.5 py-1 text-[11px] font-bold uppercase">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $rTone['svg'] ?>" />
                                    </svg>
                                    <span><?= e($log['user_role'] ?? 'system') ?></span>
                                </span>
                                <?php if (!empty($log['user_id'])): ?>
                                    <div class="mt-1 font-mono text-[10px] text-gray-500">
                                        <?= e(substr($log['user_id'], 0, 8)) ?>…
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td class="p-4">
                                <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full border <?= $tone['bg'] ?> <?= $tone['text'] ?> <?= $tone['border'] ?> px-2.5 py-1 text-[11px] font-bold">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $tone['svg'] ?>" />
                                    </svg>
                                    <span><?= e($log['action']) ?></span>
                                </span>
                            </td>

                            <td class="p-4 hidden lg:table-cell">
                                <?php if ($entity): ?>
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-white/5 text-gray-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $entityIcon($entity) ?>" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate text-xs font-bold uppercase text-gray-300">
                                                <?= e($entity) ?>
                                            </div>
                                            <?php if ($shortEntityId): ?>
                                                <code class="text-[10px] text-gray-500" title="<?= e($entityId) ?>">
                                                    <?= e($shortEntityId) ?>
                                                </code>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-gray-600">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="p-4 max-w-xs hidden xl:table-cell">
                                <?php if (!empty($log['notes'])): ?>
                                    <div class="truncate text-xs text-gray-300" title="<?= e($log['notes']) ?>">
                                        <?= e($log['notes']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-gray-600">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="p-4 whitespace-nowrap hidden lg:table-cell">
                                <code class="text-[11px] text-gray-400">
                                    <?= e($log['ip_address'] ?? '—') ?>
                                </code>
                            </td>

                            <td class="p-4 text-right">
                                <?php if ($hasDiff): ?>
                                    <button type="button"
                                        onclick='openAuditDiffModal(<?= json_encode([
                                                                        "id"     => $log["id"],
                                                                        "action" => $log["action"],
                                                                        "when"   => $log["created_date"],
                                                                        "before" => $log["before_data"],
                                                                        "after"  => $log["after_data"],
                                                                        "notes"  => $log["notes"],
                                                                    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                        aria-label="View before and after data for <?= e($log['action']) ?>"
                                        class="tap-effect inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[10px] font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>View</span>
                                    </button>
                                <?php else: ?>
                                    <span class="text-[10px] text-gray-600">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="p-16 text-center">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                </div>
                                <p class="mt-4 text-sm text-gray-400">
                                    <?= $hasFilters ? 'No audit entries match your filters.' : 'No audit entries yet.' ?>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($offset > 0 || $hasMore): ?>
            <nav aria-label="Audit log pagination"
                class="flex items-center justify-between border-t border-white/5 px-6 py-4">
                <div class="text-xs text-gray-400">
                    Showing rows <?= number_format($offset + 1) ?>–<?= number_format($offset + count($logs)) ?>
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($offset > 0): ?>
                        <a href="<?= url_e('/admin-dashboard' . $qs(['a_offset' => (string)max(0, $offset - $limit)])) ?>"
                            class="tap-effect rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white">
                            Newer
                        </a>
                    <?php endif; ?>

                    <?php if ($hasMore): ?>
                        <a href="<?= url_e('/admin-dashboard' . $qs(['a_offset' => (string)($offset + $limit)])) ?>"
                            class="tap-effect rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white">
                            Older
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    </div>

    <!-- ============================================================
         RETENTION / PURGE
         ============================================================ -->
    <div class="rounded-2xl border border-amber-500/20 bg-gradient-to-r from-amber-500/5 to-transparent p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-bold text-white">Log Retention Policy</div>
                    <div class="mt-1 text-xs text-gray-400">
                        Spec §2.1 requires retaining audit logs for at least <strong class="text-amber-500">1 year (365 days)</strong>.
                        Purge only runs on explicit confirmation.
                    </div>
                </div>
            </div>

            <button type="button" onclick="openAuditPurgeModal()"
                class="tap-effect inline-flex flex-shrink-0 items-center gap-2 rounded-full bg-red-500 px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(213,0,0,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-600 hover:shadow-[0_0_25px_rgba(213,0,0,0.5)]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Purge Old Logs
            </button>
        </div>
    </div>

    <!-- ============================================================
         AUDIT DIFF MODAL
         ============================================================ -->
    <div id="auditDiffModal" role="dialog" aria-modal="true" aria-labelledby="auditDiffModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-3xl max-h-[92vh] overflow-y-auto rounded-3xl border border-white/10 bg-[#111] shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-white/5 bg-[#111] p-6">
                <div class="min-w-0">
                    <h2 id="auditDiffModalTitle" class="font-heading text-2xl tracking-wide text-white">AUDIT ENTRY</h2>
                    <p id="adm-action" class="mt-1 truncate text-xs font-mono text-gray-400">—</p>
                    <p id="adm-when" class="mt-0.5 truncate text-[11px] text-gray-500">—</p>
                </div>
                <button type="button" onclick="closeAuditDiffModal()"
                    aria-label="Close audit entry dialog"
                    class="flex-shrink-0 rounded-full p-2 text-gray-400 transition-colors hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div id="adm-notes-wrap" class="hidden rounded-xl border border-[#00A6A6]/20 bg-[#00A6A6]/5 p-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-[#00A6A6]">Note</div>
                    <div id="adm-notes" class="mt-1 text-sm text-gray-200">—</div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-red-500/10 text-red-500">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                </svg>
                            </div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Before</div>
                        </div>
                        <pre id="adm-before"
                            aria-label="Before data"
                            class="max-h-96 overflow-auto rounded-xl border border-white/10 bg-[#0a0a0a] p-4 font-mono text-[11px] leading-relaxed text-green-400"
                            style="white-space: pre-wrap; word-break: break-word;"></pre>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500/10 text-green-500">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                </svg>
                            </div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">After</div>
                        </div>
                        <pre id="adm-after"
                            aria-label="After data"
                            class="max-h-96 overflow-auto rounded-xl border border-white/10 bg-[#0a0a0a] p-4 font-mono text-[11px] leading-relaxed text-green-400"
                            style="white-space: pre-wrap; word-break: break-word;"></pre>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-white/5 p-4">
                <button type="button" onclick="copyAuditJSON()"
                    class="tap-effect rounded-full border border-white/10 bg-white/5 px-5 py-2.5 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white">
                    <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Copy JSON
                </button>
                <button type="button" onclick="closeAuditDiffModal()"
                    class="tap-effect rounded-full bg-[#00A6A6] px-5 py-2.5 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================
         AUDIT PURGE MODAL
         ============================================================ -->
    <div id="auditPurgeModal" role="dialog" aria-modal="true" aria-labelledby="auditPurgeModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-red-500/20 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 id="auditPurgeModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">PURGE AUDIT LOGS?</h2>
                <p class="mt-2 text-sm text-gray-400">
                    This permanently deletes audit entries older than the chosen age.
                    Spec §2.1 requires retaining at least <strong class="text-white">365 days</strong>.
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/audit/purge') ?>" class="mt-6 space-y-4" data-validate>
                <?= csrf_field() ?>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Delete entries older than
                    </span>
                    <select name="days" required
                        data-validate="required"
                        data-error-label="Retention days"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-red-500 focus:ring-2 focus:ring-red-500/20">
                        <option value="365" selected>365 days (1 year — spec minimum)</option>
                        <option value="730">730 days (2 years)</option>
                        <option value="1095">1095 days (3 years)</option>
                    </select>
                    <span class="mt-2 block text-[11px] text-gray-500">
                        Anything newer than the threshold is preserved.
                    </span>
                </label>

                <div class="rounded-xl border border-red-500/20 bg-red-500/5 p-4">
                    <div class="text-xs leading-relaxed text-red-200/80">
                        <strong class="text-red-500">This cannot be undone.</strong> Deleted log rows are not recoverable. Export first if you need a copy.
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeAuditPurgeModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-red-500 px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(213,0,0,0.3)] transition-all hover:-translate-y-0.5 hover:bg-red-600">
                        Purge Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes row-enter {
        from {
            opacity: 0;
            transform: translateX(-6px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes modal-pop {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @media (prefers-reduced-motion: reduce) {

        tr,
        #auditDiffModal>div,
        #auditPurgeModal>div {
            animation: none !important;
        }
    }
</style>

<script>
    (function() {
        'use strict';

        /* ============ Pretty-print JSON ============ */
        function prettyJSON(raw) {
            if (!raw || raw === 'null') return '—';
            try {
                const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;
                return JSON.stringify(parsed, null, 2);
            } catch (e) {
                return String(raw);
            }
        }

        function trackTrigger() {
            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }
        }

        /* ============ Diff modal ============ */
        window.openAuditDiffModal = function(log) {
            if (!log) return;
            trackTrigger();

            document.getElementById('adm-action').textContent = log.action || '—';
            document.getElementById('adm-when').textContent = log.when || '—';

            const notesWrap = document.getElementById('adm-notes-wrap');
            if (log.notes) {
                notesWrap.classList.remove('hidden');
                document.getElementById('adm-notes').textContent = log.notes;
            } else {
                notesWrap.classList.add('hidden');
            }

            document.getElementById('adm-before').textContent = prettyJSON(log.before);
            document.getElementById('adm-after').textContent = prettyJSON(log.after);

            window._currentAudit = log;

            document.getElementById('auditDiffModal').classList.replace('hidden', 'flex');
        };

        window.closeAuditDiffModal = function() {
            document.getElementById('auditDiffModal').classList.replace('flex', 'hidden');
        };

        /* ============ Copy JSON ============ */
        window.copyAuditJSON = function() {
            const log = window._currentAudit;
            if (!log) return;

            const payload = {
                action: log.action,
                when: log.when,
                before: prettyJSON(log.before),
                after: prettyJSON(log.after),
                notes: log.notes || null,
            };

            const text = JSON.stringify(payload, null, 2);

            const done = () => {
                if (window.PMGZ_A11Y && window.PMGZ_A11Y.announce) {
                    window.PMGZ_A11Y.announce('Audit entry copied to clipboard');
                }
                showToast('Copied to clipboard');
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done).catch(() => {
                    fallbackCopy(text);
                });
            } else {
                fallbackCopy(text);
            }
        };

        function fallbackCopy(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                showToast('Copied to clipboard');
            } catch (e) {
                window.prompt('Copy manually:', text);
            }
            document.body.removeChild(ta);
        }

        /* ============ Purge modal ============ */
        window.openAuditPurgeModal = function() {
            trackTrigger();
            document.getElementById('auditPurgeModal').classList.replace('hidden', 'flex');
        };
        window.closeAuditPurgeModal = function() {
            document.getElementById('auditPurgeModal').classList.replace('flex', 'hidden');
        };

        /* ============ Toast ============ */
        function showToast(msg) {
            const existing = document.getElementById('audit-toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.id = 'audit-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            toast.className = 'fixed bottom-6 left-1/2 z-[70] -translate-x-1/2 rounded-full bg-black px-5 py-2.5 text-xs font-bold text-white shadow-2xl';
            toast.style.animation = 'toast-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
            toast.textContent = msg;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 350);
            }, 2200);
        }

        /* ============ Backdrop close ============ */
        const MODALS = [
            ['auditDiffModal', 'closeAuditDiffModal'],
            ['auditPurgeModal', 'closeAuditPurgeModal'],
        ];

        MODALS.forEach(([id, fn]) => {
            document.getElementById(id)?.addEventListener('click', (e) => {
                if (e.target.id === id) window[fn]();
            });
        });
    })();
</script>

<style>
    @keyframes toast-in {
        from {
            opacity: 0;
            transform: translate(-50%, 20px);
        }

        to {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    }
</style>