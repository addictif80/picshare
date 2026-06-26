<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installation — PicShare</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,sans-serif;background:linear-gradient(135deg,#1a1a2e,#16213e);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.card{background:#fff;border-radius:16px;padding:48px;max-width:560px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.2)}
.brand{text-align:center;margin-bottom:32px}
.brand-text{font-size:32px;font-weight:700;background:linear-gradient(135deg,#6366f1,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
h2{font-size:22px;margin-bottom:4px;color:#1a1a2e}
p.sub{color:#6b7280;font-size:14px;margin-bottom:28px}
.step-indicator{display:flex;gap:8px;margin-bottom:32px}
.step-dot{flex:1;height:4px;border-radius:2px;background:#e5e7eb}
.step-dot.done{background:#6366f1}
.form-group{margin-bottom:18px}
label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
input,select{width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:14px;font-family:Inter,sans-serif;transition:border-color .2s;outline:none}
input:focus,select:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}
.btn{display:block;width:100%;padding:13px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;font-family:Inter,sans-serif;transition:all .2s}
.btn:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(99,102,241,.35)}
.alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:14px}
.alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fca5a5}
.alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.hint{font-size:12px;color:#6b7280;margin-top:4px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
</style>
</head>
<body>
<?php

define('BASE_PATH', dirname(__DIR__));
$step  = (int) ($_GET['step'] ?? 1);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        // Test DB connection
        $host = trim($_POST['db_host'] ?? '127.0.0.1');
        $port = trim($_POST['db_port'] ?? '3306');
        $name = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = $_POST['db_pass'] ?? '';

        if (!$name || !$user) {
            $error = 'Veuillez remplir tous les champs requis.';
        } else {
            try {
                $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                // Create database if not exists
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$name}`");

                // Run schema
                $sql = file_get_contents(BASE_PATH . '/install/schema.sql');
                $pdo->exec($sql);

                // Write config
                $configContent = "<?php\nreturn [\n    'host'     => " . var_export($host, true) . ",\n    'port'     => " . var_export($port, true) . ",\n    'dbname'   => " . var_export($name, true) . ",\n    'username' => " . var_export($user, true) . ",\n    'password' => " . var_export($pass, true) . ",\n    'charset'  => 'utf8mb4',\n];\n";
                file_put_contents(BASE_PATH . '/config/database.php', $configContent);

                header('Location: /install/?step=2');
                exit;
            } catch (PDOException $e) {
                $error = 'Connexion échouée : ' . $e->getMessage();
            }
        }
    } elseif ($step === 2) {
        // Create admin user
        require BASE_PATH . '/config/config.php';
        $dbCfg = require BASE_PATH . '/config/database.php';
        $pdo = new PDO("mysql:host={$dbCfg['host']};port={$dbCfg['port']};dbname={$dbCfg['dbname']};charset=utf8mb4", $dbCfg['username'], $dbCfg['password']);

        $name  = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));

        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Nom et email valide requis.';
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
            $stmt->execute(['admin']);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Un administrateur existe déjà.';
            } else {
                $pdo->prepare('INSERT INTO users (email, name, role) VALUES (?, ?, ?)')->execute([$email, $name, 'admin']);

                // Create .installed flag
                file_put_contents(BASE_PATH . '/install/.installed', date('Y-m-d H:i:s'));

                header('Location: /install/?step=3');
                exit;
            }
        }
    }
}
?>
<div class="card">
  <div class="brand"><span class="brand-text">PicShare</span></div>

  <?php if ($step === 1): ?>
    <div class="step-indicator">
      <div class="step-dot done"></div>
      <div class="step-dot"></div>
      <div class="step-dot"></div>
    </div>
    <h2>Base de données</h2>
    <p class="sub">Étape 1 sur 2 — Connexion MySQL</p>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <div class="row">
        <div class="form-group">
          <label>Hôte <span style="color:#ef4444">*</span></label>
          <input type="text" name="db_host" value="127.0.0.1" required>
        </div>
        <div class="form-group">
          <label>Port</label>
          <input type="number" name="db_port" value="3306">
        </div>
      </div>
      <div class="form-group">
        <label>Nom de la base <span style="color:#ef4444">*</span></label>
        <input type="text" name="db_name" placeholder="picshare" required>
      </div>
      <div class="row">
        <div class="form-group">
          <label>Utilisateur <span style="color:#ef4444">*</span></label>
          <input type="text" name="db_user" placeholder="root" required>
        </div>
        <div class="form-group">
          <label>Mot de passe</label>
          <input type="password" name="db_pass">
        </div>
      </div>
      <button type="submit" class="btn">Suivant →</button>
    </form>

  <?php elseif ($step === 2): ?>
    <div class="step-indicator">
      <div class="step-dot done"></div>
      <div class="step-dot done"></div>
      <div class="step-dot"></div>
    </div>
    <h2>Compte administrateur</h2>
    <p class="sub">Étape 2 sur 2 — Premier utilisateur (administrateur)</p>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Nom complet <span style="color:#ef4444">*</span></label>
        <input type="text" name="name" placeholder="Marie Dupont" required autofocus>
      </div>
      <div class="form-group">
        <label>Email <span style="color:#ef4444">*</span></label>
        <input type="email" name="email" placeholder="admin@exemple.com" required>
        <p class="hint">Cet email sera utilisé pour la connexion par code OTP.</p>
      </div>
      <button type="submit" class="btn">Créer l'administrateur →</button>
    </form>

  <?php elseif ($step === 3): ?>
    <div class="step-indicator">
      <div class="step-dot done"></div>
      <div class="step-dot done"></div>
      <div class="step-dot done"></div>
    </div>
    <div style="text-align:center;padding:20px 0">
      <div style="font-size:56px;margin-bottom:16px">🎉</div>
      <h2 style="margin-bottom:8px">Installation réussie !</h2>
      <p class="sub" style="margin-bottom:32px">PicShare est prêt. Connectez-vous pour configurer SMTP et Stripe.</p>
      <a href="/login" class="btn" style="display:inline-block;max-width:240px;text-decoration:none">Se connecter →</a>
    </div>
    <div class="alert alert-error" style="margin-top:24px">
      ⚠️ Pour la sécurité, <strong>supprimez ou protégez le dossier <code>/install/</code></strong> de votre serveur.
    </div>
  <?php endif; ?>
</div>
</body>
</html>
