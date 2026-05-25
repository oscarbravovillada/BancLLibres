<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();
if (Auth::rol() !== 'admin') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$titolPagina  = 'Descarregar contrasenyes';
$paginaActiva = 'descarregar_contrasenyes';

$dir   = __DIR__ . '/../private/exports/';
$files = glob($dir . '*.csv') ?: [];
sort($files);

include __DIR__ . '/../src/views/layout_top.php';
?>

<div class="card" style="max-width:680px;margin:0 auto">
  <div class="card-header-bl">
    <i class="bi bi-file-earmark-arrow-down"></i> Descarregar contrasenyes
  </div>
  <div class="card-body">

    <?php if (empty($files)): ?>
      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Encara no s'ha generat cap fitxer de contrasenyes.
        Importa alumnes per crear-los automàticament.
      </div>
    <?php else: ?>
      <p class="text-muted mb-3" style="font-size:.9rem">
        Selecciona un fitxer per descarregar-lo:
      </p>

      <?php foreach ($files as $file): ?>
      <div class="d-flex align-items-center justify-content-between
                  px-3 py-2 mb-2 rounded-3"
           style="background:var(--bl-codi-bg)">
        <div>
          <i class="bi bi-file-earmark-spreadsheet me-2"
             style="color:#2e7d32"></i>
          <strong style="font-family:'IBM Plex Mono',monospace;font-size:.9rem">
            <?= htmlspecialchars(basename($file)) ?>
          </strong>
        </div>
        <a class="btn btn-sm btn-primary"
           href="<?= BASE_URL ?>/admin/download.php?file=<?= urlencode(basename($file)) ?>">
          <i class="bi bi-download"></i> Descarregar
        </a>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>/index.php" class="btn btn-secondary mt-3">
      <i class="bi bi-arrow-left"></i> Tornar
    </a>

  </div>
</div>

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
