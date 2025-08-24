<?php
/**
 * SHGM Exam System – Security & URL Helpers
 * - Parola hash/verify (modern + legacy SHA-256 desteği)
 * - XSS için güvenli çıktı fonksiyonları
 * - Basit input temizleme
 * - CSRF token helper (TN_SessionManager varsa onu, yoksa $_SESSION)
 * - URL helper’lar: tn_base_path(), tn_url(), tn_redirect()
 */

/* -----------------------------
 |  PASSWORD HELPERS
 * -----------------------------*/

function tn_password_hash(string $plain): string
{
    $algo = PASSWORD_DEFAULT;
    $opts = [];
    if (isset($_ENV['PASSWORD_COST']) && ctype_digit((string)$_ENV['PASSWORD_COST'])) {
        $opts['cost'] = (int) $_ENV['PASSWORD_COST'];
    }
    return password_hash($plain, $algo, $opts);
}

function tn_password_verify(string $plain, string $hash): bool
{
    if (preg_match('~^\$(?:2y|argon2)~i', $hash)) {
        return password_verify($plain, $hash);
    }
    if (preg_match('~^[0-9a-f]{64}$~i', $hash)) { // legacy sha256 hex
        return hash_equals($hash, hash('sha256', $plain));
    }
    return false;
}

function tn_password_is_legacy(string $hash): bool
{
    return (bool) preg_match('~^[0-9a-f]{64}$~i', $hash);
}

function tn_password_needs_rehash(string $hash): bool
{
    if (tn_password_is_legacy($hash)) return true;
    return password_needs_rehash($hash, PASSWORD_DEFAULT);
}

/* -----------------------------
 |  XSS / OUTPUT HELPERS
 * -----------------------------*/

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
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
    return preg_replace('/[^\P{C}\n\r\t]+/u', '', $val);
}

/* -----------------------------
 |  CSRF HELPERS
 * -----------------------------*/

function tn_csrf_token(): string
{
    $current = null;

    if (class_exists('TN_SessionManager')) {
        $s = TN_SessionManager::getInstance();
        $current = $s->get('csrf_token');
        if (!is_string($current) || !preg_match('/^[a-f0-9]{64}$/i', $current)) {
            $current = bin2hex(random_bytes(32));
            $s->set('csrf_token', $current);
        }
    } else {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $current = $_SESSION['csrf_token'] ?? null;
        if (!is_string($current) || !preg_match('/^[a-f0-9]{64}$/i', $current)) {
            $current = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $current;
        }
    }

    return $current;
}

/** $token POST’la gelen değer; doğrulama + (opsiyonel) rotate */
function tn_csrf_verify($token, bool $rotate = true): bool
{
    $stored = null;

    if (class_exists('TN_SessionManager')) {
        $stored = TN_SessionManager::getInstance()->get('csrf_token');
    } else {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $stored = $_SESSION['csrf_token'] ?? null;
    }

    if (!is_string($stored) || !is_string($token)) return false;

    $ok = hash_equals($stored, $token);

    if ($ok && $rotate) {
        $new = bin2hex(random_bytes(32));
        if (class_exists('TN_SessionManager')) {
            TN_SessionManager::getInstance()->set('csrf_token', $new);
        } else {
            $_SESSION['csrf_token'] = $new;
        }
    }

    return $ok;
}

/** Formlara kolayca CSRF eklemek için */
function tn_csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="'.e(tn_csrf_token()).'">';
}

/* Geriye dönük kısa isimler (varsa çakışmayı önlemek için exists kontrolleri) */
if (!function_exists('csrf_token')) { function csrf_token(): string { return tn_csrf_token(); } }
if (!function_exists('csrf_input')) { function csrf_input(): string { return tn_csrf_input(); } }

/* -----------------------------
 |  URL HELPERS
 * -----------------------------*/

/**
 * APP_URL’den base path’i çıkarır.
 * Örn: APP_URL=http://localhost/shgm-exam-system
 *  - tn_base_path() => '/shgm-exam-system/'
 */
function tn_base_path(): string
{
    $appUrl = $_ENV['APP_URL'] ?? '';
    $path   = rtrim((string) parse_url($appUrl, PHP_URL_PATH), '/');
    if ($path === '' || $path === null) return '/';
    return $path . '/';
}

/** Uygulama içi URL üretir: tn_url('login') => '/shgm-exam-system/login' */
function tn_url(string $path = ''): string
{
    return tn_base_path() . ltrim($path, '/');
}

/** Güvenli yönlendirme yardımcısı */
function tn_redirect(string $path, int $status = 302): void
{
    header('Location: ' . tn_url($path), true, $status);
    exit;
}
