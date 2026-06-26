<?php ob_start(); ?>
<div class="page-header">
  <div>
    <h1>Nouvel événement</h1>
    <p class="page-subtitle">Un événement peut contenir plusieurs albums (cérémonie, soirée…)</p>
  </div>
</div>

<div class="form-card">
  <form method="POST" action="<?= url('events/create') ?>">
    <?= csrfField() ?>
    <div class="form-group">
      <label class="form-label">Nom de l'événement <span class="required">*</span></label>
      <input type="text" name="title" class="form-input" placeholder="Ex: Mariage Marie & Paul" required autofocus>
    </div>
    <div class="form-group">
      <label class="form-label">Description <span class="optional">(optionnel)</span></label>
      <textarea name="description" class="form-input form-textarea" placeholder="Quelques mots sur l'événement…" rows="3"></textarea>
    </div>
    <div class="form-actions">
      <a href="<?= url('events') ?>" class="btn btn-ghost">Annuler</a>
      <button type="submit" class="btn btn-primary">Créer l'événement →</button>
    </div>
  </form>
</div>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
?>
