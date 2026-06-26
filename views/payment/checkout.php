<?php ob_start(); ?>
<div class="page-header">
  <div>
    <h1>Télécharger l'album</h1>
    <p class="page-subtitle">Album : <strong><?= e($album['title']) ?></strong></p>
  </div>
</div>

<div class="checkout-layout">
  <div class="checkout-main">
    <div class="form-card">
      <h3>Votre commande</h3>

      <div class="order-line">
        <span>Album "<?= e($album['title']) ?>"</span>
        <span id="price-display"><?= number_format($price, 2) ?> €</span>
      </div>
      <div class="order-line order-line-promo" id="promo-line" style="display:none">
        <span id="promo-label">Code promo</span>
        <span id="promo-discount" class="text-success"></span>
      </div>
      <div class="order-divider"></div>
      <div class="order-line order-total">
        <span>Total</span>
        <strong id="total-display"><?= number_format($price, 2) ?> €</strong>
      </div>

      <div class="form-group mt-4">
        <label class="form-label">Code promo</label>
        <div class="input-group">
          <input type="text" id="promo-input" class="form-input" placeholder="MONCODE">
          <button class="btn btn-outline" onclick="applyPromo()">Appliquer</button>
        </div>
        <p class="form-hint" id="promo-msg"></p>
      </div>

      <button class="btn btn-primary btn-full btn-lg" onclick="startCheckout()" id="pay-btn">
        💳 Payer et télécharger
      </button>

      <p class="checkout-note">
        Paiement sécurisé par Stripe. Après paiement, vous recevrez un lien de téléchargement valable 3 mois.
        Les photos originales sans filigrane seront incluses dans l'archive ZIP.
      </p>
    </div>
  </div>

  <div class="checkout-side">
    <div class="side-card">
      <h3>Contenu de l'archive</h3>
      <ul class="checklist">
        <li>✓ Toutes les photos en qualité originale</li>
        <li>✓ Sans filigrane</li>
        <li>✓ Liste des participants</li>
        <li>✓ Commentaires & réactions (HTML imprimable)</li>
        <li>✓ Lien valable 3 mois</li>
      </ul>
    </div>
    <div class="side-card">
      <h3>Statistiques</h3>
      <?php
      $stats = \PicShare\Core\Database::fetch(
        'SELECT COUNT(*) as total,
         (SELECT COUNT(*) FROM comments c JOIN photos p2 ON p2.id = c.photo_id WHERE p2.album_id = ?) as comments,
         (SELECT COUNT(*) FROM reactions r JOIN photos p3 ON p3.id = r.photo_id WHERE p3.album_id = ?) as reactions,
         (SELECT COUNT(DISTINCT uploader_name) FROM photos WHERE album_id = ? AND status = ?) as participants
         FROM photos WHERE album_id = ? AND status = ?',
        [$album['id'], $album['id'], $album['id'], 'approved', $album['id'], 'approved']
      );
      ?>
      <div class="stat-row"><span>📸 Photos</span><strong><?= $stats['total'] ?></strong></div>
      <div class="stat-row"><span>💬 Commentaires</span><strong><?= $stats['comments'] ?></strong></div>
      <div class="stat-row"><span>❤️ Réactions</span><strong><?= $stats['reactions'] ?></strong></div>
      <div class="stat-row"><span>👥 Participants</span><strong><?= $stats['participants'] ?></strong></div>
    </div>
  </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe     = Stripe('<?= e($stripeKey) ?>');
const albumId    = <?= $album['id'] ?>;
const basePrice  = <?= $price ?>;
let promoCode    = '';
let finalPrice   = basePrice;

function applyPromo() {
  const code = document.getElementById('promo-input').value.trim();
  if (!code) return;
  fetch('/payment/promo', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'code=' + encodeURIComponent(code)
  })
  .then(r => r.json())
  .then(data => {
    const msg = document.getElementById('promo-msg');
    if (data.valid) {
      promoCode = code;
      finalPrice = parseFloat(data.price_final);
      document.getElementById('promo-line').style.display = 'flex';
      document.getElementById('promo-label').textContent = 'Code : ' + code;
      document.getElementById('promo-discount').textContent = data.label;
      document.getElementById('total-display').textContent = data.price_final + ' €';
      msg.className = 'form-hint text-success';
      msg.textContent = data.message;
    } else {
      promoCode = '';
      msg.className = 'form-hint text-error';
      msg.textContent = data.message;
    }
  });
}

function startCheckout() {
  const btn = document.getElementById('pay-btn');
  btn.disabled = true;
  btn.textContent = 'Redirection…';

  const body = 'promo_code=' + encodeURIComponent(promoCode);
  fetch('/payment/' + albumId + '/session', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body
  })
  .then(r => r.json())
  .then(data => {
    if (data.free) {
      window.location.href = data.redirect;
    } else if (data.url) {
      window.location.href = data.url;
    } else {
      alert(data.error || 'Erreur lors du paiement.');
      btn.disabled = false;
      btn.textContent = '💳 Payer et télécharger';
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.textContent = '💳 Payer et télécharger';
  });
}
</script>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
?>
