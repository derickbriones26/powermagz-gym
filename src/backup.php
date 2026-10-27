<?php

/**
 * PowerMagz — Backup runner  (spec §2.2)
 *
 * Usage:
 *   php scripts/backup.php               → full backup pipeline
 *   php scripts/backup.php --dump-only   → dump only, no encryption/upload
 *   php scripts/backup.php --verify FILE → verify a backup file
 *   php scripts/backup.php --rotate      → rotate old backups only
 *
 * Cron (Linux/macOS): run daily at 3 AM
 *   0 3 * * *  cd /path/to/powermagz-gym && php scripts/backup.php >> storage/logs/backup.log 2>&1
 *
 * Windows Task Scheduler:
 *   Program:  C:\path\to\php.exe
 *   Argument: C:\path\to\powermagz-gym\scripts\backup.php
 *   Start in: C:\path\to\powermagz-gym
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

/* ================================================================
 |  BOOTSTRAP
 |  ================================================================ */

require_once dirname(__DIR__) . '/src/bootstrap.php';

/* ================================================================
 |  PARSE ARGS
 |  ================================================================ */

$args       = array_slice($argv, 1);
$dumpOnly   = in_array('--dump-only', $args, true);
$rotateOnly = in_array('--rotate',    $args, true);
$verifyFile = null;

$verifyIdx = array_search('--verify', $args, true);
if ($verifyIdx !== false && isset($args[$verifyIdx + 1])) {
    $verifyFile = $args[$verifyIdx + 1];
}

/* ================================================================
 |  LOGGING HELPERS
 |  ================================================================ */

function backup_log(string $msg, string $level = 'info'): void
{
    $ts = date('Y-m-d H:i:s');
    $prefix = match ($level) {
        'error' => '[ERROR]',
        'warn'  => '[WARN ]',
        'ok'    => '[  OK ]',
        default => '[INFO ]',
    };
    echo "[{$ts}] {$prefix} {$msg}\n";
}

function backup_human_bytes(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
    if ($bytes < 1024 * 1024 * 1024) return round($bytes / (1024 * 1024), 2) . ' MB';
    return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
}

/* ================================================================
 |  HEADER
 |  ================================================================ */

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  PowerMagz — Backup Runner\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$config = backup_config();

backup_log('Config dir:       ' . $config['dir']);
backup_log('Retain days:      ' . $config['retain_days']);
backup_log('Encrypt:          ' . ($config['encrypt'] ? 'yes' : 'no'));
backup_log('Remote:           ' . $config['remote']);
if (defined('APP_ENV')) {
    backup_log('Environment:      ' . APP_ENV);
}
echo "\n";

/* ================================================================
 |  VERIFY MODE
 |  ================================================================ */

if ($verifyFile !== null) {
    if (!is_file($verifyFile)) {
        backup_log('File not found: ' . $verifyFile, 'error');
        exit(1);
    }

    backup_log('Verifying: ' . $verifyFile);
    $result = backup_verify_file($verifyFile);

    if ($result['ok']) {
        backup_log('✅ Verified (' . $result['kind'] . ', '
            . backup_human_bytes($result['bytes']) . ') — ' . $result['detail'], 'ok');
        exit(0);
    }

    backup_log('❌ Verification failed — ' . $result['detail'], 'error');
    exit(1);
}

/* ================================================================
 |  ROTATE-ONLY MODE
 |  ================================================================ */

if ($rotateOnly) {
    backup_log('Rotating backups older than ' . $config['retain_days'] . ' days…');
    $rotate = backup_rotate_local();
    backup_log('Deleted: ' . $rotate['deleted'] . ' · Kept: ' . $rotate['kept'], 'ok');
    echo "\n";
    exit(0);
}

/* ================================================================
 |  DUMP-ONLY MODE
 |  ================================================================ */

if ($dumpOnly) {
    backup_log('Running dump-only (no encryption, no upload)…');
    echo "\n";

    $t0 = microtime(true);
    $dump = backup_dump_database();
    $elapsed = round(microtime(true) - $t0, 2);

    if (!$dump['ok']) {
        backup_log('❌ Dump failed: ' . ($dump['error'] ?? 'unknown'), 'error');
        exit(1);
    }

    backup_log('✅ Dump complete in ' . $elapsed . 's', 'ok');
    backup_log('   Path:   ' . $dump['path']);
    backup_log('   Size:   ' . backup_human_bytes($dump['size']));
    backup_log('   Method: ' . $dump['method']);
    echo "\n";

    backup_write_marker();
    backup_log('Marker file updated.', 'ok');
    echo "\n";
    exit(0);
}

/* ================================================================
 |  FULL PIPELINE
 |  ================================================================ */

$t0 = microtime(true);

backup_log('Starting full backup pipeline…');
echo "\n";

/* ---------- 1. Dump ---------- */
backup_log('Step 1/4 · Dumping database…');
$dump = backup_dump_database();

if (!$dump['ok']) {
    backup_log('❌ Dump failed: ' . ($dump['error'] ?? 'unknown'), 'error');
    exit(1);
}
backup_log('✅ Dump: ' . backup_human_bytes($dump['size'])
    . ' via ' . $dump['method'], 'ok');
backup_log('   → ' . $dump['path']);

/* ---------- 2. Encrypt ---------- */
$finalPath = $dump['path'];
if ($config['encrypt']) {
    backup_log('Step 2/4 · Encrypting…');
    $enc = backup_encrypt_file($dump['path']);

    if (!$enc['ok']) {
        backup_log('⚠️  Encryption failed: ' . $enc['error'], 'warn');
        backup_log('   Plain SQL retained (backup is still valid but not encrypted).', 'warn');
    } else {
        $finalPath = $enc['path'];
        backup_log('✅ Encrypted: ' . backup_human_bytes((int)filesize($finalPath)), 'ok');
        backup_log('   → ' . $finalPath);
    }
} else {
    backup_log('Step 2/4 · Encryption disabled — skipping.', 'warn');
}

/* ---------- 3. Upload ---------- */
if ($config['remote'] !== 'none') {
    backup_log('Step 3/4 · Uploading to ' . $config['remote'] . '…');
    $up = backup_upload_remote($finalPath);

    if ($up['ok']) {
        backup_log('✅ Uploaded: ' . $up['url'], 'ok');
    } else {
        backup_log('⚠️  Upload failed: ' . $up['error'], 'warn');
    }
} else {
    backup_log('Step 3/4 · Remote disabled — skipping.', 'warn');
}

/* ---------- 4. Rotate ---------- */
backup_log('Step 4/4 · Rotating local backups…');
$rotate = backup_rotate_local();
backup_log('✅ Deleted ' . $rotate['deleted'] . ' · Kept ' . $rotate['kept'], 'ok');

/* ---------- Marker ---------- */
backup_write_marker();
backup_log('Marker updated: ' . date('c'), 'ok');

/* ---------- Summary ---------- */
$elapsed = round(microtime(true) - $t0, 2);

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  ✅ Backup complete in {$elapsed}s\n";
echo "═══════════════════════════════════════════════════════════\n\n";

exit(0);
