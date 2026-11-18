<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Models\PartnerSale;
use App\Models\PayoutRequest;

function check(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$pdo = \App\Config\Database::pdo();

// Pure commission math.
check(PartnerSale::commissionFor(['amount' => 1000, 'commission_amount' => 100]) === 100.0, 'stored commission wins');
check(PartnerSale::commissionFor(['amount' => 1000, 'commission_rate' => 15]) === 150.0, 'rate is applied');
check(PartnerSale::commissionFor(['amount' => 1000]) === 100.0, 'default is 10%');
check(PartnerSale::commissionFor(['amount' => 1000, 'commission_rate' => 0]) === 100.0, 'zero rate falls back to 10%');

// Isolated partner so no existing sales/payouts affect the balance.
$partner = \App\Core\Model::uuid();
$pdo->prepare("INSERT INTO users (id, email, password_hash, role) VALUES (?, ?, 'x', 'partner')")
    ->execute([$partner, 'payout-test-' . $partner . '@example.test']);

$sale = PartnerSale::create([
    'partner_user_id'   => $partner,
    'type'              => 'session',
    'description'       => 'Payout test sale',
    'amount'            => 1000,
    'sale_date'         => date('Y-m-d'),
    'status'            => 'approved',
    'commission_rate'   => 10,
    'commission_amount' => 100,
]);

$balance = PayoutRequest::balanceFor($partner);
check($balance['lifetime'] === 100.0, 'lifetime commission is 100');
check($balance['available'] === 100.0, 'available starts at 100');

$payout = PayoutRequest::requestPayout($partner, 100, 'cash');
check($payout !== null && (float)$payout['amount'] === 100.0, 'full balance payout is accepted');
check((float)PayoutRequest::balanceFor($partner)['available'] === 0.0, 'pending payout reserves the funds');

$threw = false;
try {
    PayoutRequest::requestPayout($partner, 100, 'cash');
} catch (\RuntimeException $e) {
    $threw = true;
}
check($threw, 'second payout over the balance is rejected');

$payouts = PayoutRequest::where(['partner_user_id' => $partner]);
check(count($payouts) === 1, 'rejected payout was not inserted');

foreach ($payouts as $p) PayoutRequest::delete($p['id']);
PartnerSale::delete($sale['id']);
$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$partner]);

echo "OK payouts + commission\n";
