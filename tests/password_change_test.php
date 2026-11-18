<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Models\User;

function check(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$pdo = \App\Config\Database::pdo();
$id  = \App\Core\Model::uuid();
$pdo->prepare('INSERT INTO users (id, email, password_hash, role) VALUES (?, ?, ?, ?)')
    ->execute([$id, 'password-test-' . $id . '@example.test', password_hash('OldPass123', PASSWORD_BCRYPT), 'user']);

$hash = static fn() => $pdo->query("SELECT password_hash FROM users WHERE id = '{$id}'")->fetchColumn();

check(User::changePassword($id, 'wrong-password', 'BrandNew123') !== null, 'wrong current password is rejected');
check(password_verify('OldPass123', (string)$hash()), 'wrong current password leaves the hash untouched');

check(User::changePassword($id, 'OldPass123', 'short') !== null, 'short new password is rejected');
check(User::changePassword($id, 'OldPass123', 'OldPass123') !== null, 'same password is rejected');

check(User::changePassword($id, 'OldPass123', 'BrandNew123') === null, 'correct current password is accepted');
check(password_verify('BrandNew123', (string)$hash()), 'new password verifies');
check(!password_verify('OldPass123', (string)$hash()), 'old password no longer works');

check(User::changePassword('missing-id', 'OldPass123', 'BrandNew123') !== null, 'unknown user is rejected');

$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);

echo "OK password change\n";
