<?php

/**
 * PowerMagz — Encryption helpers  (spec §2.2)
 *
 * AES-256-GCM authenticated encryption for sensitive fields:
 *   - payment tokens
 *   - external API keys stored in system_settings
 *   - any PII the operator chooses to encrypt at rest
 *
 * Design:
 *   - 32-byte key derived from APP_KEY (in .env)
 *   - 12-byte random nonce per encryption (never reused)
 *   - 16-byte GCM auth tag (tamper detection)
 *   - Base64 output prefixed with `v1:` for future key rotation
 *
 * Why GCM: AEAD — you get confidentiality + integrity in one primitive.
 * If a ciphertext is tampered with, decryption fails loudly instead of
 * silently returning garbage.
 *
 * Key management:
 *   The app key lives in .env as APP_KEY=base64:...
 *   Generate one with: php scripts/keygen.php
 *   Never commit .env to git (should already be in .gitignore).
 */

/* ================================================================
 |  VERSION + PREFIX
 |  ================================================================ */

if (!defined('PMGZ_CRYPTO_VERSION')) {
    define('PMGZ_CRYPTO_VERSION', 'v1');
}

if (!defined('PMGZ_CRYPTO_PREFIX')) {
    define('PMGZ_CRYPTO_PREFIX', 'v1:');
}

/* ================================================================
 |  KEY LOADING
 |  ================================================================ */

if (!function_exists('crypto_key')) {

    /**
     * Get the raw 32-byte application key.
     * Reads APP_KEY from the environment (loaded by config.php from .env).
     *
     * Accepts two forms:
     *   APP_KEY=base64:XYZ...  ← preferred
     *   APP_KEY=hex:abcd...    ← legacy / portable
     *
     * Returns null if not configured. Callers should handle this
     * gracefully (skip encryption rather than fatal).
     */
    function crypto_key(): ?string
    {
        static $cached = false;
        if ($cached !== false) return $cached;

        $raw = getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? '');
        if (!is_string($raw) || trim($raw) === '') {
            $cached = null;
            return null;
        }
        $raw = trim($raw);

        /* base64: prefix (Laravel convention) */
        if (str_starts_with($raw, 'base64:')) {
            $decoded = base64_decode(substr($raw, 7), true);
            if ($decoded === false || strlen($decoded) !== 32) {
                error_log('[crypto] APP_KEY base64 form is not 32 bytes');
                $cached = null;
                return null;
            }
            $cached = $decoded;
            return $cached;
        }

        /* hex: prefix */
        if (str_starts_with($raw, 'hex:')) {
            $hex = substr($raw, 4);
            if (!ctype_xdigit($hex) || strlen($hex) !== 64) {
                error_log('[crypto] APP_KEY hex form must be 64 hex chars');
                $cached = null;
                return null;
            }
            $bin = hex2bin($hex);
            if ($bin === false) {
                $cached = null;
                return null;
            }
            $cached = $bin;
            return $cached;
        }

        /* Bare 32-byte string (not recommended) */
        if (strlen($raw) === 32) {
            $cached = $raw;
            return $cached;
        }

        error_log('[crypto] APP_KEY is set but has an unrecognised format');
        $cached = null;
        return null;
    }

    /**
     * Whether an APP_KEY has been configured.
     */
    function crypto_is_configured(): bool
    {
        return crypto_key() !== null;
    }
}

/* ================================================================
 |  ENCRYPT / DECRYPT
 |  ================================================================ */

if (!function_exists('crypto_encrypt')) {

    /**
     * Encrypt a plaintext string using AES-256-GCM.
     *
     * @param string|null $plaintext
     * @return string|null  Base64 payload with `v1:` prefix, or null on failure
     *                      (e.g., APP_KEY not configured).
     */
    function crypto_encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') return $plaintext;

        $key = crypto_key();
        if ($key === null) return null;

        $nonce = random_bytes(12);   /* 96-bit nonce — GCM standard */
        $tag   = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',                       /* no AAD */
            16                        /* 128-bit tag */
        );

        if ($ciphertext === false) {
            error_log('[crypto_encrypt] openssl_encrypt failed');
            return null;
        }

        /* Wire format: v1:base64(nonce || tag || ciphertext) */
        $wire = $nonce . $tag . $ciphertext;

        return PMGZ_CRYPTO_PREFIX . base64_encode($wire);
    }

    /**
     * Decrypt a payload produced by crypto_encrypt().
     *
     * Detects the version prefix. Unknown versions return null.
     * Plaintext that doesn't start with the prefix is returned unchanged
     * (allows gradual migration of existing rows).
     *
     * @param string|null $payload
     * @return string|null
     */
    function crypto_decrypt(?string $payload): ?string
    {
        if ($payload === null || $payload === '') return $payload;

        /* Not encrypted — return as-is (legacy values) */
        if (!str_starts_with($payload, PMGZ_CRYPTO_PREFIX)) {
            return $payload;
        }

        $key = crypto_key();
        if ($key === null) {
            error_log('[crypto_decrypt] ciphertext present but APP_KEY is not configured');
            return null;
        }

        $b64 = substr($payload, strlen(PMGZ_CRYPTO_PREFIX));
        $wire = base64_decode($b64, true);
        if ($wire === false || strlen($wire) < 12 + 16 + 1) {
            error_log('[crypto_decrypt] invalid base64 payload');
            return null;
        }

        /* Split wire format: nonce(12) || tag(16) || ciphertext(rest) */
        $nonce      = substr($wire, 0, 12);
        $tag        = substr($wire, 12, 16);
        $ciphertext = substr($wire, 28);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($plaintext === false) {
            /* Tampering or wrong key. Do NOT log the payload. */
            error_log('[crypto_decrypt] decryption failed (tampering or key mismatch)');
            return null;
        }

        return $plaintext;
    }
}

/* ================================================================
 |  CONVENIENCE HELPERS
 |  ================================================================ */

if (!function_exists('crypto_encrypt_array')) {
    /**
     * JSON-encode + encrypt an array. Useful for storing structured
     * data (e.g., payment method tokens) in a single TEXT column.
     */
    function crypto_encrypt_array(array $data): ?string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) return null;
        return crypto_encrypt($json);
    }
}

if (!function_exists('crypto_decrypt_array')) {
    /**
     * Decrypt + JSON-decode an array payload.
     *
     * @return array|null  Null if decryption fails; [] if payload was empty.
     */
    function crypto_decrypt_array(?string $payload): ?array
    {
        if ($payload === null || $payload === '') return [];

        $json = crypto_decrypt($payload);
        if ($json === null) return null;

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}

/* ================================================================
 |  MASKING (for safe display in admin UI)
 |  ================================================================ */

if (!function_exists('crypto_mask')) {
    /**
     * Produce a safe display string for a sensitive value.
     * Never reveals more than the last 4 characters.
     *
     *   crypto_mask('4111111111111111')  → '•••• •••• •••• 1111'
     *   crypto_mask('token_abc123xyz')   → '••••••••••••3xyz'
     *   crypto_mask(null)                → '—'
     */
    function crypto_mask(?string $plaintext, int $revealLast = 4): string
    {
        if ($plaintext === null || $plaintext === '') return '—';

        $len = strlen($plaintext);
        if ($len <= $revealLast) {
            return str_repeat('•', max(4, $len));
        }

        $tail = substr($plaintext, -$revealLast);
        $masked = str_repeat('•', min($len - $revealLast, 12));

        return $masked . $tail;
    }
}

/* ================================================================
 |  FINGERPRINT (for lookup / dedup without decrypting)
 |  ================================================================ */

if (!function_exists('crypto_fingerprint')) {
    /**
     * Deterministic HMAC-SHA256 of a plaintext value.
     * Lets you check for duplicates or look up rows without storing
     * the plaintext value in a way that's searchable.
     *
     *   $fp = crypto_fingerprint($cardNumber);
     *   SELECT * FROM payments WHERE payment_token_fp = ?
     *
     * Returns lowercase hex (64 chars) or null if key is missing.
     */
    function crypto_fingerprint(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') return null;

        $key = crypto_key();
        if ($key === null) return null;

        return hash_hmac('sha256', $plaintext, $key);
    }
}

/* ================================================================
 |  KEY GENERATION  (for scripts/keygen.php)
 |  ================================================================ */

if (!function_exists('crypto_generate_key')) {
    /**
     * Generate a fresh 32-byte key, formatted as `base64:...`.
     * Used by scripts/keygen.php on first setup.
     */
    function crypto_generate_key(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }
}
