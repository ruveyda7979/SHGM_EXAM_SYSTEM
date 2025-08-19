<?php
/**
 * SHGM Exam System - Sistem Sabitleri
 * @file config/tn_constants.php
 *
 * Notlar:
 * - Bu dosya doğrudan çalıştırıldığında hiçbir şey yapmaz.
 * - index.php'den veya tn_app_config.php içinden include edilmelidir.
 */

/* -------------------------------------------------
 |  KULLANICI TİPLERİ VE YETKİLERİ
 * ------------------------------------------------*/
define('TN_ROLE_SUPER_ADMIN', 'super_admin');
define('TN_ROLE_ADMIN',        'admin');
define('TN_ROLE_INSTRUCTOR',   'instructor');
define('TN_ROLE_STUDENT',      'student');
define('TN_ROLE_OBSERVER',     'observer');

define('TN_USER_STATUS_ACTIVE',    1);
define('TN_USER_STATUS_INACTIVE',  0);
define('TN_USER_STATUS_SUSPENDED', -1);
define('TN_USER_STATUS_PENDING',   2);

define('TN_SESSION_ACTIVE',     'active');
define('TN_SESSION_EXPIRED',    'expired');
define('TN_SESSION_TERMINATED', 'terminated');

/* -------------------------------------------------
 |  SINAV SİSTEMİ
 * ------------------------------------------------*/
define('HA_EXAM_STATUS_DRAFT',      'draft');
define('HA_EXAM_STATUS_PUBLISHED',  'published');
define('HA_EXAM_STATUS_ACTIVE',     'active');
define('HA_EXAM_STATUS_COMPLETED',  'completed');
define('HA_EXAM_STATUS_CANCELLED',  'cancelled');
define('HA_EXAM_STATUS_ARCHIVED',   'archived');

define('HA_QUESTION_TYPE_MULTIPLE_CHOICE', 'multiple_choice');
define('HA_QUESTION_TYPE_TRUE_FALSE',      'true_false');
define('HA_QUESTION_TYPE_FILL_BLANK',      'fill_blank');
define('HA_QUESTION_TYPE_ESSAY',           'essay');
define('HA_QUESTION_TYPE_MATCHING',        'matching');
define('HA_QUESTION_TYPE_ORDERING',        'ordering');

define('HA_DIFFICULTY_EASY',   'easy');
define('HA_DIFFICULTY_MEDIUM', 'medium');
define('HA_DIFFICULTY_HARD',   'hard');
define('HA_DIFFICULTY_EXPERT', 'expert');

define('HA_SESSION_STATUS_NOT_STARTED', 'not_started');
define('HA_SESSION_STATUS_IN_PROGRESS', 'in_progress');
define('HA_SESSION_STATUS_COMPLETED',   'completed');
define('HA_SESSION_STATUS_TIMEOUT',     'timeout');
define('HA_SESSION_STATUS_TERMINATED',  'terminated');
define('HA_SESSION_STATUS_SUSPENDED',   'suspended');

define('HA_ANSWER_STATUS_NOT_ANSWERED', 'not_answered');
define('HA_ANSWER_STATUS_ANSWERED',     'answered');
define('HA_ANSWER_STATUS_MARKED',       'marked');
define('HA_ANSWER_STATUS_SKIPPED',      'skipped');

/* -------------------------------------------------
 |  KAYIT & MEDYA
 * ------------------------------------------------*/
define('HA_RECORDING_TYPE_AUDIO',  'audio');
define('HA_RECORDING_TYPE_VIDEO',  'video');
define('HA_RECORDING_TYPE_SCREEN', 'screen');
define('HA_RECORDING_TYPE_WEBCAM', 'webcam');

define('HA_RECORDING_STATUS_RECORDING',  'recording');
define('HA_RECORDING_STATUS_COMPLETED',  'completed');
define('HA_RECORDING_STATUS_FAILED',     'failed');
define('HA_RECORDING_STATUS_PROCESSING', 'processing');

define('HA_FILE_TYPE_IMAGE',    'image');
define('HA_FILE_TYPE_AUDIO',    'audio');
define('HA_FILE_TYPE_VIDEO',    'video');
define('HA_FILE_TYPE_DOCUMENT', 'document');
define('HA_FILE_TYPE_ARCHIVE',  'archive');

define('HA_IMAGE_FORMATS',   ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('HA_AUDIO_FORMATS',   ['mp3', 'wav', 'ogg', 'webm']);
define('HA_VIDEO_FORMATS',   ['mp4', 'webm', 'avi', 'mov']);
define('HA_DOCUMENT_FORMATS',['pdf', 'doc', 'docx', 'txt']);

/* -------------------------------------------------
 |  RAPOR & ANALİTİK
 * ------------------------------------------------*/
define('RP_REPORT_TYPE_STUDENT',     'student');
define('RP_REPORT_TYPE_EXAM',        'exam');
define('RP_REPORT_TYPE_PERFORMANCE', 'performance');
define('RP_REPORT_TYPE_COMPARISON',  'comparison');
define('RP_REPORT_TYPE_STATISTICS',  'statistics');
define('RP_REPORT_TYPE_HISTORICAL',  'historical');

define('RP_FORMAT_HTML',  'html');
define('RP_FORMAT_PDF',   'pdf');
define('RP_FORMAT_EXCEL', 'excel');
define('RP_FORMAT_CSV',   'csv');
define('RP_FORMAT_JSON',  'json');

define('RP_GRADING_TYPE_AI',       'ai');
define('RP_GRADING_TYPE_MANUAL',   'manual');
define('RP_GRADING_TYPE_HYBRID',   'hybrid');
define('RP_GRADING_TYPE_AUTOMATIC','automatic');

/* -------------------------------------------------
 |  API & ENTEGRASYON
 * ------------------------------------------------*/
define('TN_HTTP_OK',                  200);
define('TN_HTTP_CREATED',             201);
define('TN_HTTP_BAD_REQUEST',         400);
define('TN_HTTP_UNAUTHORIZED',        401);
define('TN_HTTP_FORBIDDEN',           403);
define('TN_HTTP_NOT_FOUND',           404);
define('TN_HTTP_METHOD_NOT_ALLOWED',  405);
define('TN_HTTP_INTERNAL_ERROR',      500);
define('TN_HTTP_SERVICE_UNAVAILABLE', 503);

define('TN_API_RESPONSE_SUCCESS', 'success');
define('TN_API_RESPONSE_ERROR',   'error');
define('TN_API_RESPONSE_WARNING', 'warning');
define('TN_API_RESPONSE_INFO',    'info');

define('TN_SHGM_API_VERSION',    $_ENV['SHGM_API_VERSION'] ?? 'v1');
define('TN_SHGM_API_TIMEOUT',   (int)($_ENV['SHGM_API_TIMEOUT'] ?? 30));
define('TN_SHGM_API_RETRY_COUNT', 3);
define('TN_SHGM_API_RETRY_DELAY', 2);

define('TN_API_RATE_LIMIT_REQUESTS', 100);
define('TN_API_RATE_LIMIT_WINDOW',   3600);
define('TN_API_RATE_LIMIT_BAN_TIME', 1800);

/* -------------------------------------------------
 |  GÜVENLİK
 * ------------------------------------------------*/
define('TN_SECURITY_LEVEL_LOW',      1);
define('TN_SECURITY_LEVEL_MEDIUM',   2);
define('TN_SECURITY_LEVEL_HIGH',     3);
define('TN_SECURITY_LEVEL_MAXIMUM',  4);

define('TN_ENCRYPT_AES256', 'aes-256-gcm');
define('TN_ENCRYPT_AES128', 'aes-128-gcm');
define('TN_HASH_SHA256',    'sha256');
define('TN_HASH_SHA512',    'sha512');

define('TN_SECURITY_CHECK_IP',          'ip_check');
define('TN_SECURITY_CHECK_USER_AGENT',  'user_agent_check');
define('TN_SECURITY_CHECK_SESSION',     'session_check');
define('TN_SECURITY_CHECK_TOKEN',       'token_check');

/* -------------------------------------------------
 |  LOG & HATA
 * ------------------------------------------------*/
define('TN_LOG_EMERGENCY', 'emergency'); // 0
define('TN_LOG_ALERT',     'alert');     // 1
define('TN_LOG_CRITICAL',  'critical');  // 2
define('TN_LOG_ERROR',     'error');     // 3
define('TN_LOG_WARNING',   'warning');   // 4
define('TN_LOG_NOTICE',    'notice');    // 5
define('TN_LOG_INFO',      'info');      // 6
define('TN_LOG_DEBUG',     'debug');     // 7

define('TN_LOG_TYPE_SYSTEM',   'system');
define('TN_LOG_TYPE_USER',     'user');
define('TN_LOG_TYPE_EXAM',     'exam');
define('TN_LOG_TYPE_API',      'api');
define('TN_LOG_TYPE_SECURITY', 'security');
define('TN_LOG_TYPE_ERROR',    'error');

define('TN_ERROR_TYPE_SYSTEM',         'system_error');
define('TN_ERROR_TYPE_DATABASE',       'database_error');
define('TN_ERROR_TYPE_API',            'api_error');
define('TN_ERROR_TYPE_VALIDATION',     'validation_error');
define('TN_ERROR_TYPE_PERMISSION',     'permission_error');
define('TN_ERROR_TYPE_AUTHENTICATION', 'auth_error');

/* -------------------------------------------------
 |  AI SABİTLERİ
 * ------------------------------------------------*/
define('TN_AI_MODEL_GPT4',        'gpt-4');
define('TN_AI_MODEL_GPT35_TURBO', 'gpt-3.5-turbo');
define('TN_AI_MODEL_CLAUDE',      'claude-3');

define('TN_AI_TASK_GRADING',   'grading');
define('TN_AI_TASK_FEEDBACK',  'feedback');
define('TN_AI_TASK_ANALYSIS',  'analysis');
define('TN_AI_TASK_SUGGESTION','suggestion');

define('TN_AI_CONFIDENCE_LOW',    0.60);
define('TN_AI_CONFIDENCE_MEDIUM', 0.75);
define('TN_AI_CONFIDENCE_HIGH',   0.90);

/* -------------------------------------------------
 |  PERFORMANS
 * ------------------------------------------------*/
define('TN_CACHE_SHORT',     300);
define('TN_CACHE_MEDIUM',   1800);
define('TN_CACHE_LONG',     3600);
define('TN_CACHE_VERY_LONG',86400);

define('TN_MEMORY_LIMIT_LOW',      '128M');
define('TN_MEMORY_LIMIT_MEDIUM',   '256M');
define('TN_MEMORY_LIMIT_HIGH',     '512M');
define('TN_MEMORY_LIMIT_MAXIMUM',  '1G');

define('TN_BATCH_SIZE_SMALL',  50);
define('TN_BATCH_SIZE_MEDIUM', 100);
define('TN_BATCH_SIZE_LARGE',  500);

/* -------------------------------------------------
 |  TARİH & ZAMAN
 * ------------------------------------------------*/
define('TN_DATE_FORMAT',            'Y-m-d');
define('TN_TIME_FORMAT',            'H:i:s');
define('TN_DATETIME_FORMAT',        'Y-m-d H:i:s');
define('TN_DISPLAY_DATE_FORMAT',    'd.m.Y');
define('TN_DISPLAY_DATETIME_FORMAT','d.m.Y H:i');

define('TN_TIMEZONE_ISTANBUL', 'Europe/Istanbul');
define('TN_TIMEZONE_UTC',      'UTC');

/* -------------------------------------------------
 |  VALIDATION
 * ------------------------------------------------*/
define('TN_TC_LENGTH',  11);
define('TN_TC_PATTERN', '/^[1-9][0-9]{10}$/');

define('TN_EMAIL_MAX_LENGTH', 255);
define('TN_EMAIL_PATTERN', '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/');

define('TN_PASSWORD_MIN_LENGTH',      8);
define('TN_PASSWORD_MAX_LENGTH',      128);
define('TN_PASSWORD_REQUIRE_UPPER',   true);
define('TN_PASSWORD_REQUIRE_LOWER',   true);
define('TN_PASSWORD_REQUIRE_NUMBER',  true);
define('TN_PASSWORD_REQUIRE_SPECIAL', true);

define('TN_PHONE_PATTERN', '/^(\+90|0)?[5][0-9]{9}$/');

/* -------------------------------------------------
 |  MESAJ & BİLDİRİM
 * ------------------------------------------------*/
define('TN_NOTIFICATION_SUCCESS', 'success');
define('TN_NOTIFICATION_ERROR',   'error');
define('TN_NOTIFICATION_WARNING', 'warning');
define('TN_NOTIFICATION_INFO',    'info');

define('TN_EMAIL_TEMPLATE_WELCOME',        'welcome');
define('TN_EMAIL_TEMPLATE_EXAM_REMINDER',  'exam_reminder');
define('TN_EMAIL_TEMPLATE_RESULTS',        'exam_results');
define('TN_EMAIL_TEMPLATE_PASSWORD_RESET', 'password_reset');

/* -------------------------------------------------
 |  HELPER FONKSİYONLAR
 * ------------------------------------------------*/
if (!function_exists('tn_get_constants')) {
    function tn_get_constants($prefix = 'TN_') {
        $all = get_defined_constants(true);
        $user = $all['user'] ?? [];
        $filtered = [];
        foreach ($user as $name => $value) {
            if (strpos($name, $prefix) === 0) {
                $filtered[$name] = $value;
            }
        }
        return $filtered;
    }
}

if (!function_exists('tn_exam_statuses')) {
    function tn_exam_statuses() {
        return [
            HA_EXAM_STATUS_DRAFT     => 'Taslak',
            HA_EXAM_STATUS_PUBLISHED => 'Yayınlanmış',
            HA_EXAM_STATUS_ACTIVE    => 'Aktif',
            HA_EXAM_STATUS_COMPLETED => 'Tamamlanmış',
            HA_EXAM_STATUS_CANCELLED => 'İptal Edilmiş',
            HA_EXAM_STATUS_ARCHIVED  => 'Arşivlenmiş',
        ];
    }
}

if (!function_exists('tn_question_types')) {
    function tn_question_types() {
        return [
            HA_QUESTION_TYPE_MULTIPLE_CHOICE => 'Çoktan Seçmeli',
            HA_QUESTION_TYPE_TRUE_FALSE      => 'Doğru/Yanlış',
            HA_QUESTION_TYPE_FILL_BLANK      => 'Boşluk Doldurma',
            HA_QUESTION_TYPE_ESSAY           => 'Açık Uçlu',
            HA_QUESTION_TYPE_MATCHING        => 'Eşleştirme',
            HA_QUESTION_TYPE_ORDERING        => 'Sıralama',
        ];
    }
}

if (!function_exists('tn_difficulty_levels')) {
    function tn_difficulty_levels() {
        return [
            HA_DIFFICULTY_EASY   => 'Kolay',
            HA_DIFFICULTY_MEDIUM => 'Orta',
            HA_DIFFICULTY_HARD   => 'Zor',
            HA_DIFFICULTY_EXPERT => 'Uzman',
        ];
    }
}

if (!function_exists('tn_user_roles')) {
    function tn_user_roles() {
        return [
            TN_ROLE_SUPER_ADMIN => 'Süper Yönetici',
            TN_ROLE_ADMIN       => 'Yönetici',
            TN_ROLE_INSTRUCTOR  => 'Eğitmen',
            TN_ROLE_STUDENT     => 'Öğrenci',
            TN_ROLE_OBSERVER    => 'Gözlemci',
        ];
    }
}

if (!function_exists('tn_system_info')) {
    function tn_system_info() {
        return [
            'name'              => defined('APP_NAME')     ? APP_NAME     : 'SHGM Exam System',
            'version'           => defined('APP_VERSION')  ? APP_VERSION  : '1.0.0',
            'environment'       => defined('APP_ENV')      ? APP_ENV      : 'development',
            'php_version'       => PHP_VERSION,
            'server_time'       => date(TN_DATETIME_FORMAT),
            'timezone'          => defined('APP_TIMEZONE') ? APP_TIMEZONE : TN_TIMEZONE_ISTANBUL,
            'memory_limit'      => ini_get('memory_limit'),
            'max_execution_time'=> ini_get('max_execution_time'),
            'upload_max_filesize'=> ini_get('upload_max_filesize'),
            'post_max_size'     => ini_get('post_max_size'),
        ];
    }
}

/* -------------------------------------------------
 |  DEBUG LOG
 * ------------------------------------------------*/
if (defined('APP_DEBUG') && APP_DEBUG) {
    $constantCount = count(tn_get_constants());
    error_log('[TN_CONSTANTS] ' . $constantCount . ' constants loaded at ' . date('Y-m-d H:i:s'));
}
