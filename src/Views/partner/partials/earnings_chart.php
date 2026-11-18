<?php

/**
 * Partner — Earnings Chart (2026 UI)
 * Expects $sales array from parent view
 */

$sales = $sales ?? [];

// ---------- Bucket approved sales by day ----------
$byDay = [];
foreach ($sales as $s) {
    if (($s['status'] ?? '') !== 'approved') continue;
    $day = substr($s['sale_date'] ?? '', 0, 10);
    if ($day === '') continue;
    $byDay[$day] = ($byDay[$day] ?? 0) + (float)($s['amount'] ?? 0);
}
ksort($byDay);

$days    = array_keys($byDay);
$amounts = array_values($byDay);

$totalApproved = array_sum($amounts);
$hasData = !empty($days);
?>

<div class="rounded-2xl border border-white/5 bg-[#111] p-6 sm:p-8 shadow-sm transition-all duration-300 hover:border-[#00A6A6]/30 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.15)]">

    <!-- Header -->
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">EARNINGS OVER TIME</h3>
                <p class="mt-1 text-xs text-gray-400">Approved sales chart</p>
            </div>
        </div>

        <?php if ($hasData): ?>
            <div class="flex items-center gap-2 rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-4 py-2">
                <div class="text-[10px] font-bold uppercase tracking-widest text-[#00A6A6]">Total</div>
                <div class="font-heading text-lg text-[#00A6A6]"><?= peso($totalApproved) ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Chart or empty state -->
    <?php if (!$hasData): ?>
        <div class="mt-6 flex h-64 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 bg-white/[0.01] p-8 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
            <p class="mt-4 text-sm font-semibold text-gray-400">No approved sales yet</p>
            <p class="mt-1 max-w-xs text-xs text-gray-500">
                Log sales and have them approved by the gym to see your earnings grow here.
            </p>
            <a href="<?= url_e('/partner-dashboard/sales') ?>"
                class="tap-effect mt-6 inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300
                hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Log a Sale
            </a>
        </div>
    <?php else: ?>
        <div class="mt-6 h-64"><canvas id="earningsChart"></canvas></div>
    <?php endif; ?>
</div>

<?php if ($hasData): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        (function() {
            const canvas = document.getElementById('earningsChart');
            if (!canvas) return;

            const isDark = document.documentElement.classList.contains('dark');
            const tickColor = isDark ? '#94a3b8' : '#6b7280';
            const gridColor = isDark ? '#334155' : '#e5e7eb';

            const labels = <?= json_encode(array_map(fn($d) => date('M j', strtotime($d)), $days)) ?>;
            const values = <?= json_encode($amounts) ?>;

            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, 'rgba(0,166,166,0.35)');
            gradient.addColorStop(1, 'rgba(0,166,166,0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        borderColor: '#00A6A6',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#00A6A6',
                        pointBorderWidth: 3,
                        pointHoverBackgroundColor: '#00A6A6',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 900,
                        easing: 'easeOutQuart'
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
                            padding: 12,
                            cornerRadius: 10,
                            displayColors: false,
                            callbacks: {
                                label: (item) => '₱' + Number(item.raw).toLocaleString()
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickColor,
                                font: {
                                    size: 11
                                },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 8
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickColor,
                                font: {
                                    size: 11
                                },
                                callback: (v) => '₱' + Number(v).toLocaleString()
                            }
                        }
                    }
                }
            });
        })();
    </script>
<?php endif; ?>