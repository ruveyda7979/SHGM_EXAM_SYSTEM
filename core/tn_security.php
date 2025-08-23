<?php
/**
 * SHGM Exam System – Security Helpers
 * - Güçlü parola hash/verify + legacy (SHA-256) desteği
 * - XSS için güvenli çıktı yardımcıları
 * - Basit input temizleme
 * - CSRF token helper (SessionManager ile uyumlu)
 */

/* -----------------------------
 |  PASSWORD HELPERS
 * -----------------------------*/

/**
 * Güçlü hash üret (varsayılan: bcrypt/argon2 – PHP sürümüne göre PASSWORD_DEFAULT)
 * .env içine istersen PASSWORD_COST=12 gibi bir değer koyabilirsin (bcrypt için)
 */
function tn_password_hash(string $plain): string
{
    $algo = PASSWORD_DEFAULT;
    $options = [];
    if (isset($_ENV['PASSWORD_COST']) && ctype_digit($_ENV['PASSWORD_COST'])) {
        $options['cost'] = (int) $_ENV['PASSWORD_COST'];
    }
    return password_hash($plain, $algo, $options);
}

/**
 * Verilen hash’i doğrula.
 * - Modern hash (bcrypt/argon2) → password_verify
 * - Legacy: 64 hex SHA-256 → PHP tarafında karşılaştır (dump’lardan gelen parolalar)
 */
function tn_password_verify(string $plain, string $hash): bool
{
    // Modern hash?
    if (preg_match('~^\$(?:2y|argon2)~i', $hash)) {
        return password_verify($plain, $hash);
    }
    // Legacy (SHA-256 hex)? (64 heksadesimal karakter)
    if (preg_match('~^[0-9a-f]{64}$~i', $hash)) {
        return hash_equals($hash, hash('sha256', $plain));
    }
    return false;
}

/** Hash eski mi? (Modern değilse veya yeniden hash gerektiriyorsa true) */
function tn_password_is_legacy(string $hash): bool
{
    return (bool) preg_match('~^[0-9a-f]{64}$~i', $hash);
}

/** Modern hash’lerde cost değişmişse rehash gerektirir */
function tn_password_needs_rehash(string $hash): bool
{
    if (tn_password_is_legacy($hash)) return true;
    return password_needs_rehash($hash, PASSWORD_DEFAULT);
}

/* -----------------------------
 |  XSS / OUTPUT HELPERS
 * -----------------------------*/

/** HTML içine güvenli yazım */
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/** HTML attribute içine güvenli yazım (aynı htmlspecialchars, semantik ayırdık) */
if (!function_exists('ea')) {
    function ea($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/** Basit metin temizleme (kontrol karakterlerini kırp) */
function tn_clean_str($val)
{
    if (!is_string($val)) return $val;
    $val = trim($val);
    // Unicode control chars dışarı
    return preg_replace('/[^\P{C}\n\r\t]+/u', '', $val);
}

/* -----------------------------
 |  CSRF HELPERS
 * -----------------------------*/

/** Session tabanlı CSRF token getir/üret */
function csrf_token(): string
{
    if (!class_exists('TN_SessionManager')) return '';
    $s = TN_SessionManager::getInstance();
    $t = $s->get('csrf_token');
    if (!$t) {
        $t = bin2hex(random_bytes(32));
        $s->set('csrf_token', $t);
    }
    return $t;
}

/** Formlara eklemek için hazır <input type="hidden"> */
function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">';
}
