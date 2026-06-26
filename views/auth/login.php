<?php ob_start(); ?>
<div class="auth-body-content">
  <h2>Connexion</h2>
  <p class="auth-sub">Entrez votre email pour recevoir un code de connexion</p>

  <form method="POST" action="<?= url('login') ?>">
    <?= csrfField() ?>
    <div class="form-group">
      <label class="form-label">Adresse email</label>
      <input type="email" name="email" class="form-input" placeholder="vous@exemple.com" required autofocus>
    </div>
    <button type="submit" class="btn btn-primary btn-full">Recevoir mon code →</button>
  </form>

  <div class="auth-divider"><span>Nouveau sur <?= APP_NAME ?> ?</span></div>
  <a href="<?= url('register') ?>" class="btn btn-outline btn-full">Créer un compte</a>
</div>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/auth.php';
?>
