<?php
/**
 * Admin Dashboard (View)
 * - $summary:      ['total_users'=>..,'total_students'=>..,'total_exams'=>..,'total_questions'=>..,'active_exams'=>..]
 * - $recent_exams: [{id, code, title, status, created_display}, ...]
 * - $recent_students: [{id, display_no, status, created_display}, ...]
 * Not: Tüm çıktı HTML’inde htmlspecialchars kullanıyoruz (XSS koruması).
 */
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Yönetim Paneli', ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    body{font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,Helvetica,Arial;background:#f5f6fa;margin:0;padding:24px;}
    .grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px;margin-bottom:24px;}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;box-shadow:0 1px 2px rgba(0,0,0,.04);}
    .card h3{margin:0 0 8px;font-size:14px;color:#6b7280;font-weight:600}
    .card .val{font-size:26px;font-weight:700;color:#111827}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;font-size:14px}
    th{color:#6b7280;font-weight:600}
    .muted{color:#6b7280;font-size:12px}
    .badge{display:inline-block;padding:2px 8px;border-radius:9999px;background:#eef2ff;color:#3730a3;font-size:12px}
    @media (max-width:1100px){.grid{grid-template-columns:repeat(2,1fr)} .row{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <h1 style="margin:0 0 16px">Yönetim Paneli</h1>

  <div class="grid">
    <div class="card"><h3>Kullanıcılar</h3><div class="val"><?= (int)($summary['total_users'] ?? 0) ?></div></div>
    <div class="card"><h3>Öğrenciler</h3><div class="val"><?= (int)($summary['total_students'] ?? 0) ?></div></div>
    <div class="card"><h3>Sınavlar</h3><div class="val"><?= (int)($summary['total_exams'] ?? 0) ?></div></div>
    <div class="card"><h3>Sorular</h3><div class="val"><?= (int)($summary['total_questions'] ?? 0) ?></div></div>
    <div class="card"><h3>Aktif Sınav</h3><div class="val"><?= (int)($summary['active_exams'] ?? 0) ?></div></div>
  </div>

  <div class="row">
    <div class="card">
      <h3>Son Sınavlar</h3>
      <table>
        <thead><tr><th>Kod</th><th>Başlık</th><th>Durum</th><th class="muted">Tarih</th></tr></thead>
        <tbody>
        <?php foreach (($recent_exams ?? []) as $e): ?>
          <tr>
            <td><?= htmlspecialchars($e['code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($e['title'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge"><?= htmlspecialchars($e['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td class="muted"><?= htmlspecialchars($e['created_display'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; if (empty($recent_exams)) echo '<tr><td colspan="4" class="muted">Kayıt yok.</td></tr>'; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h3>Son Öğrenciler</h3>
      <table>
        <thead><tr><th>No</th><th>Durum</th><th class="muted">Tarih</th></tr></thead>
        <tbody>
        <?php foreach (($recent_students ?? []) as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['display_no'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge"><?= htmlspecialchars($s['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td class="muted"><?= htmlspecialchars($s['created_display'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; if (empty($recent_students)) echo '<tr><td colspan="3" class="muted">Kayıt yok.</td></tr>'; ?>
        </tbody>
      </table>
    </div>
  </div>

  <p class="muted" style="margin-top:16px">SHGM Pilot Exam System</p>
</body>
</html>
