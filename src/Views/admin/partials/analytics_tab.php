<?php

/**
 * Admin — Analytics Tab (2026 UI)
 *
 * Receives from AdminController::dashboard():
 *   $data['payments']             — Payment::listWithFilters() rows
 *   $data['paymentSummary']       — statusSummary()
 *   $data['paymentAging']         — agingReport()
 *   $data['paymentMethods']       — methodConfig()
 *   $data['paymentRevenue7d']     — revenueDaily(7)
 *   $data['partnerSales']         — PartnerSale::listWithFilters() rows
 *   $data['saleSummary']          — PartnerSale::summary()
 *   $data['partners']             — PartnerProfile::listWithMetrics()
 *   $data['partnerSummary']       — PartnerProfile::summary()
 *   $data['partnerAcquisition']   — acquisitionTrend(12)
 *   $data['partnerTypeBreak']     — typeBreakdown()
 *   $data['partnerTop']           — topPerformers(8)
 *   $data['b2bSummary']           — B2BClient::summary()
 *   $data['b2bByPartner']         — countsByPartner()
 *   $data['payouts']              — PayoutRequest::listWithFilters() rows
 *   $data['payoutSummary']        — PayoutRequest::summary()
 *   $data['payoutReconciliation'] — monthlyReconciliation(12)
 *   $data['enrollments']          — Enrollment rows
 *   $data['users']                — User rows
 */

use App\Models\Payment;

/* ---------- Safe extraction ---------- */

$payments        = $data['payments']             ?? [];
$paymentSummary  = $data['paymentSummary']       ?? [];
$paymentAging    = $data['paymentAging']         ?? ['buckets' => [], 'rows' => []];
$methodCfg       = $data['paymentMethods']       ?? ['accepted_methods' => [], 'collection_frequency' => 'monthly', 'grace_days' => 7];
$revenue7d       = $data['paymentRevenue7d']     ?? [];
$partnerSales    = $data['partnerSales']         ?? [];
$saleSummary     = $data['saleSummary']          ?? [];
$partners        = $data['partners']             ?? [];
$partnerSummary  = $data['partnerSummary']       ?? [];
$partnerAcq      = $data['partnerAcquisition']   ?? [];
$partnerTypes    = $data['partnerTypeBreak']     ?? [];
$partnerTop      = $data['partnerTop']           ?? [];
$b2bSummary      = $data['b2bSummary']           ?? ['total' => 0, 'linked' => 0, 'pending' => 0, 'inactive' => 0];
$b2bByPartner    = $data['b2bByPartner']         ?? [];
$payouts         = $data['payouts']              ?? [];
$payoutSummary   = $data['payoutSummary']        ?? [];
$recon           = $data['payoutReconciliation'] ?? [];
$enrollments     = $data['enrollments']          ?? [];
$users           = $data['users']                ?? [];

/* ---------- Period filter ---------- */
$period  = $_GET['period'] ?? '30d';
$periods = [
    '7d'  => ['label' => '7 Days',  'days' => 7],
    '30d' => ['label' => '30 Days', 'days' => 30],
    '90d' => ['label' => '90 Days', 'days' => 90],
    'all' => ['label' => 'All Time', 'days' => null],
];
$days   = $periods[$period]['days'] ?? 30;
$cutoff = $days !== null ? date('Y-m-d', strtotime("-{$days} days")) : '1970-01-01';

/* ---------- Filter by period ---------- */
$filteredPayments = array_values(array_filter($payments, function ($p) use ($cutoff) {
    return ($p['paid_date'] ?? '') >= $cutoff;
}));

$filteredSales = array_values(array_filter($partnerSales, function ($s) use ($cutoff) {
    return ($s['sale_date'] ?? '') >= $cutoff;
}));

/* ---------- Partner map ---------- */
$partnerMap = [];
foreach ($partners as $p) {
    $partnerMap[$p['user_id']] = $p['business_name'] ?? 'Partner';
}

/* ---------- Revenue aggregation ---------- */
$b2cTotal = 0.0;
$b2bTotal = 0.0;
$tierTotals = ['digital' => 0.0, 'standard' => 0.0, 'premium' => 0.0, 'b2b' => 0.0];
$methodTotals = ['gcash' => 0.0, 'cash' => 0.0, 'credit_card' => 0.0, 'bank_transfer' => 0.0];
$paidCount = 0;
$failedCount = 0;
$refundedCount = 0;
$refundedAmount = 0.0;

foreach ($filteredPayments as $p) {
    $amt    = (float)($p['amount'] ?? 0);
    $refAmt = (float)($p['refund_amount'] ?? 0);
    $status = $p['status'] ?? 'pending';
    $source = $p['source'] ?? 'b2c';
    $tier   = $p['tier']   ?? '';
    $method = $p['method'] ?? 'gcash';

    if ($status === 'paid' || $status === 'partial_refund') {
        if ($source === 'b2c') {
            $b2cTotal += $amt;
            if (isset($tierTotals[$tier])) $tierTotals[$tier] += $amt;
        }
        if (isset($methodTotals[$method])) $methodTotals[$method] += $amt;
        $paidCount++;
        $refundedAmount += $refAmt;
    } elseif ($status === 'refunded') {
        $refundedCount++;
        $refundedAmount += $amt;
    } elseif ($status === 'failed') {
        $failedCount++;
    }
}

foreach ($filteredSales as $s) {
    if (($s['status'] ?? '') !== 'approved') continue;
    $b2bTotal += (float)($s['amount'] ?? 0);
}
$tierTotals['b2b'] = $b2bTotal;
$totalRevenue = $b2cTotal + $b2bTotal;
$netRevenue = $totalRevenue - $refundedAmount;

/* ---------- Rates ---------- */
$settled = $paidCount + $failedCount;
$successRate = $settled > 0 ? round(($paidCount / $settled) * 100, 1) : 0.0;
$failureRate = $settled > 0 ? round(($failedCount / $settled) * 100, 1) : 0.0;

$memberCount = 0;
foreach ($users as $u) {
    if (($u['role'] ?? '') !== 'admin') $memberCount++;
}
$arpu = $memberCount > 0 ? round($b2cTotal / $memberCount, 2) : 0.0;
$avgTransaction = $paidCount > 0 ? round($totalRevenue / $paidCount, 2) : 0.0;

/* ---------- Partner rankings ---------- */
$partnerTotals = [];
foreach ($filteredSales as $s) {
    if (($s['status'] ?? '') !== 'approved') continue;
    $uid = $s['partner_user_id'] ?? '';
    $partnerTotals[$uid] = ($partnerTotals[$uid] ?? 0) + (float)($s['amount'] ?? 0);
}
arsort($partnerTotals);
$partnerTotals = array_slice($partnerTotals, 0, 8, true);

$topPartnerLabels = [];
$topPartnerValues = [];
foreach ($partnerTotals as $uid => $total) {
    $topPartnerLabels[] = $partnerMap[$uid] ?? 'Partner';
    $topPartnerValues[] = round($total, 2);
}

/* ---------- Daily revenue ---------- */
$dailyRevenue = [];
$seriesDays = $days ?? 90;
for ($i = $seriesDays - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $dailyRevenue[$d] = 0.0;
}
foreach ($filteredPayments as $p) {
    if (!in_array($p['status'] ?? '', ['paid', 'partial_refund'], true)) continue;
    $d = $p['paid_date'] ?? '';
    if (isset($dailyRevenue[$d])) $dailyRevenue[$d] += (float)($p['amount'] ?? 0);
}
$revenueLabels = array_keys($dailyRevenue);
$revenueValues = array_values($dailyRevenue);

/* ---------- Member growth ---------- */
$memberGrowth = [];
for ($i = $seriesDays - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $memberGrowth[$d] = 0;
}
foreach ($users as $u) {
    $d = substr($u['created_date'] ?? '', 0, 10);
    if (isset($memberGrowth[$d])) $memberGrowth[$d]++;
}
$cumulative = 0;
foreach ($memberGrowth as $d => $count) {
    $cumulative += $count;
    $memberGrowth[$d] = $cumulative;
}
$growthLabels = array_keys($memberGrowth);
$growthValues = array_values($memberGrowth);

/* ---------- Enrollment status ---------- */
$enrollStatus = ['active' => 0, 'pending' => 0, 'expired' => 0, 'paused' => 0, 'cancelled' => 0];
foreach ($enrollments as $e) {
    $s = $e['status'] ?? 'pending';
    if (isset($enrollStatus[$s])) $enrollStatus[$s]++;
}

/* ---------- Aging buckets ---------- */
$agingBuckets = $paymentAging['buckets'] ?? [];
$agingLabels  = ['Current', '1–7d', '8–14d', '15–30d', '30+d'];
$agingCounts  = [
    (int)($agingBuckets['current']['count'] ?? 0),
    (int)($agingBuckets['1_7']['count']     ?? 0),
    (int)($agingBuckets['8_14']['count']    ?? 0),
    (int)($agingBuckets['15_30']['count']   ?? 0),
    (int)($agingBuckets['30_plus']['count'] ?? 0),
];
$agingAmounts = [
    (float)($agingBuckets['current']['total'] ?? 0),
    (float)($agingBuckets['1_7']['total']     ?? 0),
    (float)($agingBuckets['8_14']['total']    ?? 0),
    (float)($agingBuckets['15_30']['total']   ?? 0),
    (float)($agingBuckets['30_plus']['total'] ?? 0),
];
$hasAging = array_sum($agingCounts) > 0;

/* ---------- Partner-specific series ---------- */
$acqLabels = [];
$acqValues = [];
foreach ($partnerAcq as $row) {
    $acqLabels[] = $row['month'];
    $acqValues[] = $row['count'];
}

$typeLabels = [];
$typeCounts = [];
$typeRev    = [];
foreach ($partnerTypes as $row) {
    $typeLabels[] = $row['label'];
    $typeCounts[] = $row['count'];
    $typeRev[]    = $row['revenue'];
}

/* Retention health buckets */
$retentionPartners = [
    'active'   => (int)($partnerSummary['active']   ?? 0),
    'inactive' => (int)($partnerSummary['inactive'] ?? 0),
    'churned'  => (int)($partnerSummary['churned']  ?? 0),
];

/* Payout reconciliation */
$reconLabels = [];
$reconValues = [];
foreach ($recon as $row) {
    $reconLabels[] = $row['month'];
    $reconValues[] = (float)$row['total'];
}

/* ---------- Chart meta for KPI cards ---------- */
$srColorClass = $successRate >= 90 ? 'text-green-500'
    : ($successRate >= 70 ? 'text-amber-500' : 'text-red-500');
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Admin Panel</span>
            </div>
            <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                ANALYTICS
            </h1>
            <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                Revenue, payments, partner performance, and member growth across the entire gym.
            </p>
        </div>
    </div>

    <!-- ============================================================
         PERIOD SELECTOR
         ============================================================ -->
    <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm">
        <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Period:</span>
        <?php foreach ($periods as $key => $cfg):
            $isActive = $key === $period;
        ?>
            <a href="<?= url_e('/admin-dashboard?tab=analytics&period=' . $key) ?>"
                aria-current="<?= $isActive ? 'true' : 'false' ?>"
                class="filter-pill flex items-center gap-2 rounded-full border <?= $isActive ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-4 py-2 text-sm font-semibold transition-all duration-300">
                <span><?= e($cfg['label']) ?></span>
            </a>
        <?php endforeach; ?>

        <div class="ml-auto text-xs text-gray-400" <?= a11y_live_region('polite') ?>>
            Showing data from <strong class="text-white"><?= e(short_date($cutoff)) ?></strong> to today
        </div>
    </div>

    <!-- ============================================================
         TOP KPIs
         ============================================================ -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">

        <!-- Total Revenue -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Revenue</div>
                    <div class="mt-2 font-heading text-3xl text-[#00A6A6]"><?= peso($totalRevenue) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        Net: <span class="text-white"><?= peso($netRevenue) ?></span>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Avg Transaction -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Avg Transaction</div>
                    <div class="mt-2 font-heading text-3xl text-white"><?= peso($avgTransaction) ?></div>
                    <div class="mt-1 text-xs text-gray-400"><?= $paidCount ?> paid</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-gray-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- ARPU -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">ARPU</div>
                    <div class="mt-2 font-heading text-3xl text-[#00A6A6]"><?= peso($arpu) ?></div>
                    <div class="mt-1 text-xs text-gray-400">Per member</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="group relative overflow-hidden rounded-2xl border <?= $successRate >= 90 ? 'border-green-500/20 bg-green-500/5' : ($successRate >= 70 ? 'border-amber-500/20 bg-amber-500/5' : 'border-red-500/20 bg-red-500/5') ?> p-6 shadow-sm transition-all duration-300 hover:-translate-y-1">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-current opacity-5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Success Rate</div>
                    <div class="mt-2 font-heading text-3xl <?= $srColorClass ?>"><?= number_format($successRate, 1) ?>%</div>
                    <div class="mt-1 flex items-center gap-2 text-xs">
                        <span class="font-semibold text-green-500"><?= $paidCount ?> paid</span>
                        <span class="font-semibold text-red-500"><?= $failedCount ?> failed</span>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 <?= $srColorClass ?> transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Refunded -->
        <div class="group relative overflow-hidden rounded-2xl border border-purple-500/20 bg-purple-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-purple-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-purple-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-purple-400">Refunded</div>
                    <div class="mt-2 font-heading text-3xl text-purple-400"><?= peso($refundedAmount) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= $refundedCount ?> full · <?= (int)($paymentSummary['partial_refund'] ?? 0) ?> partial
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         PARTNER & B2B ANALYTICS
         ============================================================ -->
    <section role="region" aria-label="Partner and B2B analytics">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="font-heading text-3xl tracking-wide text-white">PARTNER &amp; B2B ANALYTICS</h2>
                <p class="mt-1 text-sm text-gray-400">
                    Referral pipeline, acquisition, churn, and commission health.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                    <?= (int)($partnerSummary['total'] ?? 0) ?> partners
                </span>
                <span class="rounded-full border border-purple-500/30 bg-purple-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-purple-400">
                    <?= (int)($b2bSummary['total'] ?? 0) ?> clients
                </span>
            </div>
        </div>

        <!-- Partner KPI mini strip -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40">
                <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Partner Revenue</div>
                        <div class="mt-2 font-heading text-2xl text-[#00A6A6]"><?= peso($partnerSummary['total_revenue'] ?? 0) ?></div>
                        <div class="mt-1 text-xs text-gray-400">
                            <?= (int)($partnerSummary['total_referrals'] ?? 0) ?> referrals · <?= (int)($partnerSummary['linked_clients'] ?? 0) ?> linked
                        </div>
                    </div>
                </div>
            </div>

            <div class="group relative overflow-hidden rounded-2xl border border-purple-500/20 bg-purple-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-purple-500/40">
                <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-purple-500/5 transition-transform duration-500 group-hover:scale-150"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-purple-400">Commission Earned</div>
                        <div class="mt-2 font-heading text-2xl text-purple-400"><?= peso($partnerSummary['total_commission'] ?? 0) ?></div>
                        <div class="mt-1 text-xs text-gray-400">
                            <?= peso($partnerSummary['pending_commission'] ?? 0) ?> pending
                        </div>
                    </div>
                </div>
            </div>

            <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40">
                <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Avg per Partner</div>
                        <div class="mt-2 font-heading text-2xl text-white"><?= peso($partnerSummary['avg_revenue_per_partner'] ?? 0) ?></div>
                        <div class="mt-1 text-xs text-gray-400">Revenue</div>
                    </div>
                </div>
            </div>

            <div class="group relative overflow-hidden rounded-2xl border border-purple-500/20 bg-purple-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-purple-500/40">
                <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-purple-500/5 transition-transform duration-500 group-hover:scale-150"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-purple-400">Paid Out</div>
                        <div class="mt-2 font-heading text-2xl text-purple-400"><?= peso($partnerSummary['total_paid_out'] ?? 0) ?></div>
                        <div class="mt-1 text-xs text-gray-400"><?= (int)($payoutSummary['approved'] ?? 0) ?> payouts</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart row 1: Acquisition + Type mix -->
        <div class="mt-6 grid gap-6 lg:grid-cols-2">

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">ACQUISITION TREND</h3>
                    <p class="mt-1 text-xs text-gray-400">New partners per month · last 12 months</p>
                </div>
                <div class="mt-4 h-64"><canvas id="anAcqChart" role="img" aria-label="Bar chart of new partners per month"></canvas></div>
            </div>

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">PARTNER TYPE MIX</h3>
                    <p class="mt-1 text-xs text-gray-400">Count and revenue by partner type</p>
                </div>
                <div class="mt-4 h-64"><canvas id="anTypeChart" role="img" aria-label="Bar chart comparing partner counts and revenue"></canvas></div>
            </div>
        </div>

        <!-- Chart row 2: Retention + Top partners -->
        <div class="mt-6 grid gap-6 lg:grid-cols-2">

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">PARTNER RETENTION</h3>
                    <p class="mt-1 text-xs text-gray-400">Active · Inactive · Churned (60d no sale)</p>
                </div>
                <div class="mt-4 h-64"><canvas id="anRetentionChart" role="img" aria-label="Doughnut chart of partner retention"></canvas></div>
            </div>

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-heading text-2xl tracking-wide text-white">TOP PARTNERS</h3>
                        <p class="mt-1 text-xs text-gray-400">Highest revenue contributors</p>
                    </div>
                    <?php if (!empty($topPartnerValues)): ?>
                        <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                            Top <?= count($topPartnerValues) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (empty($topPartnerValues)): ?>
                    <div class="mt-4 flex h-64 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 bg-white/[0.02]">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/5 text-gray-500">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm text-gray-400">No approved sales in this period.</p>
                    </div>
                <?php else: ?>
                    <div class="mt-4 h-64"><canvas id="anTopPartnersChart" role="img" aria-label="Top partners by revenue"></canvas></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chart row 3: Payout reconciliation + B2B clients -->
        <div class="mt-6 grid gap-6 lg:grid-cols-2">

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-heading text-2xl tracking-wide text-white">PAYOUT RECONCILIATION</h3>
                        <p class="mt-1 text-xs text-gray-400">Approved payouts per month · last 12 months</p>
                    </div>
                    <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                        <?= peso(array_sum($reconValues)) ?>
                    </span>
                </div>
                <div class="mt-4 h-64"><canvas id="anReconChart" role="img" aria-label="Line chart of monthly approved payouts"></canvas></div>
            </div>

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-heading text-2xl tracking-wide text-white">B2B CLIENTS BY PARTNER</h3>
                        <p class="mt-1 text-xs text-gray-400">Top referrers by client count</p>
                    </div>
                    <span class="rounded-full border border-purple-500/30 bg-purple-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-purple-400">
                        <?= (int)($b2bSummary['linked'] ?? 0) ?> linked
                    </span>
                </div>

                <?php if (empty($b2bByPartner)): ?>
                    <div class="mt-4 flex h-64 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 bg-white/[0.02]">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/5 text-gray-500">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm text-gray-400">No B2B clients yet.</p>
                    </div>
                <?php else: ?>
                    <div class="mt-4 h-64"><canvas id="anB2bClientsChart" role="img" aria-label="Top referrers by client count"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ============================================================
         PAYMENT ANALYTICS
         ============================================================ -->
    <section role="region" aria-label="Payment analytics">
        <div class="mb-4">
            <h2 class="font-heading text-3xl tracking-wide text-white">PAYMENT ANALYTICS</h2>
            <p class="mt-1 text-sm text-gray-400">Revenue splits, outcomes, and aging.</p>
        </div>

        <!-- Row 1: B2C vs B2B + Tier -->
        <div class="grid gap-6 lg:grid-cols-2">

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-heading text-2xl tracking-wide text-white">B2C VS B2B REVENUE</h3>
                        <p class="mt-1 text-xs text-gray-400">Revenue split by source</p>
                    </div>
                    <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                        <?= peso($totalRevenue) ?>
                    </span>
                </div>
                <div class="mt-4 h-72"><canvas id="pieRevenue" role="img" aria-label="B2C vs B2B revenue split"></canvas></div>
            </div>

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">REVENUE BY TIER</h3>
                    <p class="mt-1 text-xs text-gray-400">Which plans drive the most revenue</p>
                </div>
                <div class="mt-4 h-72"><canvas id="tierBar" role="img" aria-label="Revenue by tier"></canvas></div>
            </div>
        </div>

        <!-- Row 2: Outcomes + Aging -->
        <div class="mt-6 grid gap-6 lg:grid-cols-2">

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-heading text-2xl tracking-wide text-white">PAYMENT OUTCOMES</h3>
                        <p class="mt-1 text-xs text-gray-400">Paid · Failed · Refunded · Pending</p>
                    </div>
                    <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                        <?= number_format($successRate, 1) ?>% success
                    </span>
                </div>
                <div class="mt-4 h-72"><canvas id="outcomePie" role="img" aria-label="Payment outcomes"></canvas></div>
            </div>

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-heading text-2xl tracking-wide text-white">AGING SNAPSHOT</h3>
                        <p class="mt-1 text-xs text-gray-400">Unpaid balance by days overdue</p>
                    </div>
                    <?php if ($hasAging): ?>
                        <a href="<?= url_e('/admin-dashboard/payments/aging') ?>"
                            class="tap-effect inline-flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-500 transition-all hover:-translate-y-0.5 hover:bg-amber-500/20"
                            download>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Export
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!$hasAging): ?>
                    <div class="mt-4 flex h-72 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 bg-white/[0.02]">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-500/10 text-green-500">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-green-500">No outstanding balances</p>
                        <p class="mt-1 text-xs text-gray-400">Every payment is settled.</p>
                    </div>
                <?php else: ?>
                    <div class="mt-4 h-72"><canvas id="agingBar" role="img" aria-label="Aging buckets"></canvas></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Row 3: Revenue trend + Member growth -->
        <div class="mt-6 grid gap-6 lg:grid-cols-2">

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">REVENUE TREND</h3>
                    <p class="mt-1 text-xs text-gray-400">Daily revenue over the period</p>
                </div>
                <div class="mt-4 h-72"><canvas id="revenueTrend" role="img" aria-label="Daily revenue trend"></canvas></div>
            </div>

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">MEMBER GROWTH</h3>
                    <p class="mt-1 text-xs text-gray-400">Cumulative members over time</p>
                </div>
                <div class="mt-4 h-72"><canvas id="memberGrowth" role="img" aria-label="Member growth trend"></canvas></div>
            </div>
        </div>

        <!-- Row 4: Methods + Enrollment status -->
        <div class="mt-6 grid gap-6 lg:grid-cols-2">

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">PAYMENT METHODS</h3>
                    <p class="mt-1 text-xs text-gray-400">GCash · Cash · Card · Bank</p>
                </div>
                <div class="mt-4 h-72"><canvas id="methodPie" role="img" aria-label="Payment methods revenue"></canvas></div>
            </div>

            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">ENROLLMENT STATUS</h3>
                    <p class="mt-1 text-xs text-gray-400">Current state of all enrollments</p>
                </div>
                <div class="mt-4 h-72"><canvas id="enrollPie" role="img" aria-label="Enrollment status distribution"></canvas></div>
            </div>
        </div>

        <!-- Performance summary -->
        <div class="mt-6 rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <h3 class="font-heading text-2xl tracking-wide text-white">PERFORMANCE SUMMARY</h3>
            <p class="mt-1 text-xs text-gray-400">Key metrics for this period</p>

            <dl class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <div class="rounded-xl border border-[#00A6A6]/20 bg-[#00A6A6]/5 p-4">
                    <dt class="text-[10px] font-bold uppercase tracking-widest text-[#00A6A6]">B2C Revenue</dt>
                    <dd class="mt-1 font-heading text-2xl text-[#00A6A6]"><?= peso($b2cTotal) ?></dd>
                    <dd class="mt-1 text-xs text-gray-400">
                        <?= $totalRevenue > 0 ? round(($b2cTotal / $totalRevenue) * 100) : 0 ?>% of total
                    </dd>
                </div>

                <div class="rounded-xl border border-red-500/20 bg-red-500/5 p-4">
                    <dt class="text-[10px] font-bold uppercase tracking-widest text-red-500">B2B Revenue</dt>
                    <dd class="mt-1 font-heading text-2xl text-red-500"><?= peso($b2bTotal) ?></dd>
                    <dd class="mt-1 text-xs text-gray-400">
                        <?= $totalRevenue > 0 ? round(($b2bTotal / $totalRevenue) * 100) : 0 ?>% of total
                    </dd>
                </div>

                <div class="rounded-xl border border-purple-500/20 bg-purple-500/5 p-4">
                    <dt class="text-[10px] font-bold uppercase tracking-widest text-purple-400">Refunded</dt>
                    <dd class="mt-1 font-heading text-2xl text-purple-400"><?= peso($refundedAmount) ?></dd>
                    <dd class="mt-1 text-xs text-gray-400">
                        <?= $totalRevenue > 0 ? round(($refundedAmount / $totalRevenue) * 100, 1) : 0 ?>% of gross
                    </dd>
                </div>

                <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                    <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Members</dt>
                    <dd class="mt-1 font-heading text-2xl text-white"><?= $memberCount ?></dd>
                    <dd class="mt-1 text-xs text-gray-400">All registered</dd>
                </div>

                <div class="rounded-xl border border-green-500/20 bg-green-500/5 p-4">
                    <dt class="text-[10px] font-bold uppercase tracking-widest text-green-500">Active</dt>
                    <dd class="mt-1 font-heading text-2xl text-green-500"><?= $enrollStatus['active'] ?></dd>
                    <dd class="mt-1 text-xs text-gray-400">Enrollments</dd>
                </div>

                <div class="rounded-xl border border-blue-500/20 bg-blue-500/5 p-4">
                    <dt class="text-[10px] font-bold uppercase tracking-widest text-blue-400">Paused</dt>
                    <dd class="mt-1 font-heading text-2xl text-blue-400"><?= $enrollStatus['paused'] ?></dd>
                    <dd class="mt-1 text-xs text-gray-400">Frozen clock</dd>
                </div>

                <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-4">
                    <dt class="text-[10px] font-bold uppercase tracking-widest text-amber-500">Pending</dt>
                    <dd class="mt-1 font-heading text-2xl text-amber-500"><?= $enrollStatus['pending'] ?></dd>
                    <dd class="mt-1 text-xs text-gray-400">Awaiting</dd>
                </div>

                <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                    <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Expired</dt>
                    <dd class="mt-1 font-heading text-2xl text-gray-400"><?= $enrollStatus['expired'] ?></dd>
                    <dd class="mt-1 text-xs text-gray-400">Past due</dd>
                </div>
            </dl>
        </div>

        <!-- Payment configuration panel -->
        <div class="mt-6 rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-heading text-2xl tracking-wide text-white">PAYMENT CONFIGURATION</h3>
                        <p class="mt-0.5 text-xs text-gray-400">Method availability, collection frequency, and grace periods</p>
                    </div>
                </div>
                <a href="<?= url_e('/admin-dashboard?tab=settings') ?>"
                    class="tap-effect inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-6 py-3 text-xs font-bold text-white transition-all duration-300 hover:border-[#00A6A6] hover:bg-[#00A6A6] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Edit Settings
                </a>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Accepted Methods</div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <?php foreach (Payment::METHODS as $m):
                            $enabled = in_array($m, $methodCfg['accepted_methods'] ?? [], true);
                        ?>
                            <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase <?= $enabled ? 'bg-green-500/10 text-green-500 border border-green-500/20' : 'bg-white/5 text-gray-500 border border-white/10 line-through' ?>">
                                <?= e(Payment::methodLabel($m)) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Collection Frequency</div>
                    <div class="mt-2 font-heading text-xl text-white">
                        <?= e(ucfirst($methodCfg['collection_frequency'] ?? 'monthly')) ?>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">Recurring collection schedule</div>
                </div>

                <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Late Payment Grace</div>
                    <div class="mt-2 font-heading text-xl text-white">
                        <?= (int)($methodCfg['grace_days'] ?? 7) ?> days
                    </div>
                    <div class="mt-1 text-xs text-gray-400">Before marked late</div>
                </div>
            </div>
        </div>
    </section>
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

    @media (prefers-reduced-motion: reduce) {
        [style*="fade-in"] {
            animation: none !important;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function() {
        'use strict';

        const isDark = document.documentElement.classList.contains('dark');
        const gridClr = isDark ? '#334155' : '#e5e7eb';
        const tickClr = isDark ? '#94a3b8' : '#4b5563';
        const TEAL = '#00A6A6';
        const RED = '#D50000';
        const AMBER = '#f59e0b';
        const BLUE = '#3b82f6';
        const PURPLE = '#9333ea';
        const GREEN = '#22c55e';
        const GRAY = '#94a3b8';

        Chart.defaults.font.family = 'Montserrat, sans-serif';
        Chart.defaults.color = tickClr;

        const baseOpts = {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 900,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    labels: {
                        color: tickClr,
                        font: {
                            size: 11
                        },
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 14
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#f1f5f9',
                    bodyColor: '#cbd5e1',
                    padding: 12,
                    cornerRadius: 10
                }
            }
        };

        const money = (v) => '₱' + Number(v).toLocaleString();

        /* ============================================================
         *  Partner analytics
         * ============================================================ */

        /* --- Acquisition --- */
        const acqData = {
            labels: <?= json_encode($acqLabels) ?>,
            values: <?= json_encode($acqValues) ?>
        };
        const acqEl = document.getElementById('anAcqChart');
        if (acqEl) {
            if (acqData.labels.length === 0) {
                acqEl.parentElement.innerHTML = '<div class="flex h-full items-center justify-center text-sm text-gray-500">No acquisition data yet</div>';
            } else {
                const ctx = acqEl.getContext('2d');
                const grad = ctx.createLinearGradient(0, 0, 0, 260);
                grad.addColorStop(0, TEAL);
                grad.addColorStop(1, 'rgba(0,166,166,0.3)');

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: acqData.labels.map(m => {
                            const [y, mo] = m.split('-');
                            return new Date(y, mo - 1).toLocaleDateString('en', {
                                month: 'short',
                                year: '2-digit'
                            });
                        }),
                        datasets: [{
                            data: acqData.values,
                            backgroundColor: grad,
                            hoverBackgroundColor: TEAL,
                            borderRadius: 8,
                            borderSkipped: false,
                            maxBarThickness: 32
                        }]
                    },
                    options: {
                        ...baseOpts,
                        plugins: {
                            ...baseOpts.plugins,
                            legend: {
                                display: false
                            },
                            tooltip: {
                                ...baseOpts.plugins.tooltip,
                                callbacks: {
                                    label: (i) => i.raw + ' partner' + (i.raw !== 1 ? 's' : '')
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: tickClr,
                                    font: {
                                        size: 10
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: gridClr,
                                    drawBorder: false
                                },
                                ticks: {
                                    color: tickClr,
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        }

        /* --- Type mix --- */
        const typeData = {
            labels: <?= json_encode($typeLabels) ?>,
            counts: <?= json_encode($typeCounts) ?>,
            revenue: <?= json_encode($typeRev) ?>
        };
        const typeEl = document.getElementById('anTypeChart');
        if (typeEl && typeData.labels.length > 0) {
            new Chart(typeEl, {
                type: 'bar',
                data: {
                    labels: typeData.labels,
                    datasets: [{
                            label: 'Partners',
                            data: typeData.counts,
                            backgroundColor: TEAL,
                            borderRadius: 8,
                            borderSkipped: false,
                            yAxisID: 'y',
                            maxBarThickness: 60
                        },
                        {
                            label: 'Revenue (₱)',
                            data: typeData.revenue,
                            backgroundColor: PURPLE,
                            borderRadius: 8,
                            borderSkipped: false,
                            yAxisID: 'y1',
                            maxBarThickness: 60
                        }
                    ]
                },
                options: {
                    ...baseOpts,
                    plugins: {
                        ...baseOpts.plugins,
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => i.datasetIndex === 0 ?
                                    i.raw + ' partner' + (i.raw !== 1 ? 's' : '') :
                                    money(i.raw)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickClr
                            }
                        },
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            grid: {
                                color: gridClr,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickClr,
                                precision: 0
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickClr,
                                callback: (v) => '₱' + Number(v).toLocaleString(),
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }

        /* --- Retention --- */
        const retentionData = {
            active: <?= (int)($retentionPartners['active']   ?? 0) ?>,
            inactive: <?= (int)($retentionPartners['inactive'] ?? 0) ?>,
            churned: <?= (int)($retentionPartners['churned']  ?? 0) ?>
        };
        const retEl = document.getElementById('anRetentionChart');
        if (retEl) {
            new Chart(retEl, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Inactive', 'Churned (60d)'],
                    datasets: [{
                        data: [retentionData.active, retentionData.inactive, retentionData.churned],
                        backgroundColor: [GREEN, GRAY, RED],
                        borderWidth: 0,
                        hoverOffset: 12
                    }]
                },
                options: {
                    ...baseOpts,
                    cutout: '68%',
                    plugins: {
                        ...baseOpts.plugins,
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => i.label + ': ' + i.raw
                            }
                        }
                    }
                }
            });
        }

        /* --- Top partners --- */
        const topPartners = {
            labels: <?= json_encode($topPartnerLabels) ?>,
            values: <?= json_encode($topPartnerValues) ?>
        };
        const topEl = document.getElementById('anTopPartnersChart');
        if (topEl && topPartners.labels.length > 0) {
            new Chart(topEl, {
                type: 'bar',
                data: {
                    labels: topPartners.labels,
                    datasets: [{
                        data: topPartners.values,
                        backgroundColor: TEAL,
                        hoverBackgroundColor: '#008c8c',
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 32
                    }]
                },
                options: {
                    ...baseOpts,
                    indexAxis: 'y',
                    plugins: {
                        ...baseOpts.plugins,
                        legend: {
                            display: false
                        },
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => money(i.raw)
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: gridClr,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickClr,
                                callback: (v) => '₱' + Number(v).toLocaleString(),
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickClr,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }

        /* --- Payout reconciliation --- */
        const reconData = {
            labels: <?= json_encode($reconLabels) ?>,
            values: <?= json_encode($reconValues) ?>
        };
        const reconEl = document.getElementById('anReconChart');
        if (reconEl) {
            if (reconData.labels.length === 0) {
                reconEl.parentElement.innerHTML = '<div class="flex h-full items-center justify-center text-sm text-gray-500">No payout history yet</div>';
            } else {
                const ctx = reconEl.getContext('2d');
                const grad = ctx.createLinearGradient(0, 0, 0, 260);
                grad.addColorStop(0, 'rgba(147,51,234,0.35)');
                grad.addColorStop(1, 'rgba(147,51,234,0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: reconData.labels.map(m => {
                            const [y, mo] = m.split('-');
                            return new Date(y, mo - 1).toLocaleDateString('en', {
                                month: 'short',
                                year: '2-digit'
                            });
                        }),
                        datasets: [{
                            data: reconData.values,
                            borderColor: PURPLE,
                            backgroundColor: grad,
                            borderWidth: 3,
                            tension: 0.35,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: PURPLE,
                            pointBorderWidth: 3,
                            pointHoverBackgroundColor: PURPLE,
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3
                        }]
                    },
                    options: {
                        ...baseOpts,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            ...baseOpts.plugins,
                            legend: {
                                display: false
                            },
                            tooltip: {
                                ...baseOpts.plugins.tooltip,
                                callbacks: {
                                    label: (i) => money(i.raw)
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: tickClr,
                                    font: {
                                        size: 10
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: gridClr,
                                    drawBorder: false
                                },
                                ticks: {
                                    color: tickClr,
                                    font: {
                                        size: 10
                                    },
                                    callback: (v) => '₱' + Number(v).toLocaleString()
                                }
                            }
                        }
                    }
                });
            }
        }

        /* --- B2B clients by partner --- */
        const b2bData = <?= json_encode($b2bByPartner, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const b2bEl = document.getElementById('anB2bClientsChart');
        if (b2bEl && b2bData && b2bData.length > 0) {
            const sorted = [...b2bData].sort((a, b) => b.clients - a.clients).slice(0, 8);

            new Chart(b2bEl, {
                type: 'bar',
                data: {
                    labels: sorted.map(r => r.business_name),
                    datasets: [{
                            label: 'Total clients',
                            data: sorted.map(r => r.clients),
                            backgroundColor: PURPLE,
                            borderRadius: 8,
                            borderSkipped: false,
                            maxBarThickness: 28,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Linked',
                            data: sorted.map(r => r.linked),
                            backgroundColor: TEAL,
                            borderRadius: 8,
                            borderSkipped: false,
                            maxBarThickness: 28,
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    ...baseOpts,
                    indexAxis: 'y',
                    plugins: {
                        ...baseOpts.plugins,
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => i.dataset.label + ': ' + i.raw
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: gridClr,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickClr,
                                precision: 0
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickClr,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }

        /* ============================================================
         *  Payment analytics
         * ============================================================ */

        /* --- B2C vs B2B --- */
        const pieEl = document.getElementById('pieRevenue');
        if (pieEl) {
            new Chart(pieEl, {
                type: 'doughnut',
                data: {
                    labels: ['B2C (Members)', 'B2B (Partners)'],
                    datasets: [{
                        data: [<?= (float)$b2cTotal ?>, <?= (float)$b2bTotal ?>],
                        backgroundColor: [TEAL, RED],
                        borderWidth: 0,
                        hoverOffset: 12
                    }]
                },
                options: {
                    ...baseOpts,
                    cutout: '68%',
                    plugins: {
                        ...baseOpts.plugins,
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => i.label + ': ' + money(i.raw)
                            }
                        }
                    }
                }
            });
        }

        /* --- Revenue by tier --- */
        const tierEl = document.getElementById('tierBar');
        if (tierEl) {
            new Chart(tierEl, {
                type: 'bar',
                data: {
                    labels: ['Digital', 'Standard', 'Premium', 'B2B'],
                    datasets: [{
                        data: [
                            <?= (float)$tierTotals['digital'] ?>,
                            <?= (float)$tierTotals['standard'] ?>,
                            <?= (float)$tierTotals['premium'] ?>,
                            <?= (float)$tierTotals['b2b'] ?>
                        ],
                        backgroundColor: [TEAL, TEAL, RED, PURPLE],
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 60
                    }]
                },
                options: {
                    ...baseOpts,
                    plugins: {
                        ...baseOpts.plugins,
                        legend: {
                            display: false
                        },
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => money(i.raw)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickClr
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridClr,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickClr,
                                callback: (v) => '₱' + Number(v).toLocaleString()
                            }
                        }
                    }
                }
            });
        }

        /* --- Outcomes --- */
        const outcomeEl = document.getElementById('outcomePie');
        if (outcomeEl) {
            new Chart(outcomeEl, {
                type: 'doughnut',
                data: {
                    labels: ['Paid', 'Pending', 'Failed', 'Refunded'],
                    datasets: [{
                        data: [
                            <?= (int)($paymentSummary['paid'] ?? 0) ?>,
                            <?= (int)($paymentSummary['pending'] ?? 0) ?>,
                            <?= (int)($paymentSummary['failed'] ?? 0) ?>,
                            <?= (int)($paymentSummary['refunded'] ?? 0) + (int)($paymentSummary['partial_refund'] ?? 0) ?>
                        ],
                        backgroundColor: [GREEN, AMBER, RED, PURPLE],
                        borderWidth: 0,
                        hoverOffset: 12
                    }]
                },
                options: {
                    ...baseOpts,
                    cutout: '68%',
                    plugins: {
                        ...baseOpts.plugins,
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => i.label + ': ' + i.raw
                            }
                        }
                    }
                }
            });
        }

        /* --- Aging --- */
        const agingEl = document.getElementById('agingBar');
        if (agingEl) {
            new Chart(agingEl, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($agingLabels) ?>,
                    datasets: [{
                        label: 'Amount (₱)',
                        data: <?= json_encode($agingAmounts) ?>,
                        backgroundColor: [GREEN, '#84cc16', AMBER, '#f97316', RED],
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 48
                    }]
                },
                options: {
                    ...baseOpts,
                    plugins: {
                        ...baseOpts.plugins,
                        legend: {
                            display: false
                        },
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                title: (items) => items[0].label + ' overdue',
                                label: (i) => {
                                    const count = <?= json_encode($agingCounts) ?>[i.dataIndex];
                                    return ['Amount: ' + money(i.raw), 'Count: ' + count];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickClr
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridClr,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickClr,
                                callback: (v) => '₱' + Number(v).toLocaleString()
                            }
                        }
                    }
                }
            });
        }

        /* --- Revenue trend --- */
        const trendEl = document.getElementById('revenueTrend');
        if (trendEl) {
            const ctx = trendEl.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 300);
            grad.addColorStop(0, 'rgba(0,166,166,0.35)');
            grad.addColorStop(1, 'rgba(0,166,166,0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_map(fn($d) => date('M j', strtotime($d)), $revenueLabels)) ?>,
                    datasets: [{
                        data: <?= json_encode($revenueValues) ?>,
                        borderColor: TEAL,
                        backgroundColor: grad,
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: TEAL,
                        pointBorderWidth: 3,
                        pointHoverBackgroundColor: TEAL,
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    ...baseOpts,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        ...baseOpts.plugins,
                        legend: {
                            display: false
                        },
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => money(i.raw)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickClr,
                                maxTicksLimit: 10,
                                autoSkip: true,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridClr,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickClr,
                                callback: (v) => '₱' + Number(v).toLocaleString()
                            }
                        }
                    }
                }
            });
        }

        /* --- Member growth --- */
        const growthEl = document.getElementById('memberGrowth');
        if (growthEl) {
            const ctx = growthEl.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 300);
            grad.addColorStop(0, 'rgba(0,166,166,0.25)');
            grad.addColorStop(1, 'rgba(0,166,166,0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_map(fn($d) => date('M j', strtotime($d)), $growthLabels)) ?>,
                    datasets: [{
                        data: <?= json_encode($growthValues) ?>,
                        borderColor: TEAL,
                        backgroundColor: grad,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 7,
                        pointBackgroundColor: TEAL,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 3,
                        pointHoverBackgroundColor: TEAL,
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    ...baseOpts,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        ...baseOpts.plugins,
                        legend: {
                            display: false
                        },
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => i.raw + ' members'
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickClr,
                                maxTicksLimit: 10,
                                autoSkip: true,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridClr,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickClr,
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        /* --- Payment methods --- */
        const methodEl = document.getElementById('methodPie');
        if (methodEl) {
            new Chart(methodEl, {
                type: 'doughnut',
                data: {
                    labels: ['GCash', 'Cash', 'Card', 'Bank'],
                    datasets: [{
                        data: [
                            <?= (float)($methodTotals['gcash'] ?? 0) ?>,
                            <?= (float)($methodTotals['cash'] ?? 0) ?>,
                            <?= (float)($methodTotals['credit_card'] ?? 0) ?>,
                            <?= (float)($methodTotals['bank_transfer'] ?? 0) ?>
                        ],
                        backgroundColor: [TEAL, AMBER, BLUE, PURPLE],
                        borderWidth: 0,
                        hoverOffset: 12
                    }]
                },
                options: {
                    ...baseOpts,
                    cutout: '68%',
                    plugins: {
                        ...baseOpts.plugins,
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => i.label + ': ' + money(i.raw)
                            }
                        }
                    }
                }
            });
        }

        /* --- Enrollment status --- */
        const enrollEl = document.getElementById('enrollPie');
        if (enrollEl) {
            new Chart(enrollEl, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Pending', 'Paused', 'Expired', 'Cancelled'],
                    datasets: [{
                        data: [
                            <?= (int)($enrollStatus['active']    ?? 0) ?>,
                            <?= (int)($enrollStatus['pending']   ?? 0) ?>,
                            <?= (int)($enrollStatus['paused']    ?? 0) ?>,
                            <?= (int)($enrollStatus['expired']   ?? 0) ?>,
                            <?= (int)($enrollStatus['cancelled'] ?? 0) ?>
                        ],
                        backgroundColor: [GREEN, AMBER, BLUE, GRAY, '#9ca3af'],
                        borderWidth: 0,
                        hoverOffset: 12
                    }]
                },
                options: {
                    ...baseOpts,
                    cutout: '68%',
                    plugins: {
                        ...baseOpts.plugins,
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => i.label + ': ' + i.raw
                            }
                        }
                    }
                }
            });
        }
    })();
</script>