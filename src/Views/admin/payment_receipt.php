<?php
/**
 * Printable admin payment receipt.
 *
 * Expects: $p (payment row), $user (member row|null), $statusKey,
 * $statusLabel, $formatDate, $formatDateTime.
 */
$amount  = (float)($p['amount'] ?? 0);
$refund  = (float)($p['refund_amount'] ?? 0);
$netPaid = max(0, $amount - $refund);
$totalLabel = $refund > 0
    ? 'Net paid'
    : ($statusKey === 'paid' ? 'Total paid' : 'Amount');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Receipt <?= e($p['receipt_no']) ?> — PowerMagz Gym</title>
    <link rel="icon" href="<?= url_e('/public/images/powermagz_gym.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --teal: #00A6A6;
            --teal-dark: #008c8c;
            --red: #D50000;
            --ink: #0f1720;
            --muted: #68727d;
            --line: #e4e9ec;
            --soft: #f6f8f8;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
        }

        body {
            padding: 32px 16px 88px;
            background: #e9eef0;
            color: var(--ink);
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .sheet {
            max-width: 760px;
            margin: 0 auto;
            overflow: hidden;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 24px 60px -24px rgba(15, 23, 32, 0.35);
        }

        .accent {
            height: 6px;
            background: linear-gradient(90deg, var(--teal) 0%, var(--teal-dark) 68%, var(--red) 100%);
        }

        .header {
            display: flex;
            align-items: flex-start;
            gap: 24px;
            padding: 34px 40px 28px;
        }

        .brand {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            min-width: 0;
        }

        .logo {
            width: 82px;
            height: 82px;
            flex-shrink: 0;
            border: 1px solid var(--line);
            border-radius: 50%;
            background: #fff;
            object-fit: contain;
        }

        .brand-name {
            font-family: 'Bebas Neue', Impact, sans-serif;
            font-size: 32px;
            line-height: 1;
            letter-spacing: 1.2px;
        }

        .brand-name span {
            color: var(--teal);
        }

        .brand-tag {
            margin-top: 5px;
            color: var(--teal);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .brand-line {
            margin-top: 7px;
            color: var(--muted);
            font-size: 10px;
            line-height: 1.65;
        }

        .meta {
            flex-shrink: 0;
            margin-left: auto;
            text-align: right;
        }

        .eyebrow {
            color: var(--teal);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .receipt-no {
            margin-top: 8px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .issued {
            margin-top: 5px;
            color: var(--muted);
            font-size: 11px;
        }

        .rule {
            height: 1px;
            margin: 0 40px;
            background: var(--line);
        }

        .parties {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr;
            gap: 22px;
            padding: 24px 40px;
            border-bottom: 1px solid var(--line);
            background: var(--soft);
        }

        .label {
            color: #8b949d;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .value {
            margin-top: 5px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .value.strong {
            color: var(--ink);
            font-size: 13px;
            font-weight: 700;
        }

        .items {
            padding: 26px 40px 8px;
        }

        .items-head,
        .items-row {
            display: grid;
            grid-template-columns: 1fr 110px 120px;
            gap: 16px;
        }

        .items-head {
            padding-bottom: 10px;
            border-bottom: 1px solid var(--line);
            color: #8b949d;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .items-row {
            align-items: start;
            padding: 16px 0;
            border-bottom: 1px solid var(--line);
        }

        .items-row:last-child {
            border-bottom: 0;
        }

        .desc {
            font-size: 13px;
            font-weight: 600;
            line-height: 1.55;
        }

        .tier {
            justify-self: start;
            padding: 4px 8px;
            border-radius: 999px;
            background: #e6f7f7;
            color: var(--teal-dark);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .money {
            font-size: 14px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .right {
            text-align: right;
        }

        .summary {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding: 8px 40px 28px;
        }

        .summary-lines {
            min-width: 280px;
            margin-left: auto;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 32px;
            padding: 5px 0;
            color: var(--muted);
            font-size: 12px;
            font-variant-numeric: tabular-nums;
        }

        .summary-row.total {
            margin-top: 6px;
            padding-top: 12px;
            border-top: 1px solid var(--line);
            color: var(--ink);
            font-size: 13px;
            font-weight: 800;
        }

        .summary-row.total strong {
            font-family: 'Bebas Neue', Impact, sans-serif;
            font-size: 30px;
            font-weight: 400;
            letter-spacing: 0.5px;
            line-height: 1;
        }

        .status {
            flex-shrink: 0;
            padding: 8px 14px;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .status-paid {
            border-color: #b9ebcd;
            background: #e7f8ee;
            color: #127a3e;
        }

        .status-pending {
            border-color: #f4dfa8;
            background: #fff8e6;
            color: #8a6100;
        }

        .status-failed {
            border-color: #f3c1c1;
            background: #fdecec;
            color: #b42318;
        }

        .status-refunded,
        .status-partial_refund {
            border-color: #d9c9f4;
            background: #f3ecff;
            color: #6b3fa0;
        }

        .refund-note {
            margin: 0 40px 28px;
            padding: 16px 18px;
            border: 1px solid #f4dfa8;
            border-radius: 12px;
            background: #fff8e6;
        }

        .refund-note p {
            margin: 5px 0 0;
            color: #7a5a10;
            font-size: 12px;
            line-height: 1.6;
        }

        .foot {
            padding: 24px 40px 30px;
            border-top: 1px solid var(--line);
            text-align: center;
        }

        .thanks {
            font-family: 'Bebas Neue', Impact, sans-serif;
            font-size: 20px;
            letter-spacing: 0.8px;
        }

        .foot p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 10px;
            line-height: 1.7;
        }

        .foot .contact {
            margin-top: 8px;
            color: #4a5560;
        }

        .toolbar {
            position: fixed;
            bottom: 22px;
            left: 50%;
            transform: translateX(-50%);
        }

        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 13px 24px;
            border: 0;
            border-radius: 999px;
            background: var(--ink);
            color: #fff;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 12px 30px -10px rgba(15, 23, 32, 0.6);
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .print-btn:hover {
            background: var(--teal);
            transform: translateY(-1px);
        }

        .print-btn svg {
            width: 16px;
            height: 16px;
        }

        @media (max-width: 640px) {
            body {
                padding: 16px 10px 88px;
            }

            .header {
                flex-direction: column;
                padding: 24px 22px 20px;
            }

            .meta {
                margin-left: 0;
                text-align: left;
            }

            .rule {
                margin: 0 22px;
            }

            .parties {
                grid-template-columns: 1fr;
                padding: 20px 22px;
            }

            .items {
                padding: 22px 22px 6px;
            }

            .items-head,
            .items-row {
                grid-template-columns: 1fr 90px;
            }

            .items-head span:nth-child(2),
            .items-row .tier {
                display: none;
            }

            .summary {
                flex-direction: column-reverse;
                padding: 6px 22px 24px;
            }

            .summary-lines {
                width: 100%;
                min-width: 0;
            }

            .refund-note {
                margin: 0 22px 24px;
            }

            .foot {
                padding: 20px 22px 24px;
            }

            .logo {
                width: 70px;
                height: 70px;
            }
        }

        @media print {
            @page {
                size: A4;
                margin: 12mm;
            }

            body {
                padding: 0;
                background: #fff;
            }

            .sheet {
                max-width: none;
                border-radius: 0;
                box-shadow: none;
            }

            .no-print {
                display: none !important;
            }

            .accent,
            .parties,
            .tier,
            .status,
            .refund-note {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <main class="sheet" role="main">
        <div class="accent"></div>

        <header class="header">
            <div class="brand">
                <img class="logo" src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="PowerMagz Gym logo">
                <div>
                    <div class="brand-name">POWERMAGZ <span>GYM</span></div>
                    <div class="brand-tag">Tabaco City · Albay</div>
                    <div class="brand-line">
                        Zone 4, Matagbac, Tabaco City, Albay<br>
                        0961 804 1523 · powermagzgym@gmail.com
                    </div>
                </div>
            </div>

            <div class="meta">
                <div class="eyebrow">Official receipt</div>
                <div class="receipt-no"><?= e($p['receipt_no']) ?></div>
                <div class="issued">Issued <?= e(date('M j, Y')) ?></div>
            </div>
        </header>

        <div class="rule"></div>

        <section class="parties">
            <div>
                <div class="label">Billed to</div>
                <div class="value strong"><?= e($p['user_name']) ?></div>
                <div class="value"><?= e($user['email'] ?? '—') ?></div>
            </div>
            <div>
                <div class="label">Payment method</div>
                <div class="value strong"><?= e(\App\Models\Payment::methodLabel((string)($p['method'] ?? ''))) ?></div>
                <div class="value">Ref <?= e(($p['reference_no'] ?? '') !== '' ? $p['reference_no'] : '—') ?></div>
            </div>
            <div>
                <div class="label">Payment date</div>
                <div class="value strong"><?= e($formatDate($p['paid_date'] ?? null)) ?></div>
                <div class="value">Confirmed <?= e($formatDateTime($p['confirmed_date'] ?? null)) ?></div>
            </div>
        </section>

        <section class="items">
            <div class="items-head">
                <span>Description</span>
                <span>Tier</span>
                <span class="right">Amount</span>
            </div>
            <div class="items-row">
                <span class="desc"><?= e(($p['description'] ?? '') !== '' ? $p['description'] : 'Membership payment') ?></span>
                <span class="tier"><?= e(strtoupper((string)($p['tier'] ?? '—'))) ?></span>
                <span class="money right">₱<?= number_format($amount, 2) ?></span>
            </div>
        </section>

        <section class="summary">
            <div class="status status-<?= e($statusKey) ?>"><?= e($statusLabel) ?></div>
            <div class="summary-lines">
                <?php if ($refund > 0): ?>
                    <div class="summary-row">
                        <span>Amount</span>
                        <span>₱<?= number_format($amount, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Refund applied</span>
                        <span>−₱<?= number_format($refund, 2) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Net paid</span>
                        <strong>₱<?= number_format($netPaid, 2) ?></strong>
                    </div>
                <?php else: ?>
                    <div class="summary-row total">
                        <span><?= e($totalLabel) ?></span>
                        <strong>₱<?= number_format($amount, 2) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($refund > 0): ?>
            <div class="refund-note">
                <div class="label">Refund reason</div>
                <p><?= e(($p['refund_reason'] ?? '') !== '' ? $p['refund_reason'] : 'Refund processed by PowerMagz Gym.') ?></p>
            </div>
        <?php endif; ?>

        <footer class="foot">
            <div class="thanks">Thank you for choosing PowerMagz Gym.</div>
            <p>This receipt is system-generated. Keep it for your records.</p>
            <p class="contact">Zone 4, Matagbac, Tabaco City, Albay · 0961 804 1523 · powermagzgym@gmail.com</p>
        </footer>
    </main>

    <div class="toolbar no-print">
        <button class="print-btn" type="button" onclick="window.print()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v7H6z" />
            </svg>
            Print / Save PDF
        </button>
    </div>
</body>

</html>
