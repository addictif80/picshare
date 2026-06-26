<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? APP_NAME) ?> — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">

<div class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <a href="<?= url() ?>" class="auth-brand">
        <?php if (setting('site_logo')): ?>
          <img src="<?= asset('uploads/logos/' . setting('site_logo')) ?>" alt="<?= APP_NAME ?>" style="height:40px;">
        <?php else: ?>
          <span class="auth-brand-text"><?= APP_NAME ?></span>
        <?php endif; ?>
      </a>
    </div>

    <?php if ($flash = getFlash('success')): ?>
      <div class="alert alert-success"><span>✓</span> <?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($flash = getFlash('error')): ?>
      <div class="alert alert-error"><span>✕</span> <?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($flash = getFlash('info')): ?>
      <div class="alert alert-info"><span>ℹ</span> <?= e($flash) ?></div>
    <?php endif; ?>

    <?= $content ?? '' ?>
  </div>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
