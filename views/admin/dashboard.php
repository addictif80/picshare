<?php ob_start(); ?>
<div class="page-header">
  <div>
    <h1>Administration</h1>
    <p class="page-subtitle">Vue globale du système <?= APP_NAME ?></p>
  </div>
  <div class="header-actions">
    <button class="btn btn-outline" onclick="runCleanup()">🧹 Nettoyage auto</button>
  </div>
</div>

<div class="stats-grid stats-grid-lg">
  <div class="stat-card">
    <div class="stat-icon">👥</div>
    <div class="stat-value"><?= $stats['users'] ?></div>
    <div class="stat-label">Utilisateurs</div>
  </div>
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
  <div class="stat-card stat-card-success">
    <div class="stat-icon">💶</div>
    <div class="stat-value"><?= number_format($stats['revenue'], 2) ?> €</div>
    <div class="stat-label">Revenus totaux</div>
  </div>
  <?php if ($stats['pending_photos'] > 0): ?>
  <div class="stat-card stat-card-warning">
    <div class="stat-icon">⏳</div>
    <div class="stat-value"><?= $stats['pending_photos'] ?></div>
    <div class="stat-label">Photos en attente</div>
  </div>
  <?php endif; ?>
</div>

<div class="admin-nav-grid">
  <a href="<?= url('admin/settings') ?>" class="admin-nav-card">
    <span class="admin-nav-icon">⚙️</span>
    <h3>Paramètres système</h3>
    <p>SMTP, Stripe, filigrane, tarification</p>
  </a>
  <a href="<?= url('admin/users') ?>" class="admin-nav-card">
    <span class="admin-nav-icon">👥</span>
    <h3>Utilisateurs</h3>
    <p>Gérer les comptes utilisateurs</p>
  </a>
  <a href="<?= url('admin/promo-codes') ?>" class="admin-nav-card">
    <span class="admin-nav-icon">🎟️</span>
    <h3>Codes promo</h3>
    <p>Créer et gérer les réductions</p>
  </a>
  <a href="<?= url('events') ?>" class="admin-nav-card">
    <span class="admin-nav-icon">📅</span>
    <h3>Tous les événements</h3>
    <p>Voir et gérer tous les événements</p>
  </a>
</div>

<script>
function runCleanup() {
  if (!confirm('Lancer le nettoyage des albums expirés ?')) return;
  fetch('/admin/cleanup', { method: 'POST' })
    .then(r => r.json())
    .then(data => alert(data.deleted + ' album(s) supprimé(s).'));
}
</script>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
?>
