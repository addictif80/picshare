<?php ob_start();
$isOpen = albumUploadOpen($album);
$isPaid = $album['payment_status'] === 'paid';
$qrUrl  = BASE_URL . '/qr/' . $album['access_token'] . '.png';
$pubUrl = BASE_URL . '/a/' . $album['access_token'];
$slideshowUrl = BASE_URL . '/a/' . $album['access_token'] . '/slideshow';
$downloadUrl  = $isPaid ? BASE_URL . '/download/' . $album['download_token'] : null;
?>
<div class="page-header">
  <div>
    <h1><?= e($album['title']) ?></h1>
    <p class="page-subtitle">Événement : <a href="<?= url('events/' . $event['id']) ?>"><?= e($event['title']) ?></a></p>
  </div>
  <div class="header-actions">
    <?php if (!$album['is_ended'] && !$isPaid): ?>
      <form method="POST" action="<?= url('albums/' . $album['id'] . '/end') ?>" onsubmit="return confirm('Terminer l\'événement ? Les envois seront définitivement fermés.')">
        <?= csrfField() ?>
        <button type="submit" class="btn btn-danger">⏹ Fin de l'événement</button>
      </form>
    <?php endif; ?>
    <?php if ($album['is_ended'] && !$isPaid): ?>
      <a href="<?= url('albums/' . $album['id'] . '/checkout') ?>" class="btn btn-success">💳 Télécharger l'album</a>
    <?php endif; ?>
    <?php if ($isPaid && $downloadUrl): ?>
      <a href="<?= $downloadUrl ?>" class="btn btn-success">⬇ Télécharger le ZIP</a>
    <?php endif; ?>
  </div>
</div>

<!-- Status bar -->
<div class="album-status-bar">
  <div class="status-item">
    <span class="status-dot <?= $isOpen ? 'dot-green' : 'dot-red' ?>"></span>
    <span><?= $isOpen ? 'Envois ouverts' : 'Envois fermés' ?></span>
  </div>
  <div class="status-item">
    <span><?= count($photos) ?> photo<?= count($photos) > 1 ? 's' : '' ?></span>
  </div>
  <div class="status-item">
    <span class="badge <?= $album['approval_mode'] === 'auto' ? 'badge-green' : 'badge-orange' ?>">
      Approbation <?= $album['approval_mode'] === 'auto' ? 'automatique' : 'manuelle' ?>
    </span>
  </div>
  <?php if ($isPaid): ?>
    <div class="status-item">
      <span class="badge badge-green">✓ Payé le <?= formatDate($album['payment_date'], 'd/m/Y') ?></span>
    </div>
    <?php if ($album['download_expires_at']): ?>
      <div class="status-item text-muted">Lien expire le <?= formatDate($album['download_expires_at'], 'd/m/Y') ?></div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<div class="manage-layout">
  <!-- Left: Photos -->
  <div class="manage-main">
    <!-- Upload zone (manager) -->
    <?php if (!$isPaid): ?>
    <div class="upload-zone" id="upload-zone">
      <input type="file" id="file-input" multiple accept="image/*" style="display:none">
      <div class="upload-zone-inner" onclick="document.getElementById('file-input').click()">
        <div class="upload-icon">📤</div>
        <p><strong>Cliquez ou déposez vos photos ici</strong></p>
        <p class="text-muted">JPG, PNG, WEBP — Max <?= setting('max_file_size_mb', 20) ?> Mo par photo</p>
      </div>
      <div class="upload-progress" id="upload-progress" style="display:none">
        <div class="progress-bar"><div class="progress-fill" id="progress-fill"></div></div>
        <span id="progress-text">Envoi en cours…</span>
      </div>
    </div>
    <?php endif; ?>

    <!-- Pending approvals -->
    <?php $pending = array_filter($photos, fn($p) => $p['status'] === 'pending'); ?>
    <?php if (!empty($pending)): ?>
      <div class="pending-section">
        <h3>⏳ En attente d'approbation (<?= count($pending) ?>)</h3>
        <div class="photos-grid">
          <?php foreach ($pending as $photo): ?>
            <div class="photo-card photo-pending" id="photo-<?= $photo['id'] ?>">
              <div class="photo-thumb">
                <img src="<?= url('photo/' . $photo['id'] . '/view') ?>" alt="" loading="lazy">
              </div>
              <div class="photo-meta">
                <span class="photo-author">📷 <?= e($photo['uploader_name']) ?></span>
                <span class="photo-time"><?= timeAgo($photo['created_at']) ?></span>
              </div>
              <div class="photo-actions">
                <button class="btn btn-sm btn-success" onclick="approvePhoto(<?= $photo['id'] ?>, 'approved')">✓ Approuver</button>
                <button class="btn btn-sm btn-danger" onclick="approvePhoto(<?= $photo['id'] ?>, 'rejected')">✕ Rejeter</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Approved photos -->
    <h3>Photos de l'album (<?= count(array_filter($photos, fn($p) => $p['status'] === 'approved')) ?>)</h3>
    <?php $approved = array_filter($photos, fn($p) => $p['status'] === 'approved'); ?>
    <?php if (empty($approved)): ?>
      <div class="empty-state">
        <p class="text-muted">Aucune photo pour le moment.</p>
      </div>
    <?php else: ?>
      <div class="photos-grid">
        <?php foreach ($approved as $photo): ?>
          <div class="photo-card" id="photo-<?= $photo['id'] ?>">
            <div class="photo-thumb">
              <img src="<?= url('photo/' . $photo['id'] . '/view') ?>" alt="" loading="lazy">
              <div class="photo-overlay">
                <?php if ($photo['comment_count'] > 0): ?>
                  <span>💬 <?= $photo['comment_count'] ?></span>
                <?php endif; ?>
                <?php if ($photo['reaction_count'] > 0): ?>
                  <span>❤️ <?= $photo['reaction_count'] ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="photo-meta">
              <span class="photo-author">📷 <?= e($photo['uploader_name']) ?></span>
              <span class="photo-time"><?= timeAgo($photo['created_at']) ?></span>
            </div>
            <?php if (!$isPaid): ?>
              <button class="btn btn-icon btn-danger photo-delete" onclick="deletePhoto(<?= $photo['id'] ?>)" title="Supprimer">🗑</button>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Right: QR + Settings -->
  <div class="manage-side">
    <div class="side-card">
      <h3>QR Code d'accès</h3>
      <div class="qr-display">
        <img src="<?= $qrUrl ?>?v=<?= time() ?>" alt="QR Code" class="qr-image" id="qr-img">
      </div>
      <div class="qr-actions">
        <a href="<?= $qrUrl ?>" download="qrcode-<?= $album['access_token'] ?>.png" class="btn btn-sm btn-outline btn-full">⬇ Télécharger le QR</a>
        <a href="<?= $pubUrl ?>" target="_blank" class="btn btn-sm btn-ghost btn-full">👁 Vue publique</a>
        <a href="<?= $slideshowUrl ?>" target="_blank" class="btn btn-sm btn-ghost btn-full">🎭 Diaporama</a>
      </div>
      <div class="qr-link">
        <input type="text" value="<?= e($pubUrl) ?>" class="form-input form-input-sm" readonly onclick="this.select()">
      </div>
    </div>

    <div class="side-card">
      <h3>Paramètres</h3>
      <form method="POST" action="<?= url('albums/' . $album['id'] . '/settings') ?>" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Titre</label>
          <input type="text" name="title" class="form-input form-input-sm" value="<?= e($album['title']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Ouverture des envois</label>
          <input type="datetime-local" name="upload_start" class="form-input form-input-sm"
                 value="<?= $album['upload_start'] ? date('Y-m-d\TH:i', strtotime($album['upload_start'])) : '' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Fermeture des envois</label>
          <input type="datetime-local" name="upload_end" class="form-input form-input-sm"
                 value="<?= $album['upload_end'] ? date('Y-m-d\TH:i', strtotime($album['upload_end'])) : '' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Approbation</label>
          <select name="approval_mode" class="form-input form-input-sm">
            <option value="auto" <?= $album['approval_mode'] === 'auto' ? 'selected' : '' ?>>Automatique</option>
            <option value="manual" <?= $album['approval_mode'] === 'manual' ? 'selected' : '' ?>>Manuelle</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Couleur QR</label>
          <input type="color" name="qr_color" value="<?= e($album['qr_color']) ?>" class="color-picker">
        </div>
        <div class="form-group">
          <label class="toggle-label">
            <input type="checkbox" name="slideshow_active" <?= $album['slideshow_active'] ? 'checked' : '' ?>>
            Diaporama actif
          </label>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-sm">Enregistrer</button>
      </form>
    </div>
  </div>
</div>

<script>
const albumToken = '<?= $album['access_token'] ?>';
const uploadUrl  = '/upload/' + albumToken;

// Drag & drop
const zone = document.getElementById('upload-zone');
if (zone) {
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => { e.preventDefault(); zone.classList.remove('drag-over'); uploadFiles(e.dataTransfer.files); });
  document.getElementById('file-input').addEventListener('change', function() { uploadFiles(this.files); });
}

function uploadFiles(files) {
  const formData = new FormData();
  for (const f of files) formData.append('photos[]', f);
  const progress = document.getElementById('upload-progress');
  const fill = document.getElementById('progress-fill');
  const text = document.getElementById('progress-text');
  if (progress) { progress.style.display = 'block'; fill.style.width = '0%'; text.textContent = 'Envoi en cours…'; }

  fetch(uploadUrl, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (progress) progress.style.display = 'none';
      if (data.uploaded?.length) { text.textContent = data.uploaded.length + ' photo(s) envoyée(s)'; location.reload(); }
      if (data.errors?.length) alert('Erreurs :\n' + data.errors.join('\n'));
    })
    .catch(() => { if (progress) progress.style.display = 'none'; alert('Erreur lors de l\'envoi.'); });
}

function approvePhoto(id, status) {
  fetch(`/photos/${id}/approve/${status}`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json()).then(d => { if (d.success) document.getElementById('photo-' + id)?.remove(); });
}

function deletePhoto(id) {
  if (!confirm('Supprimer cette photo ?')) return;
  fetch(`/photos/${id}`, { method: 'POST', headers: { 'X-HTTP-Method-Override': 'DELETE', 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json()).then(d => { if (d.success) document.getElementById('photo-' + id)?.remove(); });
}
</script>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
?>
