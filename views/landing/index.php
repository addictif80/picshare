<?php
$content = ob_start() ? '' : '';
ob_start();
?>
<section class="hero">
  <div class="hero-bg"></div>
  <div class="container hero-content">
    <div class="hero-badge">✨ Nouveau — Partagez en un flash</div>
    <h1 class="hero-title">
      Vos événements méritent<br>
      <span class="gradient-text">des souvenirs partagés</span>
    </h1>
    <p class="hero-subtitle">
      Créez un album, partagez un QR code, et laissez tous vos invités contribuer à la galerie de l'événement.
      Sans inscription pour eux, sans friction.
    </p>
    <div class="hero-actions">
      <a href="<?= url('register') ?>" class="btn btn-primary btn-lg">Commencer gratuitement</a>
      <a href="#features" class="btn btn-ghost btn-lg">Découvrir</a>
    </div>
    <div class="hero-visual">
      <div class="hero-card">
        <div class="hero-card-header">
          <div class="hero-card-dots"><span></span><span></span><span></span></div>
        </div>
        <div class="hero-card-body">
          <div class="hero-qr-demo">
            <div class="qr-placeholder">
              <svg viewBox="0 0 100 100" width="120" height="120">
                <rect x="10" y="10" width="30" height="30" fill="none" stroke="#6366f1" stroke-width="4"/>
                <rect x="60" y="10" width="30" height="30" fill="none" stroke="#6366f1" stroke-width="4"/>
                <rect x="10" y="60" width="30" height="30" fill="none" stroke="#6366f1" stroke-width="4"/>
                <rect x="16" y="16" width="18" height="18" fill="#6366f1"/>
                <rect x="66" y="16" width="18" height="18" fill="#6366f1"/>
                <rect x="16" y="66" width="18" height="18" fill="#6366f1"/>
                <rect x="60" y="60" width="8" height="8" fill="#6366f1"/>
                <rect x="72" y="60" width="8" height="8" fill="#6366f1"/>
                <rect x="60" y="72" width="8" height="8" fill="#6366f1"/>
                <rect x="72" y="72" width="8" height="8" fill="#6366f1"/>
              </svg>
            </div>
            <div class="hero-photos-grid">
              <div class="hero-photo-item" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)"></div>
              <div class="hero-photo-item" style="background:linear-gradient(135deg,#8b5cf6,#ec4899)"></div>
              <div class="hero-photo-item" style="background:linear-gradient(135deg,#f59e0b,#ef4444)"></div>
              <div class="hero-photo-item" style="background:linear-gradient(135deg,#10b981,#06b6d4)"></div>
            </div>
          </div>
          <div class="hero-upload-indicator">
            <div class="upload-pulse"></div>
            <span>12 photos ajoutées</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="features" class="features">
  <div class="container">
    <div class="section-header">
      <h2>Tout ce qu'il vous faut</h2>
      <p>Une plateforme complète pour gérer vos événements photo de A à Z</p>
    </div>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">📸</div>
        <h3>QR Code instantané</h3>
        <p>Chaque album génère un QR code unique et personnalisable. Vos invités scannent et partagent leurs photos immédiatement, sans créer de compte.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🎭</div>
        <h3>Diaporama en direct</h3>
        <p>Affichez les photos en temps réel sur un grand écran pendant votre événement. Les photos apparaissent au fur et à mesure des uploads.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">❤️</div>
        <h3>Réactions & Commentaires</h3>
        <p>Les invités peuvent réagir et commenter les photos. Un album "Best Of" se génère automatiquement avec les clichés les plus appréciés.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🛡️</div>
        <h3>Protection par filigrane</h3>
        <p>Les photos sont automatiquement protégées par un filigrane invisible à la suppression via le navigateur. Le téléchargement payant inclut les originaux sans filigrane.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📅</div>
        <h3>Contrôle des envois</h3>
        <p>Définissez des plages horaires d'envoi, ou clôturez l'album à tout moment. Tout est sous votre contrôle.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📦</div>
        <h3>Archive complète</h3>
        <p>Téléchargez toutes les photos en ZIP haute qualité, avec la liste des participants et un rapport complet des commentaires.</p>
      </div>
    </div>
  </div>
</section>

<section class="how-it-works">
  <div class="container">
    <div class="section-header">
      <h2>En 3 étapes simples</h2>
    </div>
    <div class="steps">
      <div class="step">
        <div class="step-number">1</div>
        <h3>Créez votre album</h3>
        <p>Inscrivez-vous, créez un événement et un album. Un QR code est généré automatiquement.</p>
      </div>
      <div class="step-arrow">→</div>
      <div class="step">
        <div class="step-number">2</div>
        <h3>Partagez le QR code</h3>
        <p>Imprimez-le, affichez-le, envoyez-le. Vos invités scannent et partagent leurs photos.</p>
      </div>
      <div class="step-arrow">→</div>
      <div class="step">
        <div class="step-number">3</div>
        <h3>Téléchargez l'album</h3>
        <p>À la clôture de l'événement, téléchargez l'intégralité des photos en qualité originale.</p>
      </div>
    </div>
  </div>
</section>

<section class="pricing">
  <div class="container">
    <div class="section-header">
      <h2>Tarification simple</h2>
      <p>Un seul tarif, transparent. Parce que vos souvenirs n'ont pas de prix.</p>
    </div>
    <div class="pricing-card-wrapper">
      <div class="pricing-card">
        <div class="pricing-badge">⭐ Soutien à la plateforme</div>
        <div class="pricing-amount">
          <span class="pricing-currency">€</span>
          <span class="pricing-price"><?= number_format((float) setting('album_price', 9.90), 2, ',', '') ?></span>
        </div>
        <p class="pricing-desc">par album téléchargé</p>
        <ul class="pricing-features">
          <li>✓ Toutes les photos en qualité originale</li>
          <li>✓ Sans filigrane</li>
          <li>✓ Fichier ZIP avec liste des participants</li>
          <li>✓ Rapport des commentaires & réactions</li>
          <li>✓ Lien de téléchargement sécurisé (3 mois)</li>
          <li>✓ Photos accessibles en ligne (3 mois)</li>
        </ul>
        <a href="<?= url('register') ?>" class="btn btn-primary btn-full">Commencer</a>
      </div>
    </div>
  </div>
</section>

<section class="cta-section">
  <div class="container">
    <div class="cta-card">
      <h2>Prêt à partager vos souvenirs ?</h2>
      <p>Créez votre compte en quelques secondes et organisez votre prochain événement photo.</p>
      <div class="cta-actions">
        <a href="<?= url('register') ?>" class="btn btn-primary btn-lg">Créer mon compte</a>
        <a href="<?= url('login') ?>" class="btn btn-ghost btn-lg">J'ai déjà un compte</a>
      </div>
    </div>
  </div>
</section>

<footer class="landing-footer">
  <div class="container landing-footer-inner">
    <div class="footer-brand">
      <span class="brand-text"><?= APP_NAME ?></span>
      <p><?= e(setting('site_tagline', 'Partagez vos souvenirs')) ?></p>
    </div>
    <div class="footer-links">
      <a href="<?= url('login') ?>">Connexion</a>
      <a href="<?= url('register') ?>">Inscription</a>
    </div>
    <p class="footer-copy">© <?= date('Y') ?> <?= APP_NAME ?> — Tous droits réservés</p>
  </div>
</footer>

<?php
$content = ob_get_clean();
$layout = 'landing';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_NAME ?> — <?= e(setting('site_tagline', 'Partagez vos souvenirs')) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="landing-body">
<?= $content ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
