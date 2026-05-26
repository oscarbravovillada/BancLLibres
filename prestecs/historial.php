<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Auth.php';
require_once __DIR__ . '/../src/helpers/Database.php';

Auth::requireLogin();

$titolPagina  = 'Historial';
$paginaActiva = 'prestecs';

$alumne_id = (int)($_GET['alumne_id'] ?? 0);
if (!$alumne_id) {
    header('Location: ' . BASE_URL . '/prestecs/index.php');
    exit;
}

Auth::requireAccessToAlumne($alumne_id);

$alumne = Database::fetchOne(
    "SELECT a.*, c.nom AS classe_nom FROM alumnes a JOIN classes c ON a.classe_id = c.id WHERE a.id = ?",
    [$alumne_id]
);
if (!$alumne) die("Alumne no trobat");

$historial = Database::fetchAll(
    "SELECT h.id, h.accio, h.detalls, h.creat_at,
            e.codi AS exemplar_codi
     FROM historial h
     LEFT JOIN exemplars e ON e.id = h.exemplar_id
     WHERE h.alumne_id = ?
     ORDER BY h.creat_at DESC",
    [$alumne_id]
);

include __DIR__ . '/../src/views/layout_top.php'; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne_id ?>" class="btn btn-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Tornar a la fitxa
  </a>
</div>

<div class="card">
  <div class="card-header-bl">
    <i class="bi bi-clock-history"></i>
    Historial — <?= htmlspecialchars($alumne['nom'] . ' ' . $alumne['cognoms']) ?>
    <small class="ms-2 fw-normal opacity-75"><?= htmlspecialchars($alumne['classe_nom']) ?></small>
  </div>
  <div class="table-responsive">
    <table class="table table-bl mb-0">
      <thead>
        <tr>
          <th>Data i hora</th>
          <th>Acció</th>
          <th>Exemplar</th>
          <th>Detalls</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($historial as $h): ?>
        <tr>
          <td><?= date('d/m/Y H:i', strtotime($h['creat_at'])) ?></td>
          <td><?= htmlspecialchars($h['accio']) ?></td>
          <td>
            <?php if ($h['exemplar_codi']): ?>
              <span class="codi-exemplar"><?= htmlspecialchars($h['exemplar_codi']) ?></span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($h['detalls'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$historial): ?>
        <tr><td colspan="4" class="text-center text-muted py-3">Cap registre en l'historial.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
