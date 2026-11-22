<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Models\B2BClient;

function check(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$pdo = \App\Config\Database::pdo();

// Isolated member + partner so existing rows cannot interfere.
$member  = \App\Core\Model::uuid();
$partner = \App\Core\Model::uuid();
$email   = 'coach-invite-' . $member . '@example.test';

$pdo->prepare("INSERT INTO users (id, email, password_hash, role) VALUES (?, ?, 'x', 'user')")
    ->execute([$member, $email]);
$pdo->prepare("INSERT INTO users (id, email, password_hash, role) VALUES (?, ?, 'x', 'partner')")
    ->execute([$partner, 'coach-invite-' . $partner . '@example.test']);

$client = B2BClient::create([
    'partner_user_id'   => $partner,
    'client_name'       => 'Invite Test Member',
    'client_email'      => $email,
    'membership_status' => 'pending',
]);

$pending = B2BClient::where(['client_email' => $email, 'membership_status' => 'pending']);
check(count($pending) === 1, 'new client starts pending for the member email');

$linked = B2BClient::linkToUser((string)$client['id'], $member);
check($linked['membership_status'] === 'linked', 'accept marks the client linked');
check($linked['client_user_id'] === $member, 'accept stores the member id');
check($linked['linked_at'] !== null, 'accept stamps linked_at');

check(count(B2BClient::where(['client_email' => $email, 'membership_status' => 'pending'])) === 0, 'accepted request leaves the pending list');

$second = B2BClient::create([
    'partner_user_id'   => $partner,
    'client_name'       => 'Decline Test Member',
    'client_email'      => $email,
    'membership_status' => 'pending',
]);
$declined = B2BClient::updateClient((string)$second['id'], ['membership_status' => 'inactive']);
check($declined['membership_status'] === 'inactive', 'decline archives the request');
check(count(B2BClient::where(['client_email' => $email, 'membership_status' => 'pending'])) === 0, 'declined request leaves the pending list');

foreach ([$client['id'], $second['id']] as $id) B2BClient::delete($id);
$pdo->prepare('DELETE FROM users WHERE id IN (?, ?)')->execute([$member, $partner]);

echo "OK coach invite accept/decline\n";
