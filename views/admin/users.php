<?php ob_start(); ?>
<div class="page-header">
  <div>
    <h1>Utilisateurs</h1>
    <p class="page-subtitle"><?= count($users) ?> compte<?= count($users) > 1 ? 's' : '' ?> enregistré<?= count($users) > 1 ? 's' : '' ?></p>
  </div>
</div>

<div class="data-table-wrapper">
  <table class="data-table">
    <thead>
      <tr>
        <th>Utilisateur</th>
        <th>Rôle</th>
        <th>Événements</th>
        <th>Dernière connexion</th>
        <th>Inscription</th>
        <th>Statut</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr id="user-row-<?= $user['id'] ?>">
          <td>
            <div class="user-cell">
              <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
              <div>
                <div class="user-name"><?= e($user['name']) ?></div>
                <div class="user-email"><?= e($user['email']) ?></div>
              </div>
            </div>
          </td>
          <td>
            <span class="badge <?= $user['role'] === 'admin' ? 'badge-purple' : 'badge-blue' ?>">
              <?= $user['role'] === 'admin' ? '👑 Admin' : 'Utilisateur' ?>
            </span>
          </td>
          <td><?= $user['event_count'] ?></td>
          <td><?= $user['last_login_at'] ? formatDate($user['last_login_at']) : '<span class="text-muted">Jamais</span>' ?></td>
          <td><?= formatDate($user['created_at'], 'd/m/Y') ?></td>
          <td>
            <button class="btn btn-sm <?= $user['is_active'] ? 'btn-success-outline' : 'btn-danger-outline' ?>"
                    onclick="toggleUser(<?= $user['id'] ?>)"
                    id="toggle-<?= $user['id'] ?>">
              <?= $user['is_active'] ? '✓ Actif' : '✕ Inactif' ?>
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
function toggleUser(id) {
  fetch('/admin/users/' + id + '/toggle', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const btn = document.getElementById('toggle-' + id);
        btn.textContent = data.active ? '✓ Actif' : '✕ Inactif';
        btn.className = 'btn btn-sm ' + (data.active ? 'btn-success-outline' : 'btn-danger-outline');
      }
    });
}
</script>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
?>
