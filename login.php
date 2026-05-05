<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/helpers/Database.php';
require_once __DIR__ . '/src/helpers/Auth.php';

Auth::start();

if (Auth::check()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {

        $user = Database::fetchOne(
            "SELECT * FROM usuaris 
             WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) 
             AND actiu = 1",
            [$email]
        );

        if ($user && password_verify($password, $user['password'])) {
            Auth::login($user);
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }

    $error = 'Credencials incorrectes. Torneu-ho a intentar.';
}

?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Accés — Banc de Llibres</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&family=IBM+Plex+Mono&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{
  min-height:100vh;
  background: linear-gradient(135deg, #0f2555 0%, #1a4fa0 60%, #2563eb 100%);
  display:flex; align-items:center; justify-content:center;
  font-family:'IBM Plex Sans',sans-serif;
}
.login-card{
  background:#fff; border-radius:14px; padding:44px 40px 36px;
  width:380px; box-shadow: 0 20px 60px rgba(0,0,0,.3);
}
.login-logo{
  text-align:center; margin-bottom:28px;
}
.login-logo img{
  max-width:140px;
  margin-bottom:12px;
}
.login-logo h1{font-size:1.2rem;font-weight:600;color:#1e2a3a}
.login-logo p{font-size:.8rem;color:#64748b;font-family:'IBM Plex Mono',monospace}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:.82rem;font-weight:500;color:#374151;margin-bottom:5px}
.form-group input{
  width:100%;padding:10px 12px;
  border:1.5px solid #dde3ee;border-radius:7px;
  font-size:.9rem;font-family:inherit;outline:none;
  transition:border-color .15s;
}
.form-group input:focus{border-color:#1a4fa0}
.btn-login{
  width:100%;padding:11px;
  background:#1a4fa0;color:#fff;border:none;
  border-radius:7px;font-size:.9rem;font-weight:600;
  cursor:pointer;transition:background .15s;font-family:inherit;
}
.btn-login:hover{background:#153e84}
.error-msg{
  background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;
  border-radius:7px;padding:10px 12px;font-size:.83rem;margin-bottom:16px;
}
.hint{text-align:center;font-size:.75rem;color:#94a3b8;margin-top:18px}
</style>
</head>
<body>
<div class="login-card">

  <!-- LOGO DEL CENTRE (SUBSTITUEIX 📚) -->
  <div class="login-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo centre">
      <h1>Banc de Llibres</h1>
      <p><?= ANY_ESCOLAR ?></p>
  </div>

  <?php if ($error): ?>
    <div class="error-msg">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <div class="form-group">
      <label for="email">Correu electrònic</label>
      <input type="email" id="email" name="email"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
             placeholder="professor@institut.es" autocomplete="email" required>
    </div>

    <!-- LOGO ENTRE EMAIL I PASSWORD ELIMINAT -->

    <div class="form-group">
      <label for="password">Contrasenya</label>
      <input type="password" id="password" name="password"
             placeholder="••••••••" autocomplete="current-password" required>
    </div>

    <button type="submit" class="btn-login">Accedir</button>
  </form>

  <div class="hint">Per defecte: admin@admin.com / Admin1234!</div>
</div>
</body>
</html>
