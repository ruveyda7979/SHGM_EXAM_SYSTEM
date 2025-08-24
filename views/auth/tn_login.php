<?php
/**
 * SHGM Exam System – Admin Giriş (View)
 * Controller değişkenleri:
 *   $title, $error, $email
 */
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Yönetici Girişi', ENT_QUOTES, 'UTF-8'); ?></title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#f6f7fb;margin:0}
    .wrap{max-width:420px;margin:6vh auto;padding:28px;border-radius:16px;background:#fff;box-shadow:0 6px 22px rgba(0,0,0,.07)}
    h1{font-size:20px;margin:0 0 18px}
    .field{margin:12px 0}
    label{display:block;font-size:13px;color:#444;margin-bottom:6px}
    input[type="email"],input[type="password"]{width:100%;padding:10px 12px;border:1px solid #d9dbe3;border-radius:10px;font-size:14px;outline:none}
    input:focus{border-color:#7b8cff;box-shadow:0 0 0 3px rgba(123,140,255,.15)}
    .btn{display:inline-block;width:100%;border:0;border-radius:10px;padding:11px 14px;background:#2d5bff;color:#fff;font-weight:600;font-size:14px;cursor:pointer}
    .muted{color:#778;font-size:12px}
    .error{background:#ffe6e6;color:#a00;border:1px solid #ffc6c6;padding:10px 12px;border-radius:10px;margin:8px 0}
    .footer{margin-top:14px;text-align:center}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Yönetici Girişi</h1>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="<?= tn_url('login') ?>" autocomplete="off" novalidate>
      <?= tn_csrf_input(); ?>

      <div class="field">
        <label for="email">E-posta</label>
        <input id="email" name="email" type="email" autocomplete="username"
               value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>

      <div class="field">
        <label for="password">Şifre</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required minlength="4">
      </div>

      <div class="field">
        <button class="btn" type="submit">Giriş Yap</button>
      </div>

      <p class="muted">Sadece yetkili personel içindir.</p>
    </form>

    <div class="footer muted">
      <small><?= htmlspecialchars($app_name ?? 'SHGM Exam System', ENT_QUOTES, 'UTF-8'); ?></small>
    </div>
  </div>
</body>
</html>
