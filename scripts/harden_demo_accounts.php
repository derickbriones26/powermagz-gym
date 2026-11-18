<?php

declare(strict_types=1);

/**
 * One-time production bootstrap hardening for the demo accounts that ship
 * in database/schema.sql.
 *
 * - Creates/ensures the admin account exists.
 * - On the first run (no `demo_accounts_hardened` marker):
 *     * sets the admin password from ADMIN_PASSWORD, or generates a random
 *       one and prints it once to the deploy log;
 *     * disables the seeded member/partner logins;
 *     * deletes the seeded session row.
 * - On later runs it only re-applies ADMIN_PASSWORD when
 *   FORCE_ADMIN_PASSWORD_RESET=1.
 *
 * Run from start.sh (Docker/Render) or manually:
 *   php scripts/harden_demo_accounts.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found.\n");
}

require __DIR__ . '/../src/bootstrap.php';

use App\Config\Database;
use App\Models\User;

$pdo = Database::pdo();

$adminEmail    = trim((string)(getenv('ADMIN_EMAIL') ?: 'powermagzgymadmin@gmail.com'));
$adminPassword = (string)(getenv('ADMIN_PASSWORD') ?: '');
$forceReset    = (string)(getenv('FORCE_ADMIN_PASSWORD_RESET') ?: '') === '1';
$demoLogins    = filter_var(getenv('DEMO_LOGINS_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN);

/* Seeded demo logins, re-enabled on every boot when DEMO_LOGINS_ENABLED is on. */
$demoPasswords = [
    'powermagzgymmember@gmail.com'  => (string)(getenv('DEMO_MEMBER_PASSWORD')  ?: '@2026PowermagzGymMember'),
    'powermagzgympartner@gmail.com' => (string)(getenv('DEMO_PARTNER_PASSWORD') ?: '@2026PowermagzGymPartner'),
];

$admin = $adminEmail !== '' ? User::findByEmail($adminEmail) : null;
$marker = $pdo->prepare(
    "SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'demo_accounts_hardened' LIMIT 1"
);

$alreadyHardened = false;
try {
    $marker->execute();
    $alreadyHardened = $marker->fetchColumn() !== false;
} catch (\Throwable $e) {
    fwrite(STDERR, "[harden] Could not read marker: {$e->getMessage()}\n");
}

$generatedPassword = null;

if (!$admin) {
    $password = $adminPassword !== '' ? $adminPassword : bin2hex(random_bytes(12));
    if ($adminPassword === '') $generatedPassword = $password;

    User::create([
        'email'          => $adminEmail,
        'password_hash'  => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
        'full_name'      => 'PowerMagz Admin',
        'role'           => 'admin',
        'email_verified' => 1,
    ]);

    echo "[harden] Created admin account {$adminEmail}.\n";
} else {
    $update = [
        'role'           => 'admin',
        'email_verified' => 1,
    ];

    if (!$alreadyHardened || $forceReset) {
        if ($adminPassword !== '') {
            $update['password_hash'] = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            echo "[harden] Applied ADMIN_PASSWORD to {$adminEmail}.\n";
        } else {
            $generatedPassword = bin2hex(random_bytes(12));
            $update['password_hash'] = password_hash($generatedPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            echo "[harden] Generated a new password for {$adminEmail}.\n";
        }
    }

    User::update((string)$admin['id'], $update);
}

if (!$alreadyHardened || $demoLogins) {
    if ($demoLogins) {
        /* Apply the known demo passwords and force a fresh login. */
        $stmt = $pdo->prepare(
            "UPDATE `users` SET `password_hash` = ?, `email_verified` = 1 WHERE `email` = ?"
        );
        foreach ($demoPasswords as $demoEmail => $demoPass) {
            $stmt->execute([
                password_hash($demoPass, PASSWORD_BCRYPT, ['cost' => 12]),
                $demoEmail,
            ]);
        }

        $pdo->prepare(
            "DELETE `s` FROM `sessions` `s`
               JOIN `users` `u` ON `u`.`id` = `s`.`user_id`
              WHERE `u`.`email` IN (?, ?)"
        )->execute(array_keys($demoPasswords));

        echo "[harden] Demo member/partner logins enabled (DEMO_LOGINS_ENABLED).\n";
    } else {
        /* Disable the seeded demo logins; an admin can reset them later. */
        $disabledHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare(
            "UPDATE `users`
                SET `password_hash` = ?, `email_verified` = 0
              WHERE `email` IN (?, ?)"
        )->execute([
            $disabledHash,
            'powermagzgymmember@gmail.com',
            'powermagzgympartner@gmail.com',
        ]);

        /* Remove the seeded session and any sessions for the demo accounts. */
        $pdo->prepare(
            "DELETE `s` FROM `sessions` `s`
               JOIN `users` `u` ON `u`.`id` = `s`.`user_id`
              WHERE `u`.`email` IN (?, ?)"
        )->execute(['powermagzgymmember@gmail.com', 'powermagzgympartner@gmail.com']);

        $pdo->prepare("DELETE FROM `sessions` WHERE `token` = ?")->execute([
            '4768b0cc83f751b72564de7345b77aa735edf03619abd81acfb7fd5c31a3a396',
        ]);
    }

    try {
        $adminRow = User::findByEmail($adminEmail);
        $adminId  = $admin['id'] ?? ($adminRow['id'] ?? null);
        $pdo->prepare(
            "INSERT INTO `system_settings`
                (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`)
             VALUES (UUID(), 'demo_accounts_hardened', '1', 'bool',
                     'Seeded demo logins disabled and admin password rotated.', ?)
             ON DUPLICATE KEY UPDATE `setting_value` = '1'"
        )->execute([$adminId]);

        echo "[harden] Demo credential state recorded ("
            . ($demoLogins ? 'enabled' : 'disabled') . ").\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, "[harden] Could not write marker: {$e->getMessage()}\n");
    }
}

if ($generatedPassword !== null) {
    echo "\n";
    echo "============================================================\n";
    echo "  Admin email:    {$adminEmail}\n";
    echo "  Admin password: {$generatedPassword}\n";
    echo "  Store this now, then set ADMIN_PASSWORD in the environment.\n";
    echo "============================================================\n\n";
}

echo "[harden] Done.\n";
