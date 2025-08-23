<?php
// views/layouts/default.php
// Basit varsayılan şablon: $title, $content, $flash_messages bekler.
$baseUrl   = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
$pageTitle = isset($title) ? $title : 'SHGM Exam System';
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{background:#f6f7fb}</style>
</head>
<body>
  <main class="container py-4" style="max-width:980px">
    <?php if (!empty($flash_messages)): foreach ($flash_messages as $fm): 
      $map=['success'=>'alert-success','error'=>'alert-danger','warning'=>'alert-warning','info'=>'alert-info'];
      $class=$map[$fm['type']??'info']??'alert-info'; ?>
      <div class="alert <?= $class ?>"><?= htmlspecialchars($fm['message']??'',ENT_QUOTES,'UTF-8') ?></div>
    <?php endforeach; endif; ?>

    <?= $content ?? '' ?>
  </main>
</body>
</html>
