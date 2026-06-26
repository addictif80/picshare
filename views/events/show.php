<?php ob_start();
$user = currentUser();
$isOwner = ($user['role'] === 'admin' || $event['owner_id'] == $user['id']);
?>
<div class="page-header">
  <div>
    <h1><?= e($event['title']) ?></h1>
    <?php if ($event['description']): ?>
      <p class="page-subtitle"><?= e($event['description']) ?></p>
    <?php endif; ?>
  </div>
  <a href="<?= url('events/' . $event['id'] . '/albums/create') ?>" class="btn btn-primary">+ Nouvel album</a>
</div>

<div class="two-col-layout">
  <div class="col-main">
    <div class="section-title-row">
      <h2>Albums <span class="badge"><?= count($albums) ?></span></h2>
    </div>

    <?php if (empty($albums)): ?>
      <div class="empty-state">
        <div class="empty-icon">🖼️</div>
        <h3>Aucun album pour le moment</h3>
        <p>Créez votre premier album pour commencer à partager des photos.</p>
        <a href="<?= url('events/' . $event['id'] . '/albums/create') ?>" class="btn btn-primary">Créer un album</a>
      </div>
    <?php else: ?>
      <div class="albums-grid">
        <?php foreach ($albums as $album): ?>
          <div class="album-card <?= $album['is_ended'] ? 'album-ended' : '' ?>">
            <div class="album-card-status">
              <?php if ($album['payment_status'] === 'paid'): ?>
                <span class="status-badge status-paid">✓ Payé</span>
              <?php elseif ($album['is_ended']): ?>
                <span class="status-badge status-ended">Terminé</span>
              <?php elseif (albumUploadOpen($album)): ?>
                <span class="status-badge status-open">Ouvert</span>
              <?php else: ?>
                <span class="status-badge status-closed">Fermé</span>
              <?php endif; ?>
            </div>
            <div class="album-card-header">
              <h3><?= e($album['title']) ?></h3>
              <span class="photo-count"><?= $album['photo_count'] ?> photo<?= $album['photo_count'] > 1 ? 's' : '' ?></span>
            </div>
            <?php if ($album['upload_start'] || $album['upload_end']): ?>
              <div class="album-dates">
                <?php if ($album['upload_start']): ?><span>Du <?= formatDate($album['upload_start'], 'd/m/Y H:i') ?></span><?php endif; ?>
                <?php if ($album['upload_end']): ?><span>au <?= formatDate($album['upload_end'], 'd/m/Y H:i') ?></span><?php endif; ?>
              </div>
            <?php endif; ?>
            <div class="album-card-footer">
              <a href="<?= url('albums/' . $album['id'] . '/manage') ?>" class="btn btn-sm btn-outline">Gérer</a>
              <a href="<?= url('a/' . $album['access_token']) ?>" class="btn btn-sm btn-ghost" target="_blank">Vue public</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-side">
    <?php if ($isOwner): ?>
    <div class="side-card">
      <h3>Co-gestionnaires</h3>
      <?php if (!empty($managers)): ?>
        <ul class="managers-list">
          <?php foreach ($managers as $manager): ?>
            <li>
              <span class="manager-avatar"><?= strtoupper(substr($manager['name'], 0, 1)) ?></span>
              <div>
                <div class="manager-name"><?= e($manager['name']) ?></div>
                <div class="manager-email"><?= e($manager['email']) ?></div>
              </div>
              <button class="btn btn-icon btn-danger" onclick="removeManager(<?= $event['id'] ?>, <?= $manager['id'] ?>)" title="Retirer">×</button>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-muted">Aucun co-gestionnaire</p>
      <?php endif; ?>

      <form method="POST" action="<?= url('events/' . $event['id'] . '/invite') ?>" class="invite-form">
        <?= csrfField() ?>
        <div class="input-group">
          <input type="email" name="email" class="form-input" placeholder="email@exemple.com" required>
          <button type="submit" class="btn btn-primary">Inviter</button>
        </div>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function removeManager(eventId, userId) {
  if (!confirm('Retirer ce co-gestionnaire ?')) return;
  fetch(`/events/${eventId}/managers/${userId}`, { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json()).then(d => { if (d.success) location.reload(); });
}
</script>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
?>
