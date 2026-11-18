<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Models\Attendance;
use App\Models\Enrollment;

function check(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$pdo = \App\Config\Database::pdo();
$admin = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();
$other = $pdo->query("SELECT id FROM users WHERE role != 'admin' LIMIT 1")->fetchColumn();
check((bool)$admin && (bool)$other, 'test needs one admin and one non-admin user');

// Temporary user (enrollments have a foreign key to users).
$tempUser = \App\Core\Model::uuid();
$pdo->prepare("INSERT INTO users (id, email, password_hash, role) VALUES (?, ?, 'x', 'user')")
    ->execute([$tempUser, 'expiry-test-' . $tempUser . '@example.test']);

$now = date('Y-m-d H:i:s');
$past = date('Y-m-d', strtotime('-10 days'));
$insideGrace = date('Y-m-d', strtotime('-1 day'));
$future = date('Y-m-d', strtotime('+10 days'));

$created = [];
$make = function (string $endDate) use (&$created, $tempUser): string {
    $row = Enrollment::create([
        'user_id'      => $tempUser,
        'user_name'    => 'Expiry Test',
        'tier'         => 'digital',
        'status'       => 'active',
        'start_date'   => date('Y-m-d', strtotime('-30 days')),
        'end_date'     => $endDate,
        'grace_until'  => null,
        'balance'      => 0,
    ]);
    $created[] = $row['id'];
    return $row['id'];
};

$pastId = $make($past);
$graceId = $make($insideGrace);
$futureId = $make($future);

$active = Enrollment::activeForUser($tempUser);
check($active !== null, 'an active row still returns someone');
check($active['id'] !== $pastId, 'past-due enrollment is not active');
check(in_array($active['id'], [$graceId, $futureId], true), 'grace/future enrollment is active');

// The helper member request path uses this to decide access.
check(Enrollment::activeForUser('no-such-user') === null, 'unknown user has no active enrollment');

// Attendance ownership
$record = Attendance::create([
    'user_id'   => $admin,
    'user_name' => 'Admin Test',
    'date'      => date('Y-m-d'),
    'time_in'   => $now,
    'hours'     => 0,
    'source'    => 'manual',
]);
check(Attendance::belongsTo($record['id'], (string)$admin) === true, 'owner can close own record');
check(Attendance::belongsTo($record['id'], (string)$other) === false, 'other user cannot close the record');
check(Attendance::belongsTo('missing-id', (string)$admin) === false, 'missing record is not owned');

Attendance::delete($record['id']);
foreach ($created as $id) Enrollment::delete($id);
$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$tempUser]);

echo "OK enrollment lifecycle + attendance ownership\n";
