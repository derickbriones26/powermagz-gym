<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('cli only');
require __DIR__ . '/../src/bootstrap.php';

$email = $argv[1] ?? 'admin@powermagz.ph';
$user = \App\Models\User::findByEmail($email);

if (!$user || empty($user['mfa_secret'])) {
    exit("User not found or MFA not configured.\n");
}

echo "\n";
echo "  User:  {$email}\n";
echo "  Code:  \033[1;33m" . mfa_totp($user['mfa_secret']) . "\033[0m\n";
echo "  Time:  " . mfa_seconds_remaining() . "s left\n\n";
