<?php ob_start(); ?>
<div class="page-header">
  <div>
    <h1>Paramètres système</h1>
    <p class="page-subtitle">Configuration globale de <?= APP_NAME ?></p>
  </div>
</div>

<form method="POST" action="<?= url('admin/settings') ?>" enctype="multipart/form-data">
  <?= csrfField() ?>

  <div class="settings-sections">

    <!-- Site -->
    <div class="settings-section">
      <h3>🌐 Site</h3>
      <div class="settings-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tagline</label>
            <input type="text" name="site_tagline" class="form-input" value="<?= e($settings['site_tagline'] ?? 'Partagez vos souvenirs') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Logo du site</label>
            <input type="file" name="site_logo" class="form-input-file" accept="image/*">
            <?php if (!empty($settings['site_logo'])): ?>
              <p class="form-hint">Logo actuel : <img src="<?= asset('uploads/logos/' . $settings['site_logo']) ?>" style="height:20px;vertical-align:middle;"></p>
            <?php endif; ?>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Taille max fichier (Mo)</label>
            <input type="number" name="max_file_size_mb" class="form-input" value="<?= e($settings['max_file_size_mb'] ?? 20) ?>" min="1" max="500">
          </div>
          <div class="form-group">
            <label class="form-label">Albums max par événement</label>
            <input type="number" name="max_albums_per_event" class="form-input" value="<?= e($settings['max_albums_per_event'] ?? 10) ?>" min="1" max="50">
          </div>
        </div>
        <div class="form-group">
          <label class="toggle-label">
            <input type="checkbox" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? 0) ? 'checked' : '' ?>>
            Mode maintenance (site inaccessible aux utilisateurs)
          </label>
        </div>
      </div>
    </div>

    <!-- Tarification -->
    <div class="settings-section">
      <h3>💶 Tarification</h3>
      <div class="settings-body">
        <div class="form-group">
          <label class="form-label">Prix par album (€)</label>
          <div class="input-addon">
            <input type="number" name="album_price" class="form-input" value="<?= e($settings['album_price'] ?? 9.90) ?>" step="0.01" min="0">
            <span class="addon">€</span>
          </div>
          <p class="form-hint">Mettre 0 pour rendre les téléchargements gratuits</p>
        </div>
      </div>
    </div>

    <!-- Filigrane -->
    <div class="settings-section">
      <h3>🛡️ Filigrane</h3>
      <div class="settings-body">
        <div class="form-group">
          <label class="form-label">Type de filigrane</label>
          <div class="radio-group">
            <label class="radio-card">
              <input type="radio" name="watermark_type" value="text" <?= ($settings['watermark_type'] ?? 'text') === 'text' ? 'checked' : '' ?> onchange="toggleWatermark()">
              <div class="radio-card-content"><strong>Texte</strong><span>Texte personnalisé répété</span></div>
            </label>
            <label class="radio-card">
              <input type="radio" name="watermark_type" value="image" <?= ($settings['watermark_type'] ?? '') === 'image' ? 'checked' : '' ?> onchange="toggleWatermark()">
              <div class="radio-card-content"><strong>Image</strong><span>Logo ou image répété</span></div>
            </label>
          </div>
        </div>
        <div id="wm-text-options" class="form-row">
          <div class="form-group">
            <label class="form-label">Texte du filigrane</label>
            <input type="text" name="watermark_text" class="form-input" value="<?= e($settings['watermark_text'] ?? '© PicShare') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Taille du texte (px)</label>
            <input type="number" name="watermark_size" class="form-input" value="<?= e($settings['watermark_size'] ?? 24) ?>" min="10" max="72">
          </div>
        </div>
        <div id="wm-image-options" style="display:none" class="form-group">
          <label class="form-label">Image du filigrane</label>
          <input type="file" name="watermark_image" class="form-input-file" accept="image/*">
          <?php if (!empty($settings['watermark_image'])): ?>
            <p class="form-hint">Image actuelle : <img src="<?= asset('uploads/logos/' . $settings['watermark_image']) ?>" style="height:20px;vertical-align:middle;"></p>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Opacité : <span id="opacity-val"><?= $settings['watermark_opacity'] ?? 40 ?></span>%</label>
          <input type="range" name="watermark_opacity" min="10" max="90" value="<?= e($settings['watermark_opacity'] ?? 40) ?>"
                 oninput="document.getElementById('opacity-val').textContent = this.value" class="range-input">
        </div>
      </div>
    </div>

    <!-- SMTP -->
    <div class="settings-section">
      <h3>📧 Email (SMTP)</h3>
      <div class="settings-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Serveur SMTP</label>
            <input type="text" name="smtp_host" class="form-input" value="<?= e($settings['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
          </div>
          <div class="form-group">
            <label class="form-label">Port</label>
            <input type="number" name="smtp_port" class="form-input" value="<?= e($settings['smtp_port'] ?? 587) ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Utilisateur</label>
            <input type="email" name="smtp_user" class="form-input" value="<?= e($settings['smtp_user'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Mot de passe</label>
            <input type="password" name="smtp_pass" class="form-input" placeholder="••••••••">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email expéditeur</label>
            <input type="email" name="smtp_from_email" class="form-input" value="<?= e($settings['smtp_from_email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Nom expéditeur</label>
            <input type="text" name="smtp_from_name" class="form-input" value="<?= e($settings['smtp_from_name'] ?? APP_NAME) ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Chiffrement</label>
          <select name="smtp_encryption" class="form-input">
            <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (port 587)</option>
            <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (port 465)</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Stripe -->
    <div class="settings-section">
      <h3>💳 Stripe</h3>
      <div class="settings-body">
        <div class="form-group">
          <label class="form-label">Clé publique (pk_…)</label>
          <input type="text" name="stripe_public_key" class="form-input" value="<?= e($settings['stripe_public_key'] ?? '') ?>" placeholder="pk_live_…">
        </div>
        <div class="form-group">
          <label class="form-label">Clé secrète (sk_…)</label>
          <input type="password" name="stripe_secret_key" class="form-input" placeholder="sk_live_…">
        </div>
        <div class="form-group">
          <label class="form-label">Secret webhook</label>
          <input type="password" name="stripe_webhook_secret" class="form-input" placeholder="whsec_…">
          <p class="form-hint">URL du webhook à configurer dans Stripe : <code><?= BASE_URL ?>/webhook/stripe</code></p>
        </div>
      </div>
    </div>

  </div>

  <div class="form-actions form-actions-sticky">
    <button type="submit" class="btn btn-primary btn-lg">💾 Enregistrer les paramètres</button>
  </div>
</form>

<script>
function toggleWatermark() {
  const type = document.querySelector('input[name="watermark_type"]:checked')?.value;
  document.getElementById('wm-text-options').style.display = type === 'text' ? 'flex' : 'none';
  document.getElementById('wm-image-options').style.display = type === 'image' ? 'block' : 'none';
}
toggleWatermark();
</script>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
?>
