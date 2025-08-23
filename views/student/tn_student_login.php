<?php
/**
 * Öğrenci Giriş Sayfası (View)
 * - Sadece HTML/CSS/JS; doğrulama controller’da yapılır.
 * - CSRF token TN_Controller->generateCSRFToken() ile gelir.
 */
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Öğrenci Girişi', ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#f7f8fa}
    .wrap{max-width:420px;margin:9vh auto;padding:28px;background:#fff;border:1px solid #e9ecef;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.04)}
    h1{font-size:22px;margin:0 0 18px}
    .field{margin:12px 0}
    .field input{width:100%;padding:11px 12px;border:1px solid #cfd8ea;border-radius:10px;font-size:14px}
    .btn{display:block;width:100%;padding:11px 14px;border-radius:10px;background:#2d5bff;color:#fff;border:0;font-weight:600;cursor:pointer}
    .btn:disabled{opacity:.6;cursor:not-allowed}
    .muted{color:#6c757d;font-size:12px;margin-top:10px}
    .error{background:#ffe3e6;color:#b00020;border:1px solid #ffc2c9;padding:10px 12px;border-radius:10px;margin-bottom:12px}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Öğrenci Girişi</h1>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="/auth/student-login" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <div class="field">
        <input type="email" name="email" placeholder="E-posta" required>
      </div>
      <div class="field">
        <input type="password" name="password" placeholder="Şifre" required>
      </div>
      <button class="btn" type="submit">Giriş Yap</button>
      <p class="muted">Sadece yetkili öğrenciler içindir.</p>
    </form>
  </div>
</body>
</html>
