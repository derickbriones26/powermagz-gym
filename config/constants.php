<?php

define('TIERS',              ['digital', 'standard', 'premium']);
define('ENROLLMENT_STATUSES', ['active', 'expired', 'pending']);
define('PAYMENT_METHODS',    ['gcash', 'cash']);
define('PAYMENT_STATUSES',   ['pending', 'paid']);
define('PAYOUT_STATUSES',    ['pending', 'approved', 'rejected']);
define('B2B_STATUSES',       ['linked', 'pending', 'inactive']);
define('PARTNER_TYPES',      ['coach', 'supplier']);
define('GYM_CAPACITY', 50);

/*
|--------------------------------------------------------------------------
| Gym entrance QR code
|--------------------------------------------------------------------------
|
| 🔐 SECURITY: This string is the secret that members scan at the gym
| entrance to check in. It MUST be:
|
|   • Long enough to be unguessable (≥ 16 characters, 32+ recommended)
|   • Unique to this deployment
|   • Stored in .env, never committed to git
|   • Re-printed on the entrance poster whenever it changes
|
| Generate a fresh value once (paste the output into .env):
|
|   php -r "echo 'GYM_QR_DATA=' . bin2hex(random_bytes(16)) . PHP_EOL;"
|
| The fallback below is deliberately weak so check-ins fail loudly
| (503 Service Unavailable) in production if .env is not configured.
|
*/

$gymQrFromEnv = getenv('GYM_QR_DATA') ?: '';
define(
    'GYM_QR_DATA',
    strlen($gymQrFromEnv) >= 16 ? $gymQrFromEnv : 'INSECURE-DEV-ONLY-CHANGE-ME'
);
