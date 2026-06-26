<?php ob_start(); ?>
<div class="page-header">
  <div>
    <h1>Bonjour, <?= e(currentUser()['name']) ?> 👋</h1>
    <p class="page-subtitle">Gérez vos événements et albums photo</p>
  </div>
  <a href="<?= url('events/create') ?>" class="btn btn-primary">+ Nouvel événement</a>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">📅</div>
    <div class="stat-value"><?= $stats['events'] ?></div>
    <div class="stat-label">Événements</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🖼️</div>
    <div class="stat-value"><?= $stats['albums'] ?></div>
    <div class="stat-label">Albums</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📸</div>
    <div class="stat-value"><?= $stats['photos'] ?></div>
    <div class="stat-label">Photos</div>
  </div>
  <?php if ($stats['pending_photos'] > 0): ?>
  <div class="stat-card stat-card-warning">
    <div class="stat-icon">⏳</div>
    <div class="stat-value"><?= $stats['pending_photos'] ?></div>
    <div class="stat-label">Photos en attente</div>
  </div>
  <?php endif; ?>
</div>

<?php if (empty($recentEvents)): ?>
  <div class="empty-state">
    <div class="empty-icon">📷</div>
    <h3>Aucun événement pour le moment</h3>
    <p>Créez votre premier événement et commencez à partager vos souvenirs.</p>
    <a href="<?= url('events/create') ?>" class="btn btn-primary">Créer mon premier événement</a>
  </div>
<?php else: ?>
  <div class="section-title-row">
    <h2>Événements récents</h2>
    <a href="<?= url('events') ?>" class="link">Voir tout →</a>
  </div>
  <div class="events-grid">
    <?php foreach ($recentEvents as $event): ?>
      <div class="event-card">
        <div class="event-card-header">
          <h3><?= e($event['title']) ?></h3>
          <span class="badge"><?= $event['album_count'] ?> album<?= $event['album_count'] > 1 ? 's' : '' ?></span>
        </div>
        <?php if ($event['description']): ?>
          <p class="event-desc"><?= e(substr($event['description'], 0, 100)) ?><?= strlen($event['description']) > 100 ? '…' : '' ?></p>
        <?php endif; ?>
        <div class="event-card-footer">
          <span class="event-date"><?= formatDate($event['created_at'], 'd/m/Y') ?></span>
          <a href="<?= url('events/' . $event['id']) ?>" class="btn btn-sm btn-outline">Gérer</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
?>
