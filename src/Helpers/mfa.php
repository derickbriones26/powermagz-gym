<?php

/**
 * PowerMagz — MFA helpers  (spec §2.1)
 *
 * Pure-PHP TOTP (RFC 6238) — no external library required.
 * Also provides recovery-code generation + verification.
 *
 * Loaded via `require_once` in bootstrap.php.
 *
 * Compatible with:
 *   - Google Authenticator
 *   - Authy
 *   - Microsoft Authenticator
 *   - 1Password / Bitwarden / FreeOTP
 */

/* ================================================================
 |  TOTP CORE  (RFC 6238)
 ================================================================ */

if (!function_exists('mfa_base32_decode')) {

    /**
     * Decode a base32 string (with or without padding, case-insensitive).
     * Returns raw binary or empty string on failure.
     */
    function mfa_base32_decode(string $b32): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper(rtrim($b32, '='));
        $b32 = preg_replace('/[^A-Z2-7]/', '', $b32) ?? '';

        if ($b32 === '') return '';

        $bits    = '';
        $len     = strlen($b32);
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($map, $b32[$i]);
            if ($pos === false) return '';
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $out .= chr(bindec(substr($bits, $i, 8)));
        }
        return $out;
    }

    /**
     * Generate a fresh 32-character base32 secret.
     * 160 bits of entropy — the RFC 4226 recommended size.
     */
    function mfa_generate_secret(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret   = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $alphabet[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Compute the current TOTP code for a secret.
     *
     * @param string   $secret      Base32 secret
     * @param int|null $timeSlice   Unix timestamp (defaults to now)
     * @param int      $digits      6 (default) or 8
     * @param int      $period      Seconds per window (default 30)
     */
    function mfa_totp(
        string $secret,
        ?int $timeSlice = null,
        int $digits = 6,
        int $period = 30
    ): string {
        $key       = mfa_base32_decode($secret);
        if ($key === '') return str_repeat('0', $digits);

        $timeSlice = $timeSlice ?? time();
        $counter   = (int)floor($timeSlice / $period);

        /* 8-byte big-endian counter */
        $binCounter = pack('N*', 0) . pack('N*', $counter);

        /* HMAC-SHA1 */
        $hash   = hash_hmac('sha1', $binCounter, $key, true);
        $offset = ord($hash[19]) & 0x0F;

        $part = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $mod  = 10 ** $digits;
        $code = $part % $mod;

        return str_pad((string)$code, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a submitted code against a secret.
     * Allows ±$window steps of drift (default ±1 = ±30s).
     */
    function mfa_verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6) return false;

        $now = time();
        for ($i = -$window; $i <= $window; $i++) {
            $expected = mfa_totp($secret, $now + ($i * 30));
            if (hash_equals($expected, $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * How many seconds remain in the current 30s window.
     * Used by the UI to show a countdown and warn before expiry.
     */
    function mfa_seconds_remaining(int $period = 30): int
    {
        return $period - (time() % $period);
    }

    /* ================================================================
     |  PROVISIONING  (otpauth:// URI + QR image URL)
     |  ================================================================ */

    /**
     * Build an otpauth:// URI compatible with all major authenticator apps.
     */
    function mfa_provisioning_uri(string $secret, string $accountLabel, string $issuer): string
    {
        $label  = rawurlencode($issuer . ':' . $accountLabel);
        $params = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => 'SHA1',
            'digits'    => 6,
            'period'    => 30,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/' . $label . '?' . $params;
    }

    /**
     * Generate a scannable QR image URL for the provisioning URI.
     * Uses qrserver.com — no API key, HTTPS, high reliability.
     */
    function mfa_qr_url(string $provisioningUri, int $size = 240): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size='
            . $size . 'x' . $size
            . '&data=' . urlencode($provisioningUri)
            . '&margin=8';
    }

    /* ================================================================
     |  RECOVERY CODES
     |  ================================================================ */

    /**
     * Generate a set of one-time recovery codes.
     * Returns ['plain' => [...], 'hashed' => [...]], indexed identically.
     *
     * @param int $count  Number of codes (default 10)
     */
    function mfa_generate_recovery_codes(int $count = 10): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; /* no 0/O/1/I */
        $plain  = [];
        $hashed = [];

        for ($i = 0; $i < $count; $i++) {
            $raw = '';
            for ($j = 0; $j < 10; $j++) {
                $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            /* Format: XXXX-XXXX-XX */
            $formatted = substr($raw, 0, 4) . '-' . substr($raw, 4, 4) . '-' . substr($raw, 8, 2);
            $plain[]  = $formatted;
            $hashed[] = password_hash($formatted, PASSWORD_BCRYPT, ['cost' => 12]);
        }

        return ['plain' => $plain, 'hashed' => $hashed];
    }

    /**
     * Find and consume a recovery code.
     * Returns the index of the matched hash (or -1 if no match),
     * or null if the input is malformed.
     *
     * @param string             $input   User-submitted code
     * @param array<int,string>  $hashes  bcrypt hashes from users.mfa_recovery_codes
     */
    function mfa_consume_recovery_code(string $input, array $hashes): ?int
    {
        $normalized = strtoupper(trim($input));
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized) ?? '';

        if (strlen($normalized) < 8) return null;

        foreach ($hashes as $idx => $hash) {
            if (!is_string($hash) || $hash === '') continue;

            /* Re-format candidate to XXXX-XXXX-XX to match the original */
            $candidate = substr($normalized, 0, 4)
                . '-' . substr($normalized, 4, 4)
                . '-' . substr($normalized, 8, 2);

            if (password_verify($candidate, $hash)) {
                return $idx;
            }
        }

        return -1;
    }

    /* ================================================================
     |  STORAGE HELPERS (JSON encode/decode for users.mfa_recovery_codes)
     |  ================================================================ */

    /**
     * Encode recovery hashes for storage.
     */
    function mfa_encode_recovery_hashes(array $hashes): string
    {
        return json_encode(array_values($hashes), JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decode stored recovery hashes.
     */
    function mfa_decode_recovery_hashes(?string $json): array
    {
        if (!$json) return [];
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /* ================================================================
     |  DISPLAY HELPERS
     |  ================================================================ */

    /**
     * Format a base32 secret as XXXX XXXX XXXX XXXX … for easy manual entry.
     */
    function mfa_format_secret(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }
}
