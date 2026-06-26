<?php ob_start(); ?>
<div class="page-header">
  <div>
    <h1>Codes promo</h1>
    <p class="page-subtitle">Gérez les réductions pour vos clients</p>
  </div>
</div>

<div class="two-col-layout">
  <div class="col-main">
    <?php if (empty($promos)): ?>
      <div class="empty-state">
        <div class="empty-icon">🎟️</div>
        <h3>Aucun code promo</h3>
      </div>
    <?php else: ?>
      <div class="data-table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Remise</th>
              <th>Utilisations</th>
              <th>Expiration</th>
              <th>Statut</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($promos as $promo): ?>
              <tr id="promo-row-<?= $promo['id'] ?>">
                <td><code class="code-badge"><?= e($promo['code']) ?></code></td>
                <td>
                  <?php if ($promo['discount_percent']): ?>
                    <span class="badge badge-green">-<?= $promo['discount_percent'] ?>%</span>
                  <?php elseif ($promo['discount_fixed']): ?>
                    <span class="badge badge-green">-<?= number_format($promo['discount_fixed'], 2) ?> €</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?= $promo['used_count'] ?>
                  <?php if ($promo['max_uses']): ?>/ <?= $promo['max_uses'] ?><?php endif; ?>
                </td>
                <td><?= $promo['expires_at'] ? formatDate($promo['expires_at'], 'd/m/Y') : '<span class="text-muted">∞</span>' ?></td>
                <td>
                  <button class="btn btn-sm <?= $promo['is_active'] ? 'btn-success-outline' : 'btn-danger-outline' ?>"
                          onclick="togglePromo(<?= $promo['id'] ?>)" id="promo-toggle-<?= $promo['id'] ?>">
                    <?= $promo['is_active'] ? '✓ Actif' : '✕ Inactif' ?>
                  </button>
                </td>
                <td>
                  <button class="btn btn-sm btn-danger-outline" onclick="deletePromo(<?= $promo['id'] ?>)">🗑</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-side">
    <div class="side-card">
      <h3>Créer un code promo</h3>
      <form method="POST" action="<?= url('admin/promo-codes') ?>">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Code <span class="required">*</span></label>
          <input type="text" name="code" class="form-input" placeholder="SUMMER20" required
                 style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase()">
        </div>
        <div class="form-group">
          <label class="form-label">Remise en %</label>
          <input type="number" name="discount_percent" class="form-input" min="1" max="100" placeholder="20">
        </div>
        <div class="form-group">
          <label class="form-label">Remise fixe (€)</label>
          <input type="number" name="discount_fixed" class="form-input" min="0.01" step="0.01" placeholder="5.00">
        </div>
        <p class="form-hint">Indiquez l'un ou l'autre, pas les deux.</p>
        <div class="form-group">
          <label class="form-label">Utilisations max</label>
          <input type="number" name="max_uses" class="form-input" min="1" placeholder="Illimité">
        </div>
        <div class="form-group">
          <label class="form-label">Date d'expiration</label>
          <input type="date" name="expires_at" class="form-input">
        </div>
        <button type="submit" class="btn btn-primary btn-full">Créer le code</button>
      </form>
    </div>
  </div>
</div>

<script>
function togglePromo(id) {
  fetch('/admin/promo-codes/' + id + '/toggle', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
      const btn = document.getElementById('promo-toggle-' + id);
      btn.textContent = data.active ? '✓ Actif' : '✕ Inactif';
      btn.className = 'btn btn-sm ' + (data.active ? 'btn-success-outline' : 'btn-danger-outline');
    });
}
function deletePromo(id) {
  if (!confirm('Supprimer ce code promo ?')) return;
  fetch('/admin/promo-codes/' + id, { method: 'POST', headers: { 'X-HTTP-Method-Override': 'DELETE', 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(d => { if (d.success) document.getElementById('promo-row-' + id)?.remove(); });
}
</script>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
?>
