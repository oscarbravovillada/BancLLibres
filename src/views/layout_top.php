<?php // src/views/layout_top.php ?>
<!DOCTYPE html>
<html lang="ca">
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
<aside class="sidebar">

  <div class="sidebar-brand">
    <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo">
    <h1><i class="bi bi-book-half"></i> <?= APP_NAME ?></h1>
    <small><?= ANY_ESCOLAR ?></small>
  </div>

  <nav>
    <div class="nav-section">Principal</div>

    <a href="<?= BASE_URL ?>/index.php"
       class="nav-link <?= ($paginaActiva??'')==='inici' ? 'active':'' ?>">
      <i class="bi bi-house-fill"></i> Inici
    </a>

    <a href="<?= BASE_URL ?>/classes/classes.php"
       class="nav-link <?= ($paginaActiva??'')==='classes' ? 'active':'' ?>">
      <i class="bi bi-people-fill"></i> Classes
    </a>

    <div class="nav-section">Inventari</div>

    <a href="<?= BASE_URL ?>/materies/materies.php"
       class="nav-link <?= ($paginaActiva??'')==='materies' ? 'active':'' ?>">
      <i class="bi bi-tags-fill"></i> Matèries
    </a>

    <a href="<?= BASE_URL ?>/llibres/llibres.php"
       class="nav-link <?= ($paginaActiva??'')==='llibres' ? 'active':'' ?>">
      <i class="bi bi-journal-text"></i> Llibres
    </a>

    <a href="<?= BASE_URL ?>/exemplars/exemplars.php"
       class="nav-link <?= ($paginaActiva??'')==='exemplars' ? 'active':'' ?>">
      <i class="bi bi-upc-scan"></i> Exemplars
    </a>

    <div class="nav-section">Préstecs</div>

    <a href="<?= BASE_URL ?>/prestecs/index.php"
       class="nav-link <?= ($paginaActiva??'')==='prestecs' ? 'active':'' ?>">
      <i class="bi bi-arrow-right-circle-fill"></i> Llista de préstecs
    </a>

    <a href="<?= BASE_URL ?>/incidencies/index.php"
       class="nav-link <?= ($paginaActiva??'')==='incidencies' ? 'active':'' ?>">
      <i class="bi bi-exclamation-triangle-fill"></i> Incidències
    </a>

    <?php if (Auth::rol() === 'admin'): ?>
    <div class="nav-section">Administració</div>

    <a href="<?= BASE_URL ?>/usuaris.php"
       class="nav-link <?= ($paginaActiva??'')==='usuaris' ? 'active':'' ?>">
      <i class="bi bi-person-gear"></i> Usuaris
    </a>

    <a href="<?= BASE_URL ?>/alumnes/llista.php"
       class="nav-link <?= ($paginaActiva??'')==='alumnes' ? 'active':'' ?>">
      <i class="bi bi-person-lines-fill"></i> Alumnes
    </a>

    <a href="<?= BASE_URL ?>/admin/descarregar_contrasenyes.php"
       class="nav-link <?= ($paginaActiva??'')==='descarregar_contrasenyes' ? 'active':'' ?>">
      <i class="bi bi-file-earmark-arrow-down"></i> Contrasenyes CSV
    </a>

    <div class="nav-section">Importar dades</div>

    <a href="<?= BASE_URL ?>/materies/importar.php"
       class="nav-link <?= ($paginaActiva??'')==='import_materies' ? 'active':'' ?>">
      <i class="bi bi-upload"></i> Matèries
    </a>
    <a href="<?= BASE_URL ?>/professors/importar.php"
       class="nav-link <?= ($paginaActiva??'')==='import_professors' ? 'active':'' ?>">
      <i class="bi bi-upload"></i> Professorat
    </a>
    <a href="<?= BASE_URL ?>/alumnes/importar.php"
       class="nav-link <?= ($paginaActiva??'')==='import_alumnes' ? 'active':'' ?>">
      <i class="bi bi-upload"></i> Alumnes
    </a>
    <a href="<?= BASE_URL ?>/classes/importar.php"
       class="nav-link <?= ($paginaActiva??'')==='import_classes' ? 'active':'' ?>">
      <i class="bi bi-upload"></i> Classes
    </a>
    <a href="<?= BASE_URL ?>/llibres/importar.php"
       class="nav-link <?= ($paginaActiva??'')==='import_llibres' ? 'active':'' ?>">
      <i class="bi bi-upload"></i> Llibres
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-name"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars(Auth::nom()) ?></div>
    <?php if (Auth::rol()==='admin'): ?>
      <span class="badge-admin">Administrador</span>
    <?php else: ?>
      <span class="badge-prof">Professor/a</span>
    <?php endif; ?>
    <br>
    <a href="<?= BASE_URL ?>/logout.php">
      <i class="bi bi-box-arrow-left"></i> Tancar sessió
    </a>
  </div>

</aside>
<?php endif; ?>

<div class="main-wrap">

<?php if (Auth::check()): ?>
<div class="topbar">
  <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo">
  <h2><?= htmlspecialchars($titolPagina ?? '') ?></h2>
  <div class="topbar-actions">
    <?php if (!empty($accionsTopbar)) echo $accionsTopbar; ?>
  </div>
</div>
<?php endif; ?>

<div class="content">
