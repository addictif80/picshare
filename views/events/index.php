<?php ob_start(); ?>
<div class="page-header">
  <div>
    <h1>Mes événements</h1>
    <p class="page-subtitle"><?= count($events) ?> événement<?= count($events) > 1 ? 's' : '' ?></p>
  </div>
  <a href="<?= url('events/create') ?>" class="btn btn-primary">+ Nouvel événement</a>
</div>

<?php if (empty($events)): ?>
  <div class="empty-state">
    <div class="empty-icon">📅</div>
    <h3>Aucun événement</h3>
    <a href="<?= url('events/create') ?>" class="btn btn-primary">Créer un événement</a>
  </div>
<?php else: ?>
  <div class="events-grid">
    <?php foreach ($events as $event): ?>
      <div class="event-card">
        <div class="event-card-header">
          <h3><?= e($event['title']) ?></h3>
          <span class="badge"><?= $event['album_count'] ?> album<?= $event['album_count'] > 1 ? 's' : '' ?></span>
        </div>
        <?php if ($event['description']): ?>
          <p class="event-desc"><?= e(substr($event['description'], 0, 120)) ?><?= strlen($event['description']) > 120 ? '…' : '' ?></p>
        <?php endif; ?>
        <div class="event-meta">
          <span>Par <?= e($event['owner_name']) ?></span>
          <span><?= formatDate($event['created_at'], 'd/m/Y') ?></span>
        </div>
        <div class="event-card-footer">
          <a href="<?= url('events/' . $event['id']) ?>" class="btn btn-sm btn-outline">Gérer →</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
?>
