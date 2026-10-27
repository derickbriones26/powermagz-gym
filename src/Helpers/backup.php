<?php

/**
 * PowerMagz — Backup helpers  (spec §2.2)
 *
 * Provides:
 *   - backup_dump_database()   → SQL dump file (mysqldump, PHP fallback)
 *   - backup_encrypt_file()    → AES-256-GCM encrypt the dump
 *   - backup_upload_remote()   → geo-redundant upload hook
 *   - backup_rotate_local()    → delete old backups
 *   - backup_write_marker()    → record last-backup timestamp
 *   - backup_verify_file()     → integrity check (decrypt + parse header)
 *   - backup_full()            → end-to-end orchestration
 *
 * Zero external dependencies. Works on Linux/macOS/Windows.
 * Fails loudly to error_log; returns structured results.
 */

/* ================================================================
 |  CONFIG
 |  ================================================================ */

if (!function_exists('backup_config')) {

    /**
     * Backup configuration — reads from environment with sane defaults.
     *
     * .env keys:
     *   BACKUP_DIR            default: BASE_PATH . '/storage/backups'
     *   BACKUP_RETAIN_DAYS    default: 14
     *   BACKUP_ENCRYPT        default: true (uses crypto.php)
     *   BACKUP_REMOTE         default: 'none' — 's3'|'gcs'|'b2'|'custom'
     *   BACKUP_MYSQLDUMP_BIN  default: 'mysqldump' (PATH lookup)
     */
    function backup_config(): array
    {
        $defaultDir = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../..')
            . '/storage/backups';

        return [
            'dir'             => getenv('BACKUP_DIR')            ?: $defaultDir,
            'retain_days'     => (int)(getenv('BACKUP_RETAIN_DAYS') ?: 14),
            'encrypt'         => filter_var(getenv('BACKUP_ENCRYPT') ?: 'true', FILTER_VALIDATE_BOOLEAN),
            'remote'          => strtolower(getenv('BACKUP_REMOTE') ?: 'none'),
            'mysqldump_bin'   => getenv('BACKUP_MYSQLDUMP_BIN')  ?: 'mysqldump',
            'marker_file'     => (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../..')
                . '/storage/last_backup.txt',
        ];
    }
}

/* ================================================================
 |  PATH HELPERS
 |  ================================================================ */

if (!function_exists('backup_ensure_dir')) {

    function backup_ensure_dir(string $dir): bool
    {
        if (is_dir($dir)) return true;
        return @mkdir($dir, 0755, true);
    }

    function backup_filename_prefix(): string
    {
        return 'powermagz-gym-' . date('Y-m-d_His');
    }
}

/* ================================================================
 |  DATABASE DUMP
 |  ================================================================ */

if (!function_exists('backup_dump_database')) {

    /**
     * Dump the database to a plain SQL file.
     *
     * Tries mysqldump first (fast, faithful). Falls back to a
     * pure-PHP dump if mysqldump isn't available.
     *
     * @return array{ok:bool, path:?string, size:int, method:string, error:?string}
     */
    function backup_dump_database(?string $targetDir = null): array
    {
        $config = backup_config();
        $targetDir = $targetDir ?? $config['dir'];

        if (!backup_ensure_dir($targetDir)) {
            return [
                'ok' => false,
                'path' => null,
                'size' => 0,
                'method' => 'none',
                'error' => 'Could not create backup directory: ' . $targetDir
            ];
        }

        $filename = backup_filename_prefix() . '.sql';
        $path     = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        /* ---------- Try mysqldump first ---------- */
        $result = backup_dump_via_mysqldump($path, $config['mysqldump_bin']);
        if ($result['ok']) {
            $result['size'] = is_file($path) ? (int)filesize($path) : 0;
            return $result;
        }

        error_log('[backup] mysqldump failed: ' . ($result['error'] ?? 'unknown') . ' — falling back to PHP dump');

        /* ---------- Fallback: pure-PHP dump ---------- */
        return backup_dump_via_php($path);
    }

    /**
     * Dump using the `mysqldump` binary.
     */
    function backup_dump_via_mysqldump(string $path, string $mysqldump): array
    {
        /* Skip if the binary isn't callable */
        if (!backup_binary_exists($mysqldump)) {
            return [
                'ok' => false,
                'path' => null,
                'size' => 0,
                'method' => 'mysqldump',
                'error' => 'mysqldump binary not found'
            ];
        }

        /* Read connection info from config constants */
        $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
        $port = defined('DB_PORT') ? DB_PORT : '3306';
        $name = defined('DB_NAME') ? DB_NAME : '';
        $user = defined('DB_USER') ? DB_USER : '';
        $pass = defined('DB_PASS') ? DB_PASS : '';

        if ($name === '' || $user === '') {
            return [
                'ok' => false,
                'path' => null,
                'size' => 0,
                'method' => 'mysqldump',
                'error' => 'Database credentials are not configured'
            ];
        }

        /* Build the command. Use --defaults-extra-file trick to keep
           the password off the process list. */
        $tmpCreds = tempnam(sys_get_temp_dir(), 'pmgz-my-');
        if ($tmpCreds === false) {
            return [
                'ok' => false,
                'path' => null,
                'size' => 0,
                'method' => 'mysqldump',
                'error' => 'Could not create temp credentials file'
            ];
        }

        $creds = "[client]\n"
            . "host=" . $host . "\n"
            . "port=" . $port . "\n"
            . "user=" . $user . "\n"
            . "password=" . $pass . "\n";

        if (@file_put_contents($tmpCreds, $creds) === false) {
            @unlink($tmpCreds);
            return [
                'ok' => false,
                'path' => null,
                'size' => 0,
                'method' => 'mysqldump',
                'error' => 'Could not write temp credentials file'
            ];
        }
        @chmod($tmpCreds, 0600);

        /* Build args */
        $args = [
            '--defaults-extra-file=' . $tmpCreds,
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--routines',
            '--triggers',
            '--events',
            '--set-gtid-purged=OFF',
            $name,
        ];

        $cmd = escapeshellcmd($mysqldump);
        foreach ($args as $a) {
            $cmd .= ' ' . escapeshellarg($a);
        }

        /* Redirect output to the target file (with error output separately) */
        $stderrFile = $path . '.err';

        if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
            $cmd .= ' > ' . escapeshellarg($path) . ' 2> ' . escapeshellarg($stderrFile);
        } else {
            $cmd .= ' > ' . escapeshellarg($path) . ' 2> ' . escapeshellarg($stderrFile);
        }

        $exitCode = 0;
        $output   = [];
        @exec($cmd, $output, $exitCode);

        @unlink($tmpCreds);

        if ($exitCode !== 0 || !is_file($path) || filesize($path) < 100) {
            $err = is_file($stderrFile)
                ? trim((string)file_get_contents($stderrFile))
                : 'unknown error';
            @unlink($stderrFile);
            @unlink($path);
            return [
                'ok' => false,
                'path' => null,
                'size' => 0,
                'method' => 'mysqldump',
                'error' => 'mysqldump exit ' . $exitCode . ': ' . $err
            ];
        }

        @unlink($stderrFile);

        return [
            'ok' => true,
            'path' => $path,
            'size' => (int)filesize($path),
            'method' => 'mysqldump',
            'error' => null
        ];
    }

    /**
     * Pure-PHP fallback: read schema + data table by table.
     */
    function backup_dump_via_php(string $path): array
    {
        try {
            $pdo = \App\Config\Database::pdo();
            $name = defined('DB_NAME') ? DB_NAME : '';

            $fh = fopen($path, 'wb');
            if (!$fh) {
                return [
                    'ok' => false,
                    'path' => null,
                    'size' => 0,
                    'method' => 'php',
                    'error' => 'Could not open output file'
                ];
            }

            fwrite($fh, "-- PowerMagz Gym — PHP fallback dump\n");
            fwrite($fh, "-- Database: {$name}\n");
            fwrite($fh, "-- Generated: " . date('c') . "\n\n");
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($fh, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            /* List tables */
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                /* CREATE TABLE */
                $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch();
                if (!$create) continue;

                $createSql = $create['Create Table'] ?? $create[1] ?? '';
                if ($createSql === '') continue;

                fwrite($fh, "\n-- --------------------------------------------------------\n");
                fwrite($fh, "-- Table structure for `{$table}`\n");
                fwrite($fh, "-- --------------------------------------------------------\n\n");
                fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($fh, $createSql . ";\n\n");

                /* Data (streamed in chunks to bound memory) */
                $count = (int)$pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '`')->fetchColumn();
                if ($count === 0) continue;

                fwrite($fh, "-- Data for `{$table}`\n\n");

                $offset = 0;
                $chunk = 500;
                while ($offset < $count) {
                    $rows = $pdo->query(
                        'SELECT * FROM `' . str_replace('`', '``', $table) . '` LIMIT ' . $chunk . ' OFFSET ' . $offset
                    )->fetchAll(PDO::FETCH_ASSOC);

                    if (!$rows) break;

                    foreach ($rows as $row) {
                        $cols = array_map(fn($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($row));
                        $vals = array_map(function ($v) use ($pdo) {
                            if ($v === null) return 'NULL';
                            if (is_int($v) || is_float($v)) return (string)$v;
                            return $pdo->quote((string)$v);
                        }, array_values($row));

                        fwrite($fh, "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES ("
                            . implode(', ', $vals) . ");\n");
                    }

                    $offset += $chunk;
                }

                fwrite($fh, "\n");
            }

            fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
            fclose($fh);

            return [
                'ok' => true,
                'path' => $path,
                'size' => (int)filesize($path),
                'method' => 'php',
                'error' => null
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'path' => null,
                'size' => 0,
                'method' => 'php',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Is a binary callable from the current shell?
     */
    function backup_binary_exists(string $binary): bool
    {
        if (str_contains($binary, '/') || str_contains($binary, '\\')) {
            return is_file($binary) && is_executable($binary);
        }

        $check = stripos(PHP_OS_FAMILY, 'Windows') !== false ? 'where' : 'command -v';
        $out = [];
        $code = 0;
        @exec($check . ' ' . escapeshellarg($binary) . ' 2>/dev/null', $out, $code);

        return $code === 0;
    }
}

/* ================================================================
 |  ENCRYPTION
 |  ================================================================ */

if (!function_exists('backup_encrypt_file')) {

    /**
     * AES-256-GCM encrypt the dump file in place.
     * Produces `<original>.enc` and removes the plaintext.
     *
     * @return array{ok:bool, path:?string, error:?string}
     */
    function backup_encrypt_file(string $sqlPath): array
    {
        if (!function_exists('crypto_encrypt')) {
            return [
                'ok' => false,
                'path' => $sqlPath,
                'error' => 'crypto.php not loaded'
            ];
        }
        if (!crypto_is_configured()) {
            return [
                'ok' => false,
                'path' => $sqlPath,
                'error' => 'APP_KEY not configured'
            ];
        }
        if (!is_file($sqlPath)) {
            return ['ok' => false, 'path' => $sqlPath, 'error' => 'Source file missing'];
        }

        $data = @file_get_contents($sqlPath);
        if ($data === false) {
            return ['ok' => false, 'path' => $sqlPath, 'error' => 'Could not read source file'];
        }

        /* Add a plaintext header inside the encrypted payload so
           the recipient can verify authenticity at restore time. */
        $header = "PMGZ-BACKUP-V1\n"
            . "Generated: " . date('c') . "\n"
            . "Database:  " . (defined('DB_NAME') ? DB_NAME : 'unknown') . "\n"
            . "Bytes:     " . strlen($data) . "\n"
            . "SHA256:    " . hash('sha256', $data) . "\n"
            . "---\n";

        $encrypted = crypto_encrypt($header . $data);
        if ($encrypted === null) {
            return ['ok' => false, 'path' => $sqlPath, 'error' => 'Encryption failed'];
        }

        $encPath = $sqlPath . '.enc';

        if (@file_put_contents($encPath, $encrypted) === false) {
            return ['ok' => false, 'path' => $sqlPath, 'error' => 'Could not write encrypted file'];
        }

        @chmod($encPath, 0600);
        @unlink($sqlPath);

        return ['ok' => true, 'path' => $encPath, 'error' => null];
    }
}

/* ================================================================
 |  REMOTE UPLOAD (geo-redundant storage)
 |  ================================================================ */

if (!function_exists('backup_upload_remote')) {

    /**
     * Upload a file to the configured remote provider.
     *
     * Supported providers (via env):
     *   BACKUP_REMOTE=s3  + AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY / AWS_DEFAULT_REGION / S3_BUCKET
     *   BACKUP_REMOTE=gcs + GCS_BUCKET (requires `gsutil`)
     *   BACKUP_REMOTE=b2  + B2_BUCKET (requires `b2` CLI)
     *   BACKUP_REMOTE=custom + custom hook fired
     *
     * Returns ['ok'=>bool, 'provider'=>string, 'url'=>?string, 'error'=>?string]
     */
    function backup_upload_remote(string $filePath): array
    {
        $config = backup_config();
        $provider = $config['remote'];

        if ($provider === 'none' || $provider === '') {
            return [
                'ok' => false,
                'provider' => 'none',
                'url' => null,
                'error' => 'Remote backup is not configured (BACKUP_REMOTE=none)'
            ];
        }

        if (!is_file($filePath)) {
            return [
                'ok' => false,
                'provider' => $provider,
                'url' => null,
                'error' => 'File not found: ' . $filePath
            ];
        }

        /* Custom provider → fire a hook if defined */
        if ($provider === 'custom') {
            if (function_exists('backup_remote_custom_upload')) {
                return backup_remote_custom_upload($filePath);
            }
            return [
                'ok' => false,
                'provider' => 'custom',
                'url' => null,
                'error' => 'Define backup_remote_custom_upload($path) in a custom helper'
            ];
        }

        /* S3 via aws CLI */
        if ($provider === 's3') {
            return backup_upload_via_aws_cli($filePath);
        }

        /* GCS via gsutil */
        if ($provider === 'gcs') {
            return backup_upload_via_gsutil($filePath);
        }

        /* Backblaze B2 via b2 CLI */
        if ($provider === 'b2') {
            return backup_upload_via_b2_cli($filePath);
        }

        return [
            'ok' => false,
            'provider' => $provider,
            'url' => null,
            'error' => 'Unknown remote provider: ' . $provider
        ];
    }

    function backup_upload_via_aws_cli(string $filePath): array
    {
        $bucket = getenv('S3_BUCKET') ?: '';
        if ($bucket === '') {
            return [
                'ok' => false,
                'provider' => 's3',
                'url' => null,
                'error' => 'S3_BUCKET env var not set'
            ];
        }

        if (!backup_binary_exists('aws')) {
            return [
                'ok' => false,
                'provider' => 's3',
                'url' => null,
                'error' => 'aws CLI not installed'
            ];
        }

        $key = 'powermagz-gym/' . basename($filePath);
        $dest = 's3://' . $bucket . '/' . $key;

        $cmd = 'aws s3 cp ' . escapeshellarg($filePath) . ' ' . escapeshellarg($dest)
            . ' --storage-class STANDARD_IA 2>&1';

        $out = [];
        $code = 0;
        @exec($cmd, $out, $code);

        if ($code !== 0) {
            return [
                'ok' => false,
                'provider' => 's3',
                'url' => null,
                'error' => 'aws s3 cp failed: ' . implode(' ', array_slice($out, -3))
            ];
        }

        return [
            'ok' => true,
            'provider' => 's3',
            'url' => 's3://' . $bucket . '/' . $key,
            'error' => null
        ];
    }

    function backup_upload_via_gsutil(string $filePath): array
    {
        $bucket = getenv('GCS_BUCKET') ?: '';
        if ($bucket === '') {
            return [
                'ok' => false,
                'provider' => 'gcs',
                'url' => null,
                'error' => 'GCS_BUCKET env var not set'
            ];
        }
        if (!backup_binary_exists('gsutil')) {
            return [
                'ok' => false,
                'provider' => 'gcs',
                'url' => null,
                'error' => 'gsutil not installed'
            ];
        }

        $key = 'powermagz-gym/' . basename($filePath);
        $dest = 'gs://' . $bucket . '/' . $key;

        $out = [];
        $code = 0;
        @exec('gsutil cp ' . escapeshellarg($filePath) . ' ' . escapeshellarg($dest) . ' 2>&1', $out, $code);

        if ($code !== 0) {
            return [
                'ok' => false,
                'provider' => 'gcs',
                'url' => null,
                'error' => 'gsutil cp failed: ' . implode(' ', array_slice($out, -3))
            ];
        }

        return [
            'ok' => true,
            'provider' => 'gcs',
            'url' => 'gs://' . $bucket . '/' . $key,
            'error' => null
        ];
    }

    function backup_upload_via_b2_cli(string $filePath): array
    {
        $bucket = getenv('B2_BUCKET') ?: '';
        if ($bucket === '') {
            return [
                'ok' => false,
                'provider' => 'b2',
                'url' => null,
                'error' => 'B2_BUCKET env var not set'
            ];
        }
        if (!backup_binary_exists('b2')) {
            return [
                'ok' => false,
                'provider' => 'b2',
                'url' => null,
                'error' => 'b2 CLI not installed'
            ];
        }

        $remoteName = 'powermagz-gym/' . basename($filePath);

        $out = [];
        $code = 0;
        @exec(
            'b2 upload-file ' . escapeshellarg($bucket) . ' '
                . escapeshellarg($filePath) . ' ' . escapeshellarg($remoteName) . ' 2>&1',
            $out,
            $code
        );

        if ($code !== 0) {
            return [
                'ok' => false,
                'provider' => 'b2',
                'url' => null,
                'error' => 'b2 upload-file failed: ' . implode(' ', array_slice($out, -3))
            ];
        }

        return [
            'ok' => true,
            'provider' => 'b2',
            'url' => 'b2://' . $bucket . '/' . $remoteName,
            'error' => null
        ];
    }
}

/* ================================================================
 |  LOCAL ROTATION
 |  ================================================================ */

if (!function_exists('backup_rotate_local')) {

    /**
     * Delete local backups older than the retention window.
     *
     * @return array{deleted:int, kept:int}
     */
    function backup_rotate_local(?int $retainDays = null): array
    {
        $config = backup_config();
        $retainDays = $retainDays ?? (int)$config['retain_days'];
        $dir = $config['dir'];

        if (!is_dir($dir)) return ['deleted' => 0, 'kept' => 0];

        $cutoff = time() - ($retainDays * 86400);
        $deleted = 0;
        $kept = 0;

        foreach (glob($dir . '/powermagz-gym-*.{sql,sql.enc}', GLOB_BRACE) ?: [] as $file) {
            $mtime = @filemtime($file);
            if ($mtime === false) continue;

            if ($mtime < $cutoff) {
                if (@unlink($file)) $deleted++;
            } else {
                $kept++;
            }
        }

        return ['deleted' => $deleted, 'kept' => $kept];
    }
}

/* ================================================================
 |  MARKER FILE
 |  ================================================================ */

if (!function_exists('backup_write_marker')) {

    /**
     * Write the last-backup timestamp to the marker file the settings
     * tab reads. Safe to call repeatedly.
     */
    function backup_write_marker(?string $isoTimestamp = null): bool
    {
        $config = backup_config();
        $path = $config['marker_file'];

        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $ts = $isoTimestamp ?? date('c');
        return @file_put_contents($path, $ts) !== false;
    }
}

/* ================================================================
 |  VERIFICATION
 |  ================================================================ */

if (!function_exists('backup_verify_file')) {

    /**
     * Verify a backup file is intact.
     * For .enc files: decrypt + check header + SHA-256.
     *
     * @return array{ok:bool, kind:string, detail:?string, bytes:int}
     */
    function backup_verify_file(string $filePath): array
    {
        if (!is_file($filePath)) {
            return ['ok' => false, 'kind' => 'missing', 'detail' => 'File not found', 'bytes' => 0];
        }

        $bytes = (int)filesize($filePath);

        /* Plain SQL backup — just check it has the marker comment */
        if (str_ends_with($filePath, '.sql')) {
            $head = @file_get_contents($filePath, false, null, 0, 256) ?: '';
            $ok = str_contains($head, '--') || str_contains($head, 'SET ');
            return ['ok' => $ok, 'kind' => 'sql', 'detail' => $ok ? 'Header OK' : 'Missing header', 'bytes' => $bytes];
        }

        /* Encrypted backup */
        if (str_ends_with($filePath, '.enc')) {
            if (!function_exists('crypto_decrypt')) {
                return ['ok' => false, 'kind' => 'enc', 'detail' => 'crypto.php missing', 'bytes' => $bytes];
            }

            $payload = @file_get_contents($filePath);
            if ($payload === false) {
                return ['ok' => false, 'kind' => 'enc', 'detail' => 'Read failed', 'bytes' => $bytes];
            }

            $plain = crypto_decrypt($payload);
            if ($plain === null) {
                return [
                    'ok' => false,
                    'kind' => 'enc',
                    'detail' => 'Decryption failed (wrong key or tampering)',
                    'bytes' => $bytes
                ];
            }

            /* Header check */
            if (!str_starts_with($plain, 'PMGZ-BACKUP-V1')) {
                return [
                    'ok' => false,
                    'kind' => 'enc',
                    'detail' => 'Missing PMGZ-BACKUP-V1 header',
                    'bytes' => $bytes
                ];
            }

            /* SHA-256 check */
            $split = strpos($plain, "---\n");
            if ($split === false) {
                return ['ok' => false, 'kind' => 'enc', 'detail' => 'Malformed header', 'bytes' => $bytes];
            }

            $header = substr($plain, 0, $split);
            $body   = substr($plain, $split + 4);

            $expectedHash = '';
            foreach (explode("\n", $header) as $line) {
                if (str_starts_with($line, 'SHA256:')) {
                    $expectedHash = trim(substr($line, 7));
                    break;
                }
            }

            if ($expectedHash !== '' && !hash_equals($expectedHash, hash('sha256', $body))) {
                return ['ok' => false, 'kind' => 'enc', 'detail' => 'SHA-256 mismatch', 'bytes' => $bytes];
            }

            return ['ok' => true, 'kind' => 'enc', 'detail' => 'Verified', 'bytes' => $bytes];
        }

        return ['ok' => false, 'kind' => 'unknown', 'detail' => 'Unrecognised extension', 'bytes' => $bytes];
    }
}

/* ================================================================
 |  ORCHESTRATION
 |  ================================================================ */

if (!function_exists('backup_full')) {

    /**
     * Run the full backup pipeline:
     *   1. Dump the database
     *   2. Encrypt the dump (if configured)
     *   3. Upload to remote (if configured)
     *   4. Rotate local backups
     *   5. Write the marker file
     *
     * @return array{
     *   ok: bool,
     *   dump: ?string,
     *   encrypted: bool,
     *   remote_url: ?string,
     *   bytes: int,
     *   rotate: array{deleted:int, kept:int},
     *   errors: array<int,string>
     * }
     */
    function backup_full(): array
    {
        $config = backup_config();
        $errors = [];

        $result = [
            'ok'         => false,
            'dump'       => null,
            'encrypted'  => false,
            'remote_url' => null,
            'bytes'      => 0,
            'rotate'     => ['deleted' => 0, 'kept' => 0],
            'errors'     => $errors,
        ];

        /* 1 — Dump */
        $dump = backup_dump_database();
        if (!$dump['ok']) {
            $errors[] = 'Dump: ' . ($dump['error'] ?? 'unknown');
            $result['errors'] = $errors;
            return $result;
        }
        $result['dump']  = $dump['path'];
        $result['bytes'] = $dump['size'];

        /* 2 — Encrypt */
        $finalPath = $dump['path'];
        if ($config['encrypt']) {
            $enc = backup_encrypt_file($dump['path']);
            if (!$enc['ok']) {
                $errors[] = 'Encrypt: ' . $enc['error'];
            } else {
                $result['encrypted'] = true;
                $finalPath = $enc['path'];
                $result['bytes'] = is_file($finalPath) ? (int)filesize($finalPath) : 0;
            }
        }

        /* 3 — Upload remote */
        if ($config['remote'] !== 'none' && $finalPath) {
            $up = backup_upload_remote($finalPath);
            if ($up['ok']) {
                $result['remote_url'] = $up['url'];
            } else {
                $errors[] = 'Remote: ' . $up['error'];
            }
        }

        /* 4 — Rotate local */
        $result['rotate'] = backup_rotate_local();

        /* 5 — Marker */
        backup_write_marker();

        $result['ok']     = count($errors) === 0;
        $result['errors'] = $errors;

        return $result;
    }
}
