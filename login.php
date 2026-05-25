<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/helpers/Database.php';
require_once __DIR__ . '/src/helpers/Auth.php';

Auth::start();

if (Auth::check()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$_darkMode = !empty($_SESSION['dark_mode']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $user = Database::fetchOne(
            "SELECT * FROM usuaris WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) AND actiu = 1",
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
<html lang="ca" data-bs-theme="<?= $_darkMode ? 'dark' : 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Accés — Banc de Llibres</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&family=IBM+Plex+Mono&display=swap" rel="stylesheet">
<style>
:root {
  --lc-bg-outer: #0f1a3a;
  --lc-bg-card:  #ffffff;
  --lc-text:     #1e2a3a;
  --lc-muted:    #64748b;
  --lc-border:   #dde3ee;
  --lc-btn-bg:   #1a4fa0;
  --lc-btn-hov:  #153e84;
  --lc-err-bg:   #fee2e2;
  --lc-err-c:    #991b1b;
  --lc-err-b:    #fca5a5;
  --lc-hint:     #94a3b8;
  --lc-toggle-bg: rgba(255,255,255,.12);
  --lc-toggle-c:  rgba(255,255,255,.85);
}
[data-bs-theme="dark"] {
  --lc-bg-outer: #080c16;
  --lc-bg-card:  #1c1f2e;
  --lc-text:     #dde1ec;
  --lc-muted:    #8892b0;
  --lc-border:   #2a2f45;
  --lc-btn-bg:   #1a4fa0;
  --lc-btn-hov:  #2563eb;
  --lc-err-bg:   #2a1215;
  --lc-err-c:    #fca5a5;
  --lc-err-b:    #c62828;
  --lc-hint:     #555e7a;
  --lc-toggle-bg: rgba(255,255,255,.10);
  --lc-toggle-c:  rgba(255,255,255,.70);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  min-height: 100vh;
  background: var(--lc-bg-outer);
  display: flex; align-items: center; justify-content: center;
  font-family: 'IBM Plex Sans', sans-serif;
  transition: background .25s;
}
.login-card {
  background: var(--lc-bg-card);
  border-radius: 14px;
  padding: 44px 40px 36px;
  width: 380px;
  box-shadow: 0 20px 60px rgba(0,0,0,.35);
  transition: background .25s;
}
.login-logo          { text-align: center; margin-bottom: 28px; }
.login-logo img      { max-width: 140px; margin-bottom: 12px; }
.login-logo h1       { font-size: 1.2rem; font-weight: 600; color: var(--lc-text); }
.login-logo p        { font-size: .8rem; color: var(--lc-muted); font-family: 'IBM Plex Mono', monospace; }
.form-group          { margin-bottom: 16px; }
.form-group label    { display: block; font-size: .82rem; font-weight: 500; color: var(--lc-text); margin-bottom: 5px; }
.form-group input    {
  width: 100%; padding: 10px 12px;
  border: 1.5px solid var(--lc-border);
  border-radius: 7px;
  font-size: .9rem; font-family: inherit; outline: none;
  background: var(--lc-bg-card); color: var(--lc-text);
  transition: border-color .15s;
}
.form-group input:focus { border-color: var(--lc-btn-bg); }
.btn-login {
  width: 100%; padding: 11px;
  background: var(--lc-btn-bg); color: #fff; border: none;
  border-radius: 7px; font-size: .9rem; font-weight: 600;
  cursor: pointer; transition: background .15s; font-family: inherit;
}
.btn-login:hover { background: var(--lc-btn-hov); }
.error-msg {
  background: var(--lc-err-bg); color: var(--lc-err-c);
  border: 1px solid var(--lc-err-b);
  border-radius: 7px; padding: 10px 12px; font-size: .83rem; margin-bottom: 16px;
}
.hint { text-align: center; font-size: .75rem; color: var(--lc-hint); margin-top: 18px; }

/* Dark mode floating toggle button */
.login-theme-btn {
  position: fixed; top: 16px; right: 16px;
  display: flex; align-items: center; gap: .45rem;
  padding: .45rem .9rem;
  background: var(--lc-toggle-bg);
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 20px;
  color: var(--lc-toggle-c);
  font-size: .82rem; font-weight: 500;
  cursor: pointer; font-family: inherit;
  backdrop-filter: blur(6px);
  transition: background .15s, color .15s;
  z-index: 999;
}
.login-theme-btn:hover { background: rgba(255,255,255,.22); color: #fff; }
</style>
</head>
<body>

<!-- Dark mode toggle (floating, top-right) -->
<button class="login-theme-btn" onclick="toggleDark()">
  <i class="<?= $_darkMode ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill' ?>" id="loginToggleIcon"></i>
  <span id="loginToggleTxt"><?= $_darkMode ? 'Mode clar' : 'Mode fosc' ?></span>
</button>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="login-card">

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
    <div class="form-group">
      <label for="password">Contrasenya</label>
      <input type="password" id="password" name="password"
             placeholder="••••••••" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn-login">Accedir</button>
  </form>

  <div class="hint">Per defecte: admin@admin.com / Admin1234!</div>
</div>

<script>
(function () {
  const icon  = document.getElementById('loginToggleIcon');
  const label = document.getElementById('loginToggleTxt');

  function sync() {
    const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    icon.className    = dark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    label.textContent = dark ? 'Mode clar' : 'Mode fosc';
  }

  window.toggleDark = function () {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-bs-theme') === 'dark';
    html.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
    sync();
    fetch('<?= BASE_URL ?>/toggle_theme.php').catch(() => {});
  };
})();
</script>

</body>
</html>
