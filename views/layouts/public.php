<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? APP_NAME) ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="public-body">

<nav class="public-nav">
  <div class="container public-nav-inner">
    <a href="<?= url() ?>" class="navbar-brand">
      <?php if (setting('site_logo')): ?>
        <img src="<?= asset('uploads/logos/' . setting('site_logo')) ?>" alt="<?= APP_NAME ?>" style="height:32px;">
      <?php else: ?>
        <span class="brand-text"><?= APP_NAME ?></span>
      <?php endif; ?>
    </a>
    <?php if (isLoggedIn()): ?>
      <a href="<?= url('dashboard') ?>" class="btn btn-sm btn-outline">Mon espace</a>
    <?php else: ?>
      <a href="<?= url('login') ?>" class="btn btn-sm btn-outline">Connexion</a>
    <?php endif; ?>
  </div>
</nav>

<main class="public-main">
  <?php if ($flash = getFlash('success')): ?>
    <div class="container"><div class="alert alert-success mt-4"><span>✓</span> <?= e($flash) ?></div></div>
  <?php endif; ?>
  <?php if ($flash = getFlash('error')): ?>
    <div class="container"><div class="alert alert-error mt-4"><span>✕</span> <?= e($flash) ?></div></div>
  <?php endif; ?>

  <?= $content ?? '' ?>
</main>

<script src="<?= asset('js/app.js') ?>"></script>
<?php if (isset($extraScript)) echo $extraScript; ?>
</body>
</html>
