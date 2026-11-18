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

check(Payment::isAcceptedMethod('paypal') === false, 'paypal is rejected');
check(Payment::isAcceptedMethod('') === false, 'empty method is rejected');

check(Payment::referenceError('gcash', '') !== null, 'GCash requires a reference');
check(Payment::referenceError('gcash', '123') !== null, 'short reference is rejected');
check(Payment::referenceError('gcash', '123456') === null, 'valid reference passes');
check(Payment::referenceError('gcash', 'ABC 123') !== null, 'reference rejects spaces');
check(Payment::referenceError('cash', '') === null, 'cash needs no reference');
check(Payment::referenceError('bank_transfer', 'BNK-778899') === null, 'bank reference passes');

$config = Payment::methodConfig();
check(is_array($config['accepted_methods']), 'method config returns accepted methods');
foreach ($config['accepted_methods'] as $m) {
    check(in_array($m, Payment::METHODS, true), "accepted method {$m} is known");
}

echo "OK payment methods\n";
