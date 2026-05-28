<?php
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

// Detect dark mode from session
Auth::start();
$_darkMode = !empty($_SESSION['dark_mode']);
?>
<!DOCTYPE html>
<html lang="ca" data-bs-theme="<?= $_darkMode ? 'dark' : 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($titolPagina ?? APP_NAME) ?> — <?= APP_NAME ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style><?php include __DIR__ . '/layout_styles.css'; ?></style>
</head>
<body>

<?php if (Auth::check()): ?>
<aside class="sidebar" id="sidebar">

  <div class="sidebar-brand">
    <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo">
    <div class="nav-label"><h1><i class="bi bi-book-half"></i> <?= APP_NAME ?></h1>
    <small><?= ANY_ESCOLAR ?></small></div>
  </div>

  <nav>
    <div class="nav-section"><span class="nav-label">Principal</span></div>

    <a href="<?= BASE_URL ?>/index.php"
       class="nav-link <?= ($paginaActiva??'')==='inici' ? 'active':'' ?>"
       title="Inici">
      <i class="bi bi-house-fill"></i><span class="nav-label"> Inici</span>
    </a>

    <a href="<?= BASE_URL ?>/classes/classes.php"
       class="nav-link <?= ($paginaActiva??'')==='classes' ? 'active':'' ?>"
       title="Classes">
      <i class="bi bi-people-fill"></i><span class="nav-label"> Classes</span>
    </a>

    <div class="nav-section"><span class="nav-label">Inventari</span></div>

    <a href="<?= BASE_URL ?>/materies/materies.php"
       class="nav-link <?= ($paginaActiva??'')==='materies' ? 'active':'' ?>"
       title="Matèries">
      <i class="bi bi-tags-fill"></i><span class="nav-label"> Matèries</span>
    </a>

    <a href="<?= BASE_URL ?>/llibres/llibres.php"
       class="nav-link <?= ($paginaActiva??'')==='llibres' ? 'active':'' ?>"
       title="Llibres">
      <i class="bi bi-journal-text"></i><span class="nav-label"> Llibres</span>
    </a>

    <a href="<?= BASE_URL ?>/exemplars/exemplars.php"
       class="nav-link <?= ($paginaActiva??'')==='exemplars' ? 'active':'' ?>"
       title="Exemplars">
      <i class="bi bi-upc-scan"></i><span class="nav-label"> Exemplars</span>
    </a>

    <div class="nav-section"><span class="nav-label">Préstecs</span></div>

    <a href="<?= BASE_URL ?>/prestecs/index.php"
       class="nav-link <?= ($paginaActiva??'')==='prestecs' ? 'active':'' ?>"
       title="Llista de préstecs">
      <i class="bi bi-arrow-right-circle-fill"></i><span class="nav-label"> Llista de préstecs</span>
    </a>

    <a href="<?= BASE_URL ?>/incidencies/index.php"
       class="nav-link <?= ($paginaActiva??'')==='incidencies' ? 'active':'' ?>"
       title="Incidències">
      <i class="bi bi-exclamation-triangle-fill"></i><span class="nav-label"> Incidències</span>
    </a>

    <?php if (Auth::rol() === 'admin'): ?>
    <div class="nav-section"><span class="nav-label">Administració</span></div>

    <a href="<?= BASE_URL ?>/usuaris.php"
       class="nav-link <?= ($paginaActiva??'')==='usuaris' ? 'active':'' ?>"
       title="Usuaris">
      <i class="bi bi-person-gear"></i><span class="nav-label"> Usuaris</span>
    </a>

    <a href="<?= BASE_URL ?>/alumnes/llista.php"
       class="nav-link <?= ($paginaActiva??'')==='alumnes' ? 'active':'' ?>"
       title="Alumnes">
      <i class="bi bi-person-lines-fill"></i><span class="nav-label"> Alumnes</span>
    </a>

    <a href="<?= BASE_URL ?>/admin/descarregar_contrasenyes.php"
       class="nav-link <?= ($paginaActiva??'')==='descarregar_contrasenyes' ? 'active':'' ?>"
       title="Contrasenyes CSV">
      <i class="bi bi-file-earmark-arrow-down"></i><span class="nav-label"> Contrasenyes CSV</span>
    </a>

    <div class="nav-section"><span class="nav-label">Importar dades</span></div>

    <a href="<?= BASE_URL ?>/materies/importar.php"
       class="nav-link <?= ($paginaActiva??'')==='import_materies' ? 'active':'' ?>"
       title="Importar Matèries">
      <i class="bi bi-upload"></i><span class="nav-label"> Matèries</span>
    </a>
    <a href="<?= BASE_URL ?>/professors/importar.php"
       class="nav-link <?= ($paginaActiva??'')==='import_professors' ? 'active':'' ?>"
       title="Importar Professorat">
      <i class="bi bi-upload"></i><span class="nav-label"> Professorat</span>
    </a>
    <a href="<?= BASE_URL ?>/alumnes/importar.php"
       class="nav-link <?= ($paginaActiva??'')==='import_alumnes' ? 'active':'' ?>"
       title="Importar Alumnes">
      <i class="bi bi-upload"></i><span class="nav-label"> Alumnes</span>
    </a>
    <a href="<?= BASE_URL ?>/classes/importar.php"
       class="nav-link <?= ($paginaActiva??'')==='import_classes' ? 'active':'' ?>"
       title="Importar Classes">
      <i class="bi bi-upload"></i><span class="nav-label"> Classes</span>
    </a>
    <a href="<?= BASE_URL ?>/llibres/importar.php"
       class="nav-link <?= ($paginaActiva??'')==='import_llibres' ? 'active':'' ?>"
       title="Importar Llibres">
      <i class="bi bi-upload"></i><span class="nav-label"> Llibres</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-name"><i class="bi bi-person-circle me-1"></i><span class="nav-label"><?= htmlspecialchars(Auth::nom()) ?></span></div>
    <?php if (Auth::rol()==='admin'): ?>
      <span class="badge-admin nav-label">Administrador</span>
    <?php else: ?>
      <span class="badge-prof nav-label">Professor/a</span>
    <?php endif; ?>

    <!-- Dark mode toggle -->
    <button class="dark-toggle-btn" id="darkToggleBtn" onclick="toggleDark()" title="<?= $_darkMode ? 'Mode clar' : 'Mode fosc' ?>">
      <i class="bi <?= $_darkMode ? 'bi-sun-fill' : 'bi-moon-stars-fill' ?>" id="darkToggleIcon"></i>
      <span class="nav-label" id="darkToggleTxt"><?= $_darkMode ? 'Mode clar' : 'Mode fosc' ?></span>
    </button>

    <a href="<?= BASE_URL ?>/logout.php" title="Tancar sessió">
      <i class="bi bi-box-arrow-left"></i><span class="nav-label"> Tancar sessió</span>
    </a>

    <!-- Sidebar collapse toggle -->
    <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" onclick="toggleSidebar()" title="Col·lapsar">
      <i class="bi bi-chevron-double-left" id="sidebarCollapseIcon"></i>
    </button>
  </div>

</aside>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeMobileSidebar()"></div>

<?php endif; ?>

<div class="main-wrap" id="mainWrap">

<?php if (Auth::check()): ?>
<div class="topbar">
  <button class="topbar-sidebar-btn" id="topbarSidebarBtn" onclick="toggleSidebar()" title="Barra lateral">
    <i class="bi bi-list"></i>
  </button>
  <h2><?= htmlspecialchars($titolPagina ?? '') ?></h2>
  <div class="topbar-actions">
    <?php if (!empty($accionsTopbar)) echo $accionsTopbar; ?>
  </div>
</div>
<?php endif; ?>

<div class="content">
