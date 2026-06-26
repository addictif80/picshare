<?php ob_start(); ?>
<div class="public-album-header">
  <div class="container">
    <h1><?= e($album['title']) ?></h1>
    <?php if ($album['description']): ?>
      <p class="album-subtitle"><?= e($album['description']) ?></p>
    <?php endif; ?>
    <div class="album-stats">
      <span>📸 <?= count($photos) ?> photo<?= count($photos) > 1 ? 's' : '' ?></span>
      <?php if ($isOpen): ?>
        <span class="badge badge-green">✓ Envois ouverts</span>
      <?php else: ?>
        <span class="badge badge-red">Envois fermés</span>
      <?php endif; ?>
      <?php if ($album['slideshow_active']): ?>
        <a href="<?= url('a/' . $album['access_token'] . '/slideshow') ?>" class="btn btn-sm btn-outline" target="_blank">🎭 Diaporama</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="container public-album-body">
  <!-- Guest name prompt -->
  <?php if (!$guestName && $isOpen): ?>
  <div class="guest-name-prompt" id="name-prompt">
    <div class="prompt-card">
      <h3>Avant de participer…</h3>
      <p>Quel est votre prénom ? Il sera associé à vos photos et commentaires.</p>
      <div class="input-group">
        <input type="text" id="guest-name-input" class="form-input" placeholder="Votre prénom" maxlength="50" autofocus>
        <button class="btn btn-primary" onclick="setGuestName()">Continuer →</button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Upload section -->
  <?php if ($isOpen): ?>
  <div class="upload-section <?= !$guestName ? 'hidden' : '' ?>" id="upload-section">
    <div class="upload-zone" id="public-upload-zone">
      <input type="file" id="public-file-input" multiple accept="image/*" style="display:none">
      <div class="upload-zone-inner" onclick="document.getElementById('public-file-input').click()">
        <div class="upload-icon">📤</div>
        <p><strong>Ajoutez vos photos</strong></p>
        <p class="text-muted">Cliquez ou déposez vos photos ici</p>
      </div>
      <div class="upload-progress" id="pub-progress" style="display:none">
        <div class="progress-bar"><div class="progress-fill" id="pub-progress-fill"></div></div>
        <span id="pub-progress-text">Envoi…</span>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Best-of section -->
  <?php if (!empty($bestOf)): ?>
  <div class="bestof-section">
    <div class="section-title-row">
      <h2>⭐ Best Of <span class="badge badge-gold"><?= count($bestOf) ?></span></h2>
    </div>
    <div class="photos-grid photos-grid-lg">
      <?php foreach ($bestOf as $photo): ?>
        <div class="photo-card" data-id="<?= $photo['id'] ?>">
          <div class="photo-thumb" onclick="openLightbox(<?= $photo['id'] ?>)">
            <img src="<?= url('photo/' . $photo['id'] . '/view') ?>" alt="" loading="lazy">
            <div class="bestof-badge">⭐</div>
            <div class="reaction-count">❤️ <?= $photo['reaction_count'] ?></div>
          </div>
          <div class="photo-meta">
            <span>📷 <?= e($photo['uploader_name']) ?></span>
            <span><?= timeAgo($photo['created_at']) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- All photos -->
  <div class="section-title-row">
    <h2>Toutes les photos</h2>
  </div>
  <?php if (empty($photos)): ?>
    <div class="empty-state">
      <div class="empty-icon">📸</div>
      <p>Aucune photo pour le moment. Soyez le premier à partager !</p>
    </div>
  <?php else: ?>
    <div class="photos-grid photos-grid-lg" id="photos-grid">
      <?php foreach ($photos as $photo): ?>
        <div class="photo-card" data-id="<?= $photo['id'] ?>">
          <div class="photo-thumb" onclick="openLightbox(<?= $photo['id'] ?>)">
            <img src="<?= url('photo/' . $photo['id'] . '/view') ?>" alt="" loading="lazy">
            <?php if ($photo['comment_count'] > 0): ?>
              <div class="photo-comment-badge">💬 <?= $photo['comment_count'] ?></div>
            <?php endif; ?>
          </div>
          <div class="photo-meta">
            <span>📷 <?= e($photo['uploader_name']) ?></span>
            <span><?= timeAgo($photo['created_at']) ?></span>
          </div>
          <div class="photo-reactions" id="reactions-<?= $photo['id'] ?>">
            <?php foreach (['❤️','😂','😮','👏','🔥'] as $emoji): ?>
              <button class="reaction-btn" onclick="react(<?= $photo['id'] ?>, '<?= $emoji ?>')"><?= $emoji ?></button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" style="display:none" onclick="closeLightbox(event)">
  <button class="lightbox-close" onclick="closeLightbox()">×</button>
  <button class="lightbox-prev" onclick="lightboxNav(-1)">‹</button>
  <div class="lightbox-content">
    <img src="" alt="" id="lightbox-img" class="lightbox-img">
    <div class="lightbox-info">
      <div class="lightbox-meta" id="lightbox-meta"></div>
      <div class="lightbox-reactions" id="lightbox-reactions"></div>
      <div class="lightbox-comments" id="lightbox-comments"></div>
      <?php if ($guestName || $isOpen): ?>
      <div class="comment-form" id="comment-form">
        <div class="input-group">
          <input type="text" id="comment-input" class="form-input" placeholder="Ajouter un commentaire…" maxlength="500">
          <button class="btn btn-primary btn-sm" onclick="submitComment()">Envoyer</button>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <button class="lightbox-next" onclick="lightboxNav(1)">›</button>
</div>

<script>
const albumToken = '<?= $album['access_token'] ?>';
const uploadUrl  = '/upload/' + albumToken;
let guestName    = '<?= e($guestName ?? '') ?>';
let currentPhotoId = null;
let photoIds = <?= json_encode(array_column($photos, 'id')) ?>;

function setGuestName() {
  const name = document.getElementById('guest-name-input').value.trim();
  if (!name) return;
  guestName = name;
  fetch('/guest-name/' + albumToken, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'name=' + encodeURIComponent(name)
  }).then(() => {
    document.getElementById('name-prompt').style.display = 'none';
    document.getElementById('upload-section').classList.remove('hidden');
  });
}

// Upload
const zone = document.getElementById('public-upload-zone');
const fileInput = document.getElementById('public-file-input');
if (zone) {
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => { e.preventDefault(); zone.classList.remove('drag-over'); uploadFiles(e.dataTransfer.files); });
  fileInput?.addEventListener('change', function() { uploadFiles(this.files); });
}

function uploadFiles(files) {
  const formData = new FormData();
  formData.append('name', guestName);
  for (const f of files) formData.append('photos[]', f);
  const progress = document.getElementById('pub-progress');
  if (progress) progress.style.display = 'block';
  fetch(uploadUrl, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (progress) progress.style.display = 'none';
      if (data.uploaded?.length) location.reload();
      if (data.errors?.length) alert('Erreurs :\n' + data.errors.join('\n'));
      if (!data.uploaded?.length && !data.errors?.length) alert('Aucune photo envoyée. Vérifiez le format (JPG, PNG, WEBP).');
    })
    .catch(() => {
      if (progress) progress.style.display = 'none';
      alert('Erreur réseau lors de l\'envoi. Réessayez.');
    });
}

// Lightbox
function openLightbox(id) {
  currentPhotoId = id;
  const lb = document.getElementById('lightbox');
  lb.style.display = 'flex';
  document.body.style.overflow = 'hidden';
  document.getElementById('lightbox-img').src = '/photo/' + id + '/view';
  loadPhotoDetails(id);
}

function closeLightbox(e) {
  if (e && e.target !== document.getElementById('lightbox') && !e.currentTarget.classList.contains('lightbox-close')) return;
  document.getElementById('lightbox').style.display = 'none';
  document.body.style.overflow = '';
  currentPhotoId = null;
}

function lightboxNav(dir) {
  const idx = photoIds.indexOf(currentPhotoId);
  const next = photoIds[idx + dir];
  if (next !== undefined) openLightbox(next);
}

function loadPhotoDetails(id) {
  fetch('/photo/' + id + '/details')
    .then(r => r.json())
    .then(data => {
      document.getElementById('lightbox-meta').innerHTML =
        '📷 <strong>' + data.uploader_name + '</strong> · ' + data.time_ago;
      renderReactions(data.reactions, id);
      renderComments(data.comments);
    });
}

function renderReactions(reactions, id) {
  const emojis = ['❤️','😂','😮','👏','🔥'];
  let html = emojis.map(e => {
    const r = reactions.find(x => x.type === e);
    return `<button class="reaction-btn" onclick="react(${id}, '${e}')">${e}${r ? ' <span class="reaction-count-badge">' + r.cnt + '</span>' : ''}</button>`;
  }).join('');
  document.getElementById('lightbox-reactions').innerHTML = html;
}

function renderComments(comments) {
  const el = document.getElementById('lightbox-comments');
  if (!comments.length) { el.innerHTML = '<p class="no-comments">Aucun commentaire</p>'; return; }
  el.innerHTML = comments.map(c => `
    <div class="comment-item">
      <strong>${escHtml(c.author_name)}</strong>
      <span class="comment-time">${c.time_ago}</span>
      <p>${escHtml(c.content)}</p>
    </div>
  `).join('');
}

function escHtml(t) {
  return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function react(id, type) {
  if (!guestName) { alert('Veuillez indiquer votre prénom.'); return; }
  fetch('/photo/' + id + '/react', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'name=' + encodeURIComponent(guestName) + '&type=' + encodeURIComponent(type)
  }).then(r => r.json()).then(data => {
    if (currentPhotoId === id) renderReactions(data.counts, id);
  });
}

function submitComment() {
  const input = document.getElementById('comment-input');
  const content = input.value.trim();
  if (!content || !guestName) return;
  fetch('/photo/' + currentPhotoId + '/comment', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'name=' + encodeURIComponent(guestName) + '&content=' + encodeURIComponent(content)
  }).then(r => r.json()).then(data => {
    if (data.success) { input.value = ''; loadPhotoDetails(currentPhotoId); }
  });
}

// Keyboard nav
document.addEventListener('keydown', e => {
  if (!currentPhotoId) return;
  if (e.key === 'ArrowLeft') lightboxNav(-1);
  if (e.key === 'ArrowRight') lightboxNav(1);
  if (e.key === 'Escape') closeLightbox({ target: document.getElementById('lightbox') });
});
</script>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/public.php';
?>
