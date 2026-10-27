<?php

/**
 * Partner — My Clients (2026 UI)
 * Table of clients + add/message modals + search
 */

$clients = $clients ?? [];

// ---------- Sort newest first ----------
usort($clients, fn($a, $b) => strcmp($b['created_date'] ?? '', $a['created_date'] ?? ''));

// ---------- Status counts ----------
$statusCounts = ['linked' => 0, 'pending' => 0, 'inactive' => 0];
foreach ($clients as $c) {
    $s = $c['membership_status'] ?? 'pending';
    if (isset($statusCounts[$s])) $statusCounts[$s]++;
}
$totalClients = count($clients);
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ========== Page Header ========== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                    <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Partner Portal</span>
                </div>
                <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                    MY CLIENTS
                </h1>
                <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                    Everyone you've linked to your account. Add new clients or message them directly.
                </p>
            </div>

            <button type="button" onclick="openAddClientModal()"
                class="tap-effect group inline-flex flex-shrink-0 items-center gap-2 self-start rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)] sm:self-auto">
                <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span>Add Client</span>
            </button>
        </div>
    </div>

    <!-- ========== Stats Grid ========== -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Total Clients -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Clients</div>
                    <div class="mt-2 font-heading text-4xl text-white"><?= $totalClients ?></div>
                    <div class="mt-1 text-xs text-gray-400">All time</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6]/10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Linked -->
        <div class="group relative overflow-hidden rounded-2xl border border-green-500/20 bg-green-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-green-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-green-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-green-500">
                        <?php if ($statusCounts['linked'] > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        <?php endif; ?>
                        Linked
                    </div>
                    <div class="mt-2 font-heading text-4xl text-green-500"><?= $statusCounts['linked'] ?></div>
                    <div class="mt-1 text-xs text-gray-400">Active clients</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-green-500/10 text-green-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="group relative overflow-hidden rounded-2xl border border-amber-500/20 bg-amber-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-amber-500">
                        <?php if ($statusCounts['pending'] > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <?php endif; ?>
                        Pending
                    </div>
                    <div class="mt-2 font-heading text-4xl text-amber-500"><?= $statusCounts['pending'] ?></div>
                    <div class="mt-1 text-xs text-gray-400">Awaiting signup</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Inactive -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-gray-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-gray-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Inactive</div>
                    <div class="mt-2 font-heading text-4xl text-gray-400"><?= $statusCounts['inactive'] ?></div>
                    <div class="mt-1 text-xs text-gray-400">Archived clients</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-gray-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== Filter Bar ========== -->
    <?php if (!empty($clients)): ?>
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-white/5 bg-[#111] p-4 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-widest text-gray-500">Filter:</span>

            <?php
            $filters = [
                ['all',      'All',      $totalClients,           'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['linked',   'Linked',   $statusCounts['linked'], 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['pending',  'Pending',  $statusCounts['pending'], 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['inactive', 'Inactive', $statusCounts['inactive'], 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z'],
            ];
            foreach ($filters as [$key, $label, $count, $icon]):
            ?>
                <button type="button"
                    data-filter="<?= $key ?>"
                    onclick="setClientFilter('<?= $key ?>')"
                    class="client-filter tap-effect flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-gray-300 transition-all duration-300 hover:border-[#00A6A6]/50 hover:text-white
                       <?= $key === 'all' ? 'active' : '' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                    </svg>
                    <span><?= $label ?></span>
                    <span class="rounded-full bg-black/30 px-2 py-0.5 text-[10px] font-bold text-gray-400"><?= $count ?></span>
                </button>
            <?php endforeach; ?>

            <div class="relative ml-auto w-full sm:w-64">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input id="clientSearch" type="search" placeholder="Search name or email…"
                    class="h-10 w-full rounded-full border border-white/10 bg-[#0a0a0a] pl-10 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </div>
        </div>
    <?php endif; ?>

    <!-- ========== Clients Table ========== -->
    <?php if (empty($clients)): ?>
        <div class="rounded-2xl border border-white/5 bg-[#111] p-16 text-center shadow-sm">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <h3 class="mt-6 font-heading text-2xl tracking-wide text-white">NO CLIENTS YET</h3>
            <p class="mt-2 max-w-md mx-auto text-sm text-gray-400">
                Add your first client or share your referral link — new signups via your link are linked automatically.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <button type="button" onclick="openAddClientModal()"
                    class="tap-effect inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>Add Client</span>
                </button>
                <a href="<?= url_e('/partner-dashboard/sales') ?>"
                    class="tap-effect inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-6 py-3 text-sm font-bold text-white transition-all duration-300 hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-white/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    <span>Get Referral Link</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-white/5 bg-white/[0.02]">
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Client</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Last Check-in</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Added</th>
                            <th class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="clientRows">
                        <?php foreach ($clients as $idx => $c):
                            $status = $c['membership_status'] ?? 'pending';
                            $statusStyle = [
                                'linked'   => 'bg-green-500/10 text-green-500 border border-green-500/20',
                                'pending'  => 'bg-amber-500/10 text-amber-500 border border-amber-500/20',
                                'inactive' => 'bg-gray-500/10 text-gray-400 border border-gray-500/20',
                            ][$status] ?? 'bg-gray-500/10 text-gray-400 border border-gray-500/20';

                            // SVG status icons
                            $statusIcon = [
                                'linked'   => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                                'pending'  => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                                'inactive' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>',
                            ][$status] ?? '';

                            $clientName = $c['client_name'] ?? 'Unknown';
                            $clientEmail = $c['client_email'] ?? '';
                            $lastCheckIn = $c['last_check_in'] ?? null;
                        ?>
                            <tr class="client-row group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                                data-status="<?= e($status) ?>"
                                data-search="<?= e(strtolower($clientName . ' ' . $clientEmail)) ?>"
                                style="animation: row-enter 0.3s ease-out <?= min($idx * 30, 400) ?>ms backwards;">

                                <!-- Client -->
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-sm text-white shadow-md transition-transform duration-300 group-hover:scale-110">
                                            <?= e(initials($clientName)) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-white transition-colors group-hover:text-[#00A6A6]">
                                                <?= e($clientName) ?>
                                            </div>
                                            <div class="truncate text-xs text-gray-500"><?= e($clientEmail) ?></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-bold uppercase <?= $statusStyle ?>">
                                        <?= $statusIcon ?> <?= e($status) ?>
                                    </span>
                                </td>

                                <!-- Last check-in -->
                                <td class="p-4 text-sm text-gray-400 whitespace-nowrap">
                                    <?php if ($lastCheckIn): ?>
                                        <div class="flex items-center gap-1.5">
                                            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                            <?= e(short_date($lastCheckIn)) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-600">Never</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Added date -->
                                <td class="p-4 text-sm text-gray-400 whitespace-nowrap">
                                    <?= e(short_date($c['created_date'] ?? null)) ?>
                                </td>

                                <!-- Actions -->
                                <td class="p-4 text-right">
                                    <div class="inline-flex items-center gap-1.5 opacity-80 transition-opacity group-hover:opacity-100">
                                        <button type="button"
                                            onclick='openMessageModal(<?= json_encode([
                                                                            "client_name"    => $clientName,
                                                                            "client_email"   => $clientEmail,
                                                                            "client_user_id" => $c["client_user_id"] ?? "",
                                                                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            title="Message client"
                                            class="tap-effect inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white hover:shadow-md">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            <span class="hidden sm:inline">Message</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="noClientResults" class="hidden p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-sm text-gray-400">No clients match your filter.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========== Help box ========== -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <div class="text-sm font-bold text-white">Grow your client list</div>
                <div class="mt-1 text-xs text-gray-400">
                    New members who register with your referral code are linked to you automatically.
                </div>
            </div>
        </div>
        <a href="<?= url_e('/partner-dashboard/sales') ?>"
            class="tap-effect inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-6 py-3 text-xs font-bold text-white transition-all duration-300 hover:border-[#00A6A6] hover:bg-[#00A6A6] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            Get Referral Link
        </a>
    </div>
</div>

<!-- ========== Modals ========== -->
<?php require BASE_PATH . '/src/Views/partner/partials/add_client_modal.php'; ?>
<?php require BASE_PATH . '/src/Views/partner/partials/message_client_modal.php'; ?>

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

    .client-filter.active {
        background-color: #00A6A6 !important;
        border-color: #00A6A6 !important;
        color: #ffffff !important;
        box-shadow: 0 0 15px rgba(0, 166, 166, 0.4);
    }

    .client-filter.active span:last-child {
        background-color: rgba(255, 255, 255, 0.2) !important;
        color: #ffffff !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .client-row {
            animation: none !important;
        }
    }
</style>

<script>
    /* ========== Filter + search ========== */
    let clientFilter = 'all';
    let clientSearch = '';

    function setClientFilter(status) {
        clientFilter = status;
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.filter === status);
        });
        applyClientFilters();
    }

    document.getElementById('clientSearch')?.addEventListener('input', (e) => {
        clientSearch = e.target.value.toLowerCase().trim();
        applyClientFilters();
    });

    function applyClientFilters() {
        const rows = document.querySelectorAll('#clientRows .client-row');
        let visible = 0;

        rows.forEach(row => {
            const status = row.dataset.status || '';
            const search = row.dataset.search || '';

            const okStatus = clientFilter === 'all' || status === clientFilter;
            const okSearch = clientSearch === '' || search.includes(clientSearch);

            if (okStatus && okSearch) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        const empty = document.getElementById('noClientResults');
        if (empty) empty.classList.toggle('hidden', visible > 0 || rows.length === 0);
    }
</script>