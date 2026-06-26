<?php ob_start(); ?>
<div class="page-header">
  <div>
    <h1>Nouvel album</h1>
    <p class="page-subtitle">Dans l'événement : <strong><?= e($event['title']) ?></strong></p>
  </div>
</div>

<div class="form-card">
  <form method="POST" action="<?= url('events/' . $event['id'] . '/albums/create') ?>" enctype="multipart/form-data">
    <?= csrfField() ?>

    <div class="form-section">
      <h3 class="form-section-title">Informations</h3>
      <div class="form-group">
        <label class="form-label">Titre de l'album <span class="required">*</span></label>
        <input type="text" name="title" class="form-input" placeholder="Ex: Cérémonie, Soirée dansante…" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Description <span class="optional">(optionnel)</span></label>
        <textarea name="description" class="form-input form-textarea" rows="2"></textarea>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title">Envois des photos</h3>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Date d'ouverture</label>
          <input type="datetime-local" name="upload_start" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">Date de fermeture</label>
          <input type="datetime-local" name="upload_end" class="form-input">
        </div>
      </div>
      <p class="form-hint">Laissez vide pour un accès illimité (vous pourrez fermer manuellement)</p>
    </div>

    <div class="form-section">
      <h3 class="form-section-title">Modération</h3>
      <div class="form-group">
        <label class="form-label">Approbation des photos</label>
        <div class="radio-group">
          <label class="radio-card">
            <input type="radio" name="approval_mode" value="auto" checked>
            <div class="radio-card-content">
              <strong>Automatique</strong>
              <span>Les photos sont visibles immédiatement</span>
            </div>
          </label>
          <label class="radio-card">
            <input type="radio" name="approval_mode" value="manual">
            <div class="radio-card-content">
              <strong>Manuelle</strong>
              <span>Vous validez chaque photo avant publication</span>
            </div>
          </label>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title">QR Code</h3>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Couleur du QR code</label>
          <div class="color-input-group">
            <input type="color" name="qr_color" value="#1a1a2e" class="color-picker">
            <input type="text" value="#1a1a2e" class="form-input color-text" readonly>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Logo personnalisé <span class="optional">(optionnel)</span></label>
          <input type="file" name="qr_logo" class="form-input-file" accept="image/*">
          <p class="form-hint">Sinon le logo du site est utilisé</p>
        </div>
      </div>
    </div>

    <div class="form-actions">
      <a href="<?= url('events/' . $event['id']) ?>" class="btn btn-ghost">Annuler</a>
      <button type="submit" class="btn btn-primary">Créer l'album →</button>
    </div>
  </form>
</div>

<script>
document.querySelector('.color-picker')?.addEventListener('input', function() {
  document.querySelector('.color-text').value = this.value;
});
</script>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
?>
