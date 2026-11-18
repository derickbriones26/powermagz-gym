<?php
declare(strict_types=1);

/**
 * Balance must follow the payments, even when the enrollment is already
 * active. Regression test for: approve payment -> reload -> balance returns.
 *
 * Run: php tests/enrollment_balance_test.php
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Program;
use App\Models\User;
use App\Config\Database;

function check(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$pdo = Database::pdo();

// A program with a real price to enroll into
$program = Program::where(['active' => 1, 'portal_visible' => 1])[0] ?? Program::all()[0] ?? null;
if (!$program) {
    fwrite(STDERR, "SKIP: no program seeded\n");
    exit(0);
}
$price = (float)$program['price'];
check($price > 0, 'test program has a price');

$email = 'balance-test-' . bin2hex(random_bytes(4)) . '@example.com';
$user  = User::create([
    'email'         => $email,
    'password_hash' => password_hash('test', PASSWORD_BCRYPT),
    'full_name'     => 'Balance Test',
    'role'          => 'user',
]);
$userId = (string)$user['id'];

$cleanup = static function () use ($pdo, $userId): void {
    $q = $pdo->quote($userId);
    $pdo->exec("DELETE FROM payments WHERE user_id = {$q}");
    $pdo->exec("DELETE FROM enrollments WHERE user_id = {$q}");
    $pdo->exec("DELETE FROM users WHERE id = {$q}");
};

try {
    $enrollment = Enrollment::create([
        'user_id'    => $userId,
        'program_id' => $program['id'],
        'tier'       => $program['tier'],
        'status'     => 'active', // already active before the payment lands
        'balance'    => $price,
        'start_date' => date('Y-m-d'),
        'end_date'   => date('Y-m-d', strtotime('+30 days')),
    ]);

    $payment = Payment::create([
        'user_id'     => $userId,
        'tier'        => $program['tier'],
        'amount'      => $price,
        'method'      => 'cash',
        'status'      => 'pending',
        'paid_date'   => date('Y-m-d'),
        'description' => 'Balance test payment',
    ]);

    // Confirming the payment must settle an already-active enrollment.
    Payment::markPaid((string)$payment['id'], null);
    Enrollment::syncBalance($userId, (string)$program['tier']);

    $afterPay = Enrollment::find((string)$enrollment['id']);
    check(abs((float)$afterPay['balance']) < 0.01, 'balance is 0 after payment is confirmed');

    // A partial refund puts the refunded part back on the balance.
    Payment::refund((string)$payment['id'], round($price / 4, 2), 'test refund', null);
    Enrollment::syncBalance($userId, (string)$program['tier']);

    $afterRefund = Enrollment::find((string)$enrollment['id']);
    check(
        abs((float)$afterRefund['balance'] - round($price / 4, 2)) < 0.01,
        'partial refund restores that amount as balance'
    );

    echo "OK enrollment balance\n";
} finally {
    $cleanup();
}
