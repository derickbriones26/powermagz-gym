<?php

/**
 * PowerMagz — Application key generator  (spec §2.2)
 *
 * Usage:
 *   php scripts/keygen.php           → generate key if not present
 *   php scripts/keygen.php --force   → overwrite existing key (WARNING: breaks encryption)
 *
 * Writes APP_KEY to .env at the project root.
 *
 * Run this once on first deploy. The key is used by crypto.php for
 * AES-256-GCM encryption of sensitive fields.
 *
 * NEVER commit the generated .env to git. NEVER share the key.
 */

declare(strict_types=1);

/* Only run from the command line */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

$projectRoot = dirname(__DIR__);
$envFile     = $projectRoot . '/.env';

$force = in_array('--force', $argv, true);

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  PowerMagz — Application Key Generator\n";
echo "═══════════════════════════════════════════════════════════\n\n";

/* ================================================================
 |  CHECK EXISTING KEY
 |  ================================================================ */

$existingKey = null;

if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (preg_match('/^APP_KEY=(.+)$/', trim($line), $m)) {
            $existingKey = trim($m[1]);
            break;
        }
    }
}

if ($existingKey !== null && $existingKey !== '' && !$force) {
    echo "✅  APP_KEY already exists in .env\n\n";
    echo "    Value: " . substr($existingKey, 0, 20) . "…" . substr($existingKey, -8) . "\n\n";
    echo "    Nothing to do.\n";
    echo "    Run with --force if you really need to regenerate.\n\n";
    echo "    ⚠️  Regenerating will BREAK decryption of anything already\n";
    echo "       encrypted with the current key.\n\n";
    exit(0);
}

if ($existingKey !== null && $force) {
    echo "⚠️  --force specified. Existing key will be REPLACED.\n";
    echo "    Any rows encrypted with the old key will become unreadable.\n\n";

    echo "    Type 'yes' to confirm: ";
    $handle = fopen('php://stdin', 'r');
    $answer = trim((string)fgets($handle));
    fclose($handle);

    if (strtolower($answer) !== 'yes') {
        echo "\n    Aborted. Nothing changed.\n\n";
        exit(1);
    }
    echo "\n";
}

/* ================================================================
 |  GENERATE KEY
 |  ================================================================ */

try {
    $rawKey = random_bytes(32);
} catch (\Throwable $e) {
    fwrite(STDERR, "❌  Failed to generate secure random bytes: " . $e->getMessage() . "\n");
    exit(1);
}

$appKey = 'base64:' . base64_encode($rawKey);

echo "🔐  Generated a fresh 256-bit key\n";
echo "    Format: base64:...\n\n";

/* ================================================================
 |  WRITE TO .env
 |  ================================================================ */

/* Create .env from example if it doesn't exist */
if (!is_file($envFile)) {
    $example = $projectRoot . '/.env.example';
    if (is_file($example)) {
        copy($example, $envFile);
        echo "📄  Created .env from .env.example\n";
    } else {
        file_put_contents($envFile, "# PowerMagz Gym — Environment Config\n");
        echo "📄  Created a fresh .env\n";
    }
}

/* Read existing content */
$lines = is_file($envFile) ? file($envFile, FILE_IGNORE_NEW_LINES) : [];

/* Remove any existing APP_KEY line */
$lines = array_values(array_filter($lines, function ($line) {
    return !preg_match('/^\s*APP_KEY\s*=/', $line);
}));

/* Find a good insertion point — after APP_ENV if it exists, else at the top */
$insertAt = 0;
foreach ($lines as $i => $line) {
    if (preg_match('/^\s*APP_ENV\s*=/', $line)) {
        $insertAt = $i + 1;
        break;
    }
}

array_splice($lines, $insertAt, 0, ['APP_KEY=' . $appKey]);

/* Write back */
$content = implode("\n", $lines) . "\n";
if (file_put_contents($envFile, $content) === false) {
    fwrite(STDERR, "❌  Could not write to .env\n");
    exit(1);
}

echo "✅  Wrote APP_KEY to .env\n\n";
echo "    Location: {$envFile}\n";
echo "    Value:    " . substr($appKey, 0, 20) . "…" . substr($appKey, -8) . "\n\n";

/* ================================================================
 |  VALIDATE (defensive — verify it round-trips)
 |  ================================================================ */

$test = 'verify-' . bin2hex(random_bytes(4));
$nonce = random_bytes(12);
$tag = '';
$ct = openssl_encrypt($test, 'aes-256-gcm', $rawKey, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);

if ($ct === false) {
    fwrite(STDERR, "❌  Sanity check failed — openssl_encrypt returned false\n");
    exit(1);
}

$pt = openssl_decrypt($ct, 'aes-256-gcm', $rawKey, OPENSSL_RAW_DATA, $nonce, $tag);
if ($pt !== $test) {
    fwrite(STDERR, "❌  Sanity check failed — round-trip did not match\n");
    exit(1);
}

echo "🧪  Sanity check: ✅ encryption + decryption round-trip works\n\n";

/* ================================================================
 |  CHECK .gitignore
 |  ================================================================ */

$gitignore = $projectRoot . '/.gitignore';
if (is_file($gitignore)) {
    $contents = file_get_contents($gitignore) ?: '';
    if (stripos($contents, '.env') === false) {
        echo "⚠️  .env is NOT in .gitignore — add it before committing:\n";
        echo "       echo \".env\" >> .gitignore\n\n";
    } else {
        echo "🛡️  .env is git-ignored (good)\n\n";
    }
} else {
    echo "⚠️  No .gitignore found. Create one and add:\n";
    echo "       .env\n";
    echo "       vendor/\n";
    echo "       storage/logs/*.log\n\n";
}

/* ================================================================
 |  DONE
 |  ================================================================ */

echo "═══════════════════════════════════════════════════════════\n";
echo "  Setup complete.\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "  Next steps:\n";
echo "    1. Ensure .env is in .gitignore\n";
echo "    2. Verify with:  php scripts/test_crypto.php\n";
echo "    3. On production, back up .env before deploying\n\n";
