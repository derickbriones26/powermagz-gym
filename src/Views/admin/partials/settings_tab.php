<?php

/**
 * Admin — Settings Tab (2026 UI)
 *
 * Pure settings UI — infrastructure diagnostics live in a hidden modal.
 */

use App\Config\Database;

/* ================================================================
 |  LOAD ALL SETTINGS
 |  ================================================================ */

$rawSettings = [];
$settingsMeta = [];

try {
    $rows = Database::pdo()->query("
        SELECT `setting_key`, `setting_value`, `setting_type`, `description`, `updated_date`
        FROM `system_settings`
        ORDER BY `setting_key` ASC
    ")->fetchAll();

    foreach ($rows as $r) {
        $key   = $r['setting_key'];
        $type  = $r['setting_type'];
        $value = $r['setting_value'];

        if ($type === 'json') {
            $decoded = json_decode((string)$value, true);
            $value = is_array($decoded) ? $decoded : [];
        } elseif ($type === 'int') {
            $value = (int)$value;
        } elseif ($type === 'bool') {
            $value = (bool)$value;
        }

        $rawSettings[$key]  = $value;
        $settingsMeta[$key] = [
            'type'        => $type,
            'description' => $r['description'],
            'updated'     => $r['updated_date'],
        ];
    }
} catch (\Throwable $e) {
    error_log('[settings_tab] ' . $e->getMessage());
}

/* Accessors */
$getStr = fn(string $k, string $d = '') => (string)($rawSettings[$k] ?? $d);
$getInt = fn(string $k, int $d = 0)      => (int)($rawSettings[$k] ?? $d);
$getArr = fn(string $k, array $d = [])   => is_array($rawSettings[$k] ?? null) ? $rawSettings[$k] : $d;

$acceptedMethods     = $getArr('accepted_payment_methods', ['gcash', 'cash', 'credit_card', 'bank_transfer']);
$collectionFrequency = $getStr('payment_collection_frequency', 'monthly');
$lateGraceDays       = $getInt('late_payment_grace_days', 7);
$enrollGraceDays     = $getInt('enrollment_grace_period_days', 3);
$opCostPerMember     = $getInt('operational_cost_per_member', 300);
$defaultCommission   = $getInt('default_commission_rate', 10);
$gymCapacity         = $getInt('gym_capacity', 50);
$idleTimeout         = $getInt('idle_timeout_minutes', 30);

/* ================================================================
 |  INFRASTRUCTURE DATA  (for the modal only)
 |  ================================================================ */

$phpVersion   = PHP_VERSION;
$phpOk        = version_compare(PHP_VERSION, '8.1.0', '>=');

$mysqlVersion = '—';
$mysqlOk      = false;
try {
    $mysqlVersion = (string)Database::pdo()->query('SELECT VERSION()')->fetchColumn();
    $mysqlOk      = version_compare($mysqlVersion, '8.0.0', '>=');
} catch (\Throwable $e) { /* silent */
}

$encryptionOk = function_exists('crypto_is_configured') && crypto_is_configured();
$appEnv       = defined('APP_ENV') ? APP_ENV : 'unknown';
$timezone     = defined('APP_TIMEZONE') ? APP_TIMEZONE : date_default_timezone_get();
$isHttps      = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';

/* Backup marker */
$lastBackupMarker = BASE_PATH . '/storage/last_backup.txt';
$lastBackup = is_file($lastBackupMarker)
    ? trim((string)file_get_contents($lastBackupMarker))
    : '';

$lastBackupAge = '—';
$lastBackupOk  = false;
if ($lastBackup !== '') {
    $ts = strtotime($lastBackup);
    if ($ts !== false) {
        $diffHours = (int)floor((time() - $ts) / 3600);
        $lastBackupAge = $diffHours < 24
            ? $diffHours . 'h ago'
            : floor($diffHours / 24) . 'd ago';
        $lastBackupOk = $diffHours < 48;
    }
}

$auditRetentionDays = 365;
$allHealthy = $phpOk && $mysqlOk && $encryptionOk;

/* ================================================================
 |  HELPERS
 |  ================================================================ */

$paymentMethodLabels = [
    'gcash'         => ['svg' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'label' => 'GCash'],
    'cash'          => ['svg' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'label' => 'Cash'],
    'credit_card'   => ['svg' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'label' => 'Credit Card'],
    'bank_transfer' => ['svg' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z', 'label' => 'Bank Transfer'],
];

$frequencyLabels = [
    'daily'   => 'Daily',
    'weekly'  => 'Weekly',
    'monthly' => 'Monthly (recommended)',
    'annual'  => 'Annual',
];

$timeoutOptions = [15, 30, 45, 60, 120, 240];
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                    <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Admin Panel</span>
                </div>
                <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                    SYSTEM SETTINGS
                </h1>
                <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                    Configure payments, policies, security, and data safety.
                </p>
            </div>

            <button type="button" onclick="openSystemInfoModal()"
                class="tap-effect inline-flex items-center gap-2 self-start rounded-full border border-white/10 bg-white/5 px-5 py-3 text-xs font-bold text-gray-300 transition-all duration-300 hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white sm:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>System Info</span>
                <?php if (!$allHealthy): ?>
                    <span class="ml-1 flex h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                <?php endif; ?>
            </button>
        </div>
    </div>

    <!-- ============================================================
         PAYMENT CONFIGURATION
         ============================================================ -->
    <section role="region" aria-label="Payment configuration">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            </div>
            <div>
                <h2 class="font-heading text-2xl tracking-wide text-white">PAYMENT CONFIGURATION</h2>
                <p class="text-xs text-gray-400">Which methods members can use, and how often we collect</p>
            </div>
        </div>

        <!-- Payment methods -->
        <form method="post" action="<?= url_e('/admin-dashboard/settings/payment-methods') ?>"
            class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <?= csrf_field() ?>

            <fieldset>
                <legend class="text-sm font-bold text-white">Accepted Methods</legend>
                <p class="mt-1 text-xs text-gray-400">
                    Methods members can choose at enrollment. Unchecking removes them from the enrollment form.
                </p>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <?php foreach ($paymentMethodLabels as $key => $cfg):
                        $checked = in_array($key, $acceptedMethods, true);
                    ?>
                        <label class="group relative cursor-pointer rounded-2xl border-2 p-4 transition-all
                            <?= $checked
                                ? 'border-[#00A6A6] bg-[#00A6A6]/5'
                                : 'border-white/5 bg-white/[0.02] hover:border-white/20' ?>">
                            <input type="checkbox"
                                name="methods[]"
                                value="<?= e($key) ?>"
                                <?= $checked ? 'checked' : '' ?>
                                class="peer sr-only"
                                aria-label="Enable <?= e($cfg['label']) ?> as a payment method"
                                onchange="toggleMethodCard(this)">

                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl <?= $checked ? 'bg-[#00A6A6]/20 text-[#00A6A6]' : 'bg-white/5 text-gray-400' ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $cfg['svg'] ?>" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-white"><?= e($cfg['label']) ?></div>
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500">
                                        <?= $key ?>
                                    </div>
                                </div>
                            </div>

                            <span class="method-check absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-full transition-all
                                <?= $checked ? 'bg-[#00A6A6] text-white' : 'bg-white/5 text-transparent' ?>">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="tap-effect rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    Save Payment Methods
                </button>
            </div>
        </form>

        <!-- Frequency + grace -->
        <div class="mt-4 grid gap-4 lg:grid-cols-3">

            <!-- Collection frequency -->
            <form method="post" action="<?= url_e('/admin-dashboard/settings/collection') ?>"
                class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <?= csrf_field() ?>

                <fieldset>
                    <legend class="text-sm font-bold text-white">Collection Frequency</legend>
                    <p class="mt-1 text-xs text-gray-400">How often recurring payments are collected.</p>

                    <select name="frequency" required
                        class="mt-4 h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <?php foreach ($frequencyLabels as $val => $label): ?>
                            <option value="<?= e($val) ?>" <?= $collectionFrequency === $val ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <button type="submit"
                    class="mt-4 w-full rounded-full bg-[#00A6A6] px-5 py-3 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    Save Frequency
                </button>
            </form>

            <!-- Late payment grace -->
            <form method="post" action="<?= url_e('/admin-dashboard/settings/grace-period') ?>"
                class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="late_payment">

                <fieldset>
                    <legend class="text-sm font-bold text-white">Late Payment Grace</legend>
                    <p class="mt-1 text-xs text-gray-400">Days after due date before a payment is marked late.</p>

                    <div class="mt-4 flex items-center gap-2">
                        <input type="number" name="days" value="<?= (int)$lateGraceDays ?>" min="0" max="60" required
                            class="h-12 w-24 rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-center text-lg font-bold text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <span class="text-sm text-gray-400">days</span>
                    </div>
                </fieldset>

                <button type="submit"
                    class="mt-4 w-full rounded-full bg-[#00A6A6] px-5 py-3 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    Save Grace Period
                </button>
            </form>

            <!-- Enrollment grace -->
            <form method="post" action="<?= url_e('/admin-dashboard/settings/grace-period') ?>"
                class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="enrollment">

                <fieldset>
                    <legend class="text-sm font-bold text-white">Enrollment Grace</legend>
                    <p class="mt-1 text-xs text-gray-400">Days after expiry before the account locks out.</p>

                    <div class="mt-4 flex items-center gap-2">
                        <input type="number" name="days" value="<?= (int)$enrollGraceDays ?>" min="0" max="30" required
                            class="h-12 w-24 rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-center text-lg font-bold text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <span class="text-sm text-gray-400">days</span>
                    </div>
                </fieldset>

                <button type="submit"
                    class="mt-4 w-full rounded-full bg-[#00A6A6] px-5 py-3 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    Save Grace Period
                </button>
            </form>
        </div>
    </section>

    <!-- ============================================================
         OPERATIONAL POLICY
         ============================================================ -->
    <section role="region" aria-label="Operational policy">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <h2 class="font-heading text-2xl tracking-wide text-white">OPERATIONAL POLICY</h2>
                <p class="text-xs text-gray-400">Cost floors, commission rates, and capacity limits</p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">

            <!-- Operational cost floor -->
            <form method="post" action="<?= url_e('/admin-dashboard/settings/operational-cost') ?>"
                class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <?= csrf_field() ?>

                <fieldset>
                    <legend class="text-sm font-bold text-white">Operational Cost Floor</legend>
                    <p class="mt-1 text-xs text-gray-400">
                        Programs priced below this trigger a warning badge on the Programs tab.
                    </p>

                    <div class="mt-4 flex items-center gap-2">
                        <span class="text-lg font-bold text-gray-500">₱</span>
                        <input type="number" name="amount" value="<?= (int)$opCostPerMember ?>" min="0" max="1000000" step="1" required
                            class="h-12 w-32 rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-lg font-bold text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <span class="text-sm text-gray-400">per member</span>
                    </div>
                </fieldset>

                <button type="submit"
                    class="mt-4 w-full rounded-full bg-[#00A6A6] px-5 py-3 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    Save Floor
                </button>
            </form>

            <!-- Default commission -->
            <form method="post" action="<?= url_e('/admin-dashboard/settings/commission') ?>"
                class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <?= csrf_field() ?>

                <fieldset>
                    <legend class="text-sm font-bold text-white">Default Partner Commission</legend>
                    <p class="mt-1 text-xs text-gray-400">
                        Applied to new partner profiles. Spec recommends 10–15%.
                    </p>

                    <div class="mt-4 flex items-center gap-2">
                        <input type="number" name="rate" value="<?= (int)$defaultCommission ?>" min="0" max="50" step="0.5" required
                            class="h-12 w-24 rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-center text-lg font-bold text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <span class="text-sm text-gray-400">%</span>
                    </div>
                </fieldset>

                <button type="submit"
                    class="mt-4 w-full rounded-full bg-[#00A6A6] px-5 py-3 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    Save Commission
                </button>
            </form>

            <!-- Gym capacity -->
            <form method="post" action="<?= url_e('/admin-dashboard/settings/gym-capacity') ?>"
                class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <?= csrf_field() ?>

                <fieldset>
                    <legend class="text-sm font-bold text-white">Gym Capacity</legend>
                    <p class="mt-1 text-xs text-gray-400">
                        Max simultaneous occupancy. Drives the live utilization gauge.
                    </p>

                    <div class="mt-4 flex items-center gap-2">
                        <input type="number" name="capacity" value="<?= (int)$gymCapacity ?>" min="1" max="9999" required
                            class="h-12 w-24 rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-center text-lg font-bold text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <span class="text-sm text-gray-400">members</span>
                    </div>
                </fieldset>

                <button type="submit"
                    class="mt-4 w-full rounded-full bg-[#00A6A6] px-5 py-3 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    Save Capacity
                </button>
            </form>
        </div>
    </section>

    <!-- ============================================================
         SESSION SECURITY
         ============================================================ -->
    <section role="region" aria-label="Session security">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <h2 class="font-heading text-2xl tracking-wide text-white">SESSION SECURITY</h2>
                <p class="text-xs text-gray-400">Idle timeout policy (spec §2.1 minimum: 30 minutes)</p>
            </div>
        </div>

        <form method="post" action="<?= url_e('/admin-dashboard/settings/idle-timeout') ?>"
            class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <?= csrf_field() ?>

            <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
                <fieldset>
                    <legend class="text-sm font-bold text-white">Idle Timeout</legend>
                    <p class="mt-1 text-xs text-gray-400">
                        After this period of inactivity, users are signed out automatically. A 5-minute warning prompt is shown before expiry.
                    </p>

                    <div class="mt-4 flex items-center gap-2">
                        <input type="number" name="minutes" value="<?= (int)$idleTimeout ?>" min="5" max="480" required
                            class="h-12 w-24 rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-center text-lg font-bold text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <span class="text-sm text-gray-400">minutes</span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <?php foreach ($timeoutOptions as $opt): ?>
                            <button type="button"
                                onclick="document.querySelector('input[name=minutes]').value = <?= $opt ?>"
                                class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold text-gray-300 transition-colors hover:border-[#00A6A6] hover:text-white">
                                <?= $opt ?>m
                            </button>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-5">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-amber-500">Recommended Policy</div>
                    <ul class="mt-3 space-y-2 text-xs text-gray-300">
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span><strong class="text-white">30 minutes</strong> — default for admin/staff accounts</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span><strong class="text-white">60 minutes</strong> — acceptable for busy front desks</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span><strong class="text-white">&gt; 120 minutes</strong> — discouraged per spec §2.1</span>
                        </li>
                    </ul>
                    <div class="mt-4 border-t border-white/10 pt-4 text-[11px] text-gray-400">
                        Users can override this per-account via <code class="text-white">users.idle_timeout_minutes</code>.
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="tap-effect rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    Save Timeout
                </button>
            </div>
        </form>
    </section>

    <!-- ============================================================
         DATA SAFETY
         ============================================================ -->
    <section role="region" aria-label="Data safety and backups">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <h2 class="font-heading text-2xl tracking-wide text-white">DATA SAFETY</h2>
                <p class="text-xs text-gray-400">Backups, retention, and disaster recovery</p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">

            <!-- Backup status -->
            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-sm font-bold text-white">Backup Status</div>
                        <p class="mt-1 text-xs text-gray-400">Last recorded backup timestamp.</p>
                    </div>
                    <span data-status-badge class="rounded-full border <?= $lastBackupOk ? 'bg-green-500/10 text-green-500 border-green-500/20' : 'bg-amber-500/10 text-amber-500 border-amber-500/20' ?> px-3 py-1 text-[10px] font-bold uppercase">
                        <?php if ($lastBackupOk): ?>
                            <svg class="w-3 h-3 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Recent
                        <?php else: ?>
                            <svg class="w-3 h-3 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Review
                        <?php endif; ?>
                    </span>
                </div>

                <div class="mt-4 rounded-xl border border-white/5 bg-white/[0.02] p-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Last Backup</div>
                    <div class="mt-1 font-heading text-2xl text-white">
                        <?= $lastBackup !== '' ? e($lastBackupAge) : '—' ?>
                    </div>
                    <?php if ($lastBackup !== ''): ?>
                        <div class="mt-1 text-xs text-gray-400">
                            Recorded <?= e(date('M j, Y \a\t g:i A', strtotime($lastBackup))) ?>
                        </div>
                    <?php else: ?>
                        <div class="mt-1 text-xs text-gray-400">No backup marker file found.</div>
                    <?php endif; ?>
                </div>

                <ul class="mt-4 space-y-2 text-xs text-gray-300">
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-[#00A6A6] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span><strong class="text-white">Daily</strong> automated backups — spec §2.2 requirement</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-[#00A6A6] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Store in <strong class="text-white">geo-redundant storage</strong> (S3, GCS, B2)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-[#00A6A6] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>Encrypt backups with AES-256 before uploading</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-[#00A6A6] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                        <span>Weekly <strong class="text-white">restore test</strong> — verify backups actually work</span>
                    </li>
                </ul>
            </div>

            <!-- Retention policy -->
            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div class="text-sm font-bold text-white">Retention Policy</div>
                <p class="mt-1 text-xs text-gray-400">How long different data classes are kept.</p>

                <dl class="mt-4 space-y-2">
                    <div class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">
                        <div>
                            <dt class="text-xs font-bold text-white">Audit logs</dt>
                            <dd class="text-[10px] text-gray-500">Spec §2.1</dd>
                        </div>
                        <div class="text-sm font-bold text-green-500">
                            ≥ <?= (int)$auditRetentionDays ?> days
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">
                        <div>
                            <dt class="text-xs font-bold text-white">Payment records</dt>
                            <dd class="text-[10px] text-gray-500">Financial compliance</dd>
                        </div>
                        <div class="text-sm font-bold text-white">Indefinite</div>
                    </div>

                    <div class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">
                        <div>
                            <dt class="text-xs font-bold text-white">Attendance records</dt>
                            <dd class="text-[10px] text-gray-500">Operational</dd>
                        </div>
                        <div class="text-sm font-bold text-white">Indefinite</div>
                    </div>

                    <div class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">
                        <div>
                            <dt class="text-xs font-bold text-white">Expired sessions</dt>
                            <dd class="text-[10px] text-gray-500">Cleanup allowed</dd>
                        </div>
                        <div class="text-sm font-bold text-white">After expiry</div>
                    </div>
                </dl>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="<?= url_e('/admin-dashboard?tab=audit') ?>"
                        class="tap-effect inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Audit Log
                    </a>
                    <a href="<?= url_e('/admin-dashboard?tab=audit') ?>#purge"
                        class="tap-effect inline-flex items-center gap-1.5 rounded-full border border-red-500/20 bg-red-500/10 px-3 py-1.5 text-xs font-bold text-red-500 transition-all hover:-translate-y-0.5 hover:bg-red-500/20">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Purge Old Logs
                    </a>
                </div>
            </div>
        </div>

        <!-- DR checklist -->
        <div class="mt-4 rounded-2xl border border-blue-500/20 bg-gradient-to-br from-blue-500/5 to-[#111] p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-bold text-white">Disaster Recovery Plan</div>
                    <p class="mt-1 text-xs text-gray-400">Spec §2.2 target: RTO &lt; 4 hours.</p>

                    <ul class="mt-4 grid gap-2 text-xs text-gray-300 sm:grid-cols-2">
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Database schema in version control</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Application code in git</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Automated daily backups configured</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Restore procedure documented + tested</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>APP_KEY backed up securely (offline)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Failover host ready to deploy</span>
                        </li>
                    </ul>

                    <div class="mt-4 rounded-xl border border-amber-500/20 bg-amber-500/5 p-4">
                        <div class="text-xs leading-relaxed text-amber-200/80">
                            <strong class="text-amber-500">Critical:</strong> Back up your <code class="text-white">.env</code> file (with <code class="text-white">APP_KEY</code>) offline, separately from the database. Without it, encrypted columns are unreadable.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         ALL SETTINGS (read-only)
         ============================================================ -->
    <section role="region" aria-label="All settings (read-only)">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
            </div>
            <div>
                <h2 class="font-heading text-2xl tracking-wide text-white">ALL SETTINGS</h2>
                <p class="text-xs text-gray-400">Every entry in <code class="text-white">system_settings</code> — read-only view</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <caption class="sr-only">All rows in system_settings table</caption>
                    <thead>
                        <tr class="border-b border-white/5 bg-white/[0.02]">
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Key</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Type</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Value</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Description</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden xl:table-cell">Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rawSettings as $key => $value):
                            $meta = $settingsMeta[$key] ?? ['type' => 'string', 'description' => '', 'updated' => ''];
                            $displayValue = is_array($value)
                                ? implode(', ', $value)
                                : (is_bool($value) ? ($value ? 'true' : 'false') : (string)$value);
                        ?>
                            <tr class="border-b border-white/5 transition-colors hover:bg-white/[0.02]">
                                <td class="p-4">
                                    <code class="text-xs font-bold text-[#00A6A6]"><?= e($key) ?></code>
                                </td>
                                <td class="p-4">
                                    <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-0.5 text-[10px] font-bold uppercase text-gray-300">
                                        <?= e($meta['type']) ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="max-w-md truncate text-xs text-gray-300" title="<?= e($displayValue) ?>">
                                        <?= e($displayValue) ?: '—' ?>
                                    </div>
                                </td>
                                <td class="p-4 max-w-md hidden lg:table-cell">
                                    <div class="truncate text-xs text-gray-500" title="<?= e($meta['description']) ?>">
                                        <?= e($meta['description'] ?: '—') ?>
                                    </div>
                                </td>
                                <td class="p-4 text-xs text-gray-500 whitespace-nowrap hidden xl:table-cell">
                                    <?= e($meta['updated'] ? date('M j, Y', strtotime($meta['updated'])) : '—') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($rawSettings)): ?>
                            <tr>
                                <td colspan="5" class="p-16 text-center">
                                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                        </svg>
                                    </div>
                                    <p class="mt-4 text-sm text-gray-400">No settings rows found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ============================================================
         SYSTEM INFO MODAL
         ============================================================ -->
    <div id="systemInfoModal" role="dialog" aria-modal="true" aria-labelledby="systemInfoModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-3xl max-h-[92vh] overflow-y-auto rounded-3xl border border-white/10 bg-[#111] shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-white/5 bg-[#111] p-6">
                <div>
                    <h2 id="systemInfoModalTitle" class="font-heading text-2xl tracking-wide text-white">
                        SYSTEM INFORMATION
                    </h2>
                    <p class="mt-1 text-xs text-gray-400">Live environment status — read-only diagnostics</p>
                </div>
                <button type="button" onclick="closeSystemInfoModal()"
                    aria-label="Close system info dialog"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-5">

                <!-- Summary banner -->
                <div class="flex items-center gap-3 rounded-2xl border <?= $allHealthy ? 'border-green-500/20 bg-green-500/5' : 'border-amber-500/20 bg-amber-500/5' ?> p-4">
                    <?php if ($allHealthy): ?>
                        <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    <?php else: ?>
                        <svg class="w-6 h-6 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    <?php endif; ?>
                    <div>
                        <div class="text-sm font-bold <?= $allHealthy ? 'text-green-500' : 'text-amber-500' ?>">
                            <?= $allHealthy ? 'All systems operational' : 'Some components need attention' ?>
                        </div>
                        <div class="mt-0.5 text-xs text-gray-400">
                            Review the details below for specifics.
                        </div>
                    </div>
                </div>

                <!-- Status cards -->
                <div class="grid gap-4 sm:grid-cols-2">

                    <!-- PHP -->
                    <div class="rounded-2xl border <?= $phpOk ? 'border-green-500/20 bg-green-500/5' : 'border-red-500/20 bg-red-500/5' ?> p-5">
                        <div class="flex items-center justify-between">
                            <div class="text-[10px] font-bold uppercase tracking-widest <?= $phpOk ? 'text-green-500' : 'text-red-500' ?>">
                                PHP Runtime
                            </div>
                            <?php if ($phpOk): ?>
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            <?php else: ?>
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2 font-heading text-2xl <?= $phpOk ? 'text-white' : 'text-red-500' ?>">
                            <?= e($phpVersion) ?>
                        </div>
                        <div class="mt-1 text-xs text-gray-400">Requires ≥ 8.1</div>
                    </div>

                    <!-- MySQL -->
                    <div class="rounded-2xl border <?= $mysqlOk ? 'border-green-500/20 bg-green-500/5' : 'border-amber-500/20 bg-amber-500/5' ?> p-5">
                        <div class="flex items-center justify-between">
                            <div class="text-[10px] font-bold uppercase tracking-widest <?= $mysqlOk ? 'text-green-500' : 'text-amber-500' ?>">
                                Database
                            </div>
                            <?php if ($mysqlOk): ?>
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            <?php else: ?>
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2 font-heading text-2xl <?= $mysqlOk ? 'text-white' : 'text-amber-500' ?>">
                            <?= e(substr($mysqlVersion, 0, 12)) ?>
                        </div>
                        <div class="mt-1 text-xs text-gray-400">MySQL 8+ / MariaDB 10.4+</div>
                    </div>

                    <!-- Encryption -->
                    <div class="rounded-2xl border <?= $encryptionOk ? 'border-green-500/20 bg-green-500/5' : 'border-red-500/20 bg-red-500/5' ?> p-5">
                        <div class="flex items-center justify-between">
                            <div class="text-[10px] font-bold uppercase tracking-widest <?= $encryptionOk ? 'text-green-500' : 'text-red-500' ?>">
                                Encryption
                            </div>
                            <?php if ($encryptionOk): ?>
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            <?php else: ?>
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2 font-heading text-2xl <?= $encryptionOk ? 'text-white' : 'text-red-500' ?>">
                            <?= $encryptionOk ? 'AES-256-GCM' : 'Missing key' ?>
                        </div>
                        <div class="mt-1 text-xs text-gray-400">
                            <?= $encryptionOk ? 'APP_KEY is set' : 'Set APP_KEY in .env' ?>
                        </div>
                    </div>

                    <!-- HTTPS -->
                    <div class="rounded-2xl border <?= $isHttps ? 'border-green-500/20 bg-green-500/5' : ($appEnv === 'local' ? 'border-white/5 bg-white/[0.02]' : 'border-amber-500/20 bg-amber-500/5') ?> p-5">
                        <div class="flex items-center justify-between">
                            <div class="text-[10px] font-bold uppercase tracking-widest <?= $isHttps ? 'text-green-500' : 'text-gray-500' ?>">
                                Transport
                            </div>
                            <?php if ($isHttps): ?>
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            <?php else: ?>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2 font-heading text-2xl <?= $isHttps ? 'text-white' : 'text-gray-400' ?>">
                            <?= $isHttps ? 'HTTPS' : 'HTTP' ?>
                        </div>
                        <div class="mt-1 text-xs text-gray-400">
                            <?= $isHttps ? 'TLS active' : ($appEnv === 'local' ? 'Local dev — OK' : 'Enable TLS in production') ?>
                        </div>
                    </div>
                </div>

                <!-- Extended diagnostics -->
                <details class="rounded-2xl border border-white/5 bg-white/[0.02]">
                    <summary class="cursor-pointer p-4 text-sm font-bold text-gray-300 hover:text-white">
                        <span class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#00A6A6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Extended diagnostics
                        </span>
                    </summary>

                    <div class="grid gap-3 border-t border-white/5 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl border border-white/5 bg-[#0a0a0a] p-4">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Environment</div>
                            <div class="mt-1 font-heading text-base text-white"><?= e($appEnv) ?></div>
                        </div>

                        <div class="rounded-xl border border-white/5 bg-[#0a0a0a] p-4">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Timezone</div>
                            <div class="mt-1 font-heading text-base text-white"><?= e($timezone) ?></div>
                        </div>

                        <div class="rounded-xl border border-white/5 bg-[#0a0a0a] p-4">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Upload Limit</div>
                            <div class="mt-1 font-heading text-base text-white"><?= e(ini_get('upload_max_filesize') ?: '—') ?></div>
                        </div>

                        <div class="rounded-xl border border-white/5 bg-[#0a0a0a] p-4">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Memory Limit</div>
                            <div class="mt-1 font-heading text-base text-white"><?= e(ini_get('memory_limit') ?: '—') ?></div>
                        </div>

                        <div class="rounded-xl border border-white/5 bg-[#0a0a0a] p-4">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Session Driver</div>
                            <div class="mt-1 font-heading text-base text-white"><?= e(ini_get('session.save_handler') ?: 'files') ?></div>
                        </div>

                        <div class="rounded-xl border border-white/5 bg-[#0a0a0a] p-4">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Cookie Secure</div>
                            <div class="mt-1 font-heading text-base text-white"><?= $isHttps ? 'Yes' : 'No' ?></div>
                        </div>
                    </div>
                </details>
            </div>

            <div class="flex justify-end gap-2 border-t border-white/5 p-4">
                <button type="button" onclick="closeSystemInfoModal()"
                    class="tap-effect rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    Close
                </button>
            </div>
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

    @keyframes settings-flash {
        from {
            background-color: rgba(0, 166, 166, 0.15);
        }

        to {
            background-color: transparent;
        }
    }

    .settings-flash {
        animation: settings-flash 0.9s ease-out;
    }

    details>summary::-webkit-details-marker {
        display: none;
    }

    details>summary {
        list-style: none;
    }

    @media (prefers-reduced-motion: reduce) {

        #systemInfoModal>div,
        .settings-flash {
            animation: none !important;
        }
    }
</style>

<script>
    (function() {
        'use strict';

        /* ============ Method card visual state ============ */
        window.toggleMethodCard = function(checkbox) {
            const card = checkbox.closest('label');
            if (!card) return;

            const check = card.querySelector('.method-check');
            const iconWrap = card.querySelector('span:first-child');
            const on = checkbox.checked;

            if (on) {
                card.classList.add('border-[#00A6A6]', 'bg-[#00A6A6]/5');
                card.classList.remove('border-white/5', 'bg-white/[0.02]');
                if (check) {
                    check.classList.add('bg-[#00A6A6]', 'text-white');
                    check.classList.remove('bg-white/5', 'text-transparent');
                }
                if (iconWrap) {
                    iconWrap.classList.add('bg-[#00A6A6]/20', 'text-[#00A6A6]');
                    iconWrap.classList.remove('bg-white/5', 'text-gray-400');
                }
            } else {
                card.classList.remove('border-[#00A6A6]', 'bg-[#00A6A6]/5');
                card.classList.add('border-white/5', 'bg-white/[0.02]');
                if (check) {
                    check.classList.remove('bg-[#00A6A6]', 'text-white');
                    check.classList.add('bg-white/5', 'text-transparent');
                }
                if (iconWrap) {
                    iconWrap.classList.remove('bg-[#00A6A6]/20', 'text-[#00A6A6]');
                    iconWrap.classList.add('bg-white/5', 'text-gray-400');
                }
            }
        };

        /* ============ System Info modal ============ */
        window.openSystemInfoModal = function() {
            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }
            document.getElementById('systemInfoModal').classList.replace('hidden', 'flex');
        };

        window.closeSystemInfoModal = function() {
            document.getElementById('systemInfoModal').classList.replace('flex', 'hidden');
        };

        document.getElementById('systemInfoModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'systemInfoModal') window.closeSystemInfoModal();
        });

        /* ============ Flash saved form ============ */
        document.querySelectorAll('form[action*="/admin-dashboard/settings/"]').forEach(form => {
            form.addEventListener('submit', () => {
                sessionStorage.setItem('pmgz-flash-setting', '1');
            });
        });

        if (sessionStorage.getItem('pmgz-flash-setting') === '1') {
            sessionStorage.removeItem('pmgz-flash-setting');
            document.querySelectorAll('form[action*="/admin-dashboard/settings/"]').forEach(f => {
                f.classList.add('settings-flash');
                setTimeout(() => f.classList.remove('settings-flash'), 1200);
            });
        }
    })();
</script>