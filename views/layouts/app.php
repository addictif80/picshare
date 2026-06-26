<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? APP_NAME) ?> — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>

<nav class="navbar">
  <div class="container navbar-inner">
    <a href="<?= url('dashboard') ?>" class="navbar-brand">
      <?php if (setting('site_logo')): ?>
        <img src="<?= asset('uploads/logos/' . setting('site_logo')) ?>" alt="<?= APP_NAME ?>" class="brand-logo">
      <?php else: ?>
        <span class="brand-text"><?= APP_NAME ?></span>
      <?php endif; ?>
    </a>
    <div class="navbar-menu">
      <a href="<?= url('dashboard') ?>" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/dashboard') ? 'active' : '' ?>">Tableau de bord</a>
      <a href="<?= url('events') ?>" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/events') ? 'active' : '' ?>">Événements</a>
      <?php if (isAdmin()): ?>
        <a href="<?= url('admin') ?>" class="nav-link nav-link-admin">Administration</a>
      <?php endif; ?>
      <div class="nav-user">
        <span class="nav-user-name"><?= e(currentUser()['name']) ?></span>
        <a href="<?= url('logout') ?>" class="btn btn-ghost btn-sm">Déconnexion</a>
      </div>
    </div>
  </div>
</nav>

<main class="main-content">
  <div class="container">
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
</main>

<footer class="footer">
  <div class="container footer-inner">
    <span>© <?= date('Y') ?> <?= APP_NAME ?></span>
    <span><?= e(setting('site_tagline', 'Partagez vos souvenirs')) ?></span>
  </div>
</footer>

<script src="<?= asset('js/app.js') ?>"></script>
<?php if (isset($extraScript)) echo $extraScript; ?>
</body>
</html>
