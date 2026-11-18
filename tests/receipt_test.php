<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Models\Payment;

function check(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$p = [
    'receipt_no'     => 'PMZ-2026-00001',
    'user_name'      => 'Test <script>alert(1)</script>',
    'description'    => 'Premium All-Inclusive enrollment',
    'tier'           => 'premium',
    'method'         => 'gcash',
    'reference_no'   => 'REF123456',
    'paid_date'      => '2026-11-01',
    'confirmed_date' => '2026-11-01 10:30:00',
    'amount'         => '2000.00',
    'refund_amount'  => '500.00',
    'refund_reason'  => 'Partial refund',
    'status'         => 'partial_refund',
];

$user = ['email' => 'member@example.com'];
$statusKey = 'partial_refund';
$statusLabel = Payment::statusLabel($statusKey);
$formatDate = static fn (?string $v): string => $v ? date('M j, Y', strtotime($v)) : '—';
$formatDateTime = static fn (?string $v): string => $v ? date('M j, Y g:i A', strtotime($v)) : '—';

ob_start();
require __DIR__ . '/../src/Views/admin/payment_receipt.php';
$html = (string)ob_get_clean();

check(str_contains($html, 'powermagz_gym.png'), 'receipt uses the real logo');
check(!preg_match('/[\x{1F300}-\x{1FAFF}]/u', $html), 'receipt contains no emoji');
check(str_contains($html, 'Net paid'), 'refunded receipt shows net paid');
check(str_contains($html, '₱1,500.00'), 'net paid math is correct');
check(str_contains($html, 'PMZ-2026-00001'), 'receipt number is rendered');
check(str_contains($html, 'status-partial_refund'), 'status class is rendered');
check(!str_contains($html, '<script>alert(1)</script>'), 'member name is escaped');
check(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;'), 'escaped member name is present');

echo "OK receipt\n";
