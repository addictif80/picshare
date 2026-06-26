<?php ob_start(); ?>
<div class="auth-body-content">
  <h2>Créer un compte</h2>
  <p class="auth-sub">Commencez à partager vos événements photo</p>

  <form method="POST" action="<?= url('register') ?>">
    <?= csrfField() ?>
    <div class="form-group">
      <label class="form-label">Nom complet</label>
      <input type="text" name="name" class="form-input" placeholder="Marie Dupont" required autofocus minlength="2">
    </div>
    <div class="form-group">
      <label class="form-label">Adresse email</label>
      <input type="email" name="email" class="form-input" placeholder="vous@exemple.com" required>
    </div>
    <button type="submit" class="btn btn-primary btn-full">Créer mon compte</button>
  </form>

  <div class="auth-divider"><span>Déjà un compte ?</span></div>
  <a href="<?= url('login') ?>" class="btn btn-outline btn-full">Se connecter</a>
</div>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/auth.php';
?>
