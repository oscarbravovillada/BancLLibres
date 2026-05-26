<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = "Fitxa de l'alumne";
$paginaActiva = "prestecs";

$alumne_id = (int)($_GET['id'] ?? 0);
if (!$alumne_id) {
    header("Location: /BancLLibres/index.php");
    exit;
}

/* ============================================================
   1) DADES DE L'ALUMNE
   ============================================================ */
$alumne = Database::fetchOne(
    "SELECT a.*, c.nom AS classe_nom, CONCAT(u.nom,' ',u.cognoms) AS tutor_nom
     FROM alumnes a
     JOIN classes c ON a.classe_id = c.id
     LEFT JOIN usuaris u ON c.tutor_id = u.id
     WHERE a.id = ?",
    [$alumne_id]
);

if (!$alumne) {
    die("Alumne no trobat.");
}

Auth::requireAccessToAlumne($alumne_id);

/* ============================================================
   2) LOT ASSIGNAT
   ============================================================ */
$lot = Database::fetchOne(
    "SELECT * FROM lots WHERE alumne_id = ?",
    [$alumne_id]
);

/* ============================================================
   3) EXEMPLARS DEL LOT
   ============================================================ */
$exemplars_lot = [];
if ($lot) {
    $exemplars_lot = Database::fetchAll(
        "SELECT e.*, l.titol, m.nom AS materia_nom
         FROM exemplars e
         JOIN llibres l ON e.llibre_id = l.id
         JOIN materies m ON l.materia_id = m.id
         WHERE e.lot_id = ?
         ORDER BY m.nom",
        [$lot['id']]
    );
}

/* ============================================================
   4) EXEMPLARS INDIVIDUALS (OPTATIVES)
   ============================================================ */
$optatives = Database::fetchAll(
    "SELECT e.*, l.titol, m.nom AS materia_nom
     FROM exemplars e
     JOIN llibres l ON e.llibre_id = l.id
     JOIN materies m ON l.materia_id = m.id
     WHERE e.alumne_id = ? AND e.lot_id IS NULL
     ORDER BY m.nom",
    [$alumne_id]
);

/* ============================================================
   5) CARREGAR LA VISTA
   ============================================================ */
include __DIR__ . '/../src/views/layout_top.php'; ?>

<!-- Dades alumne -->
<div class="card mb-4">
  <div class="card-header-bl">
    <i class="bi bi-person-badge"></i>
    <?= htmlspecialchars($alumne['nom'] . ' ' . $alumne['cognoms']) ?>
    <small class="ms-2 fw-normal opacity-75">
      <?= htmlspecialchars($alumne['classe_nom']) ?> — Tutor/a: <?= htmlspecialchars($alumne['tutor_nom'] ?? '—') ?>
    </small>
  </div>
  <div class="card-body">
    <div class="d-flex flex-wrap gap-2">

      <?php if (!$lot): ?>
        <a href="<?= BASE_URL ?>/prestecs/assignar_lot.php?alumne_id=<?= $alumne_id ?>" class="btn btn-primary">
          <i class="bi bi-box-seam"></i> Assignar lot
        </a>
      <?php endif; ?>

      <a href="<?= BASE_URL ?>/prestecs/afegir_optativa.php?alumne_id=<?= $alumne_id ?>" class="btn btn-outline-secondary">
        <i class="bi bi-plus-circle"></i> Afegir llibre d'optativa
      </a>

      <a href="<?= BASE_URL ?>/prestecs/devolucio.php?alumne_id=<?= $alumne_id ?>" class="btn btn-success">
        <i class="bi bi-arrow-return-left"></i> Registrar devolució
      </a>

      <a href="<?= BASE_URL ?>/prestecs/incidencia.php?alumne_id=<?= $alumne_id ?>" class="btn btn-warning">
        <i class="bi bi-exclamation-triangle"></i> Registrar incidència
      </a>

      <a href="<?= BASE_URL ?>/prestecs/reenviar.php?alumne_id=<?= $alumne_id ?>" class="btn btn-outline-info">
        <i class="bi bi-envelope"></i> Reenviar document
      </a>

      <a href="<?= BASE_URL ?>/prestecs/historial.php?alumne_id=<?= $alumne_id ?>" class="btn btn-outline-dark">
        <i class="bi bi-clock-history"></i> Historial
      </a>

    </div>
  </div>
</div>

<!-- Lot assignat -->
<div class="card mb-4">
  <div class="card-header-bl"><i class="bi bi-box-seam"></i> Lot assignat</div>
  <div class="card-body">
    <?php if (!$lot): ?>
      <div class="alert alert-warning mb-0">Aquest alumne/a encara no té lot assignat.</div>
    <?php else: ?>
      <p class="mb-3">Codi del lot: <strong class="codi-exemplar"><?= htmlspecialchars($lot['codi']) ?></strong></p>
      <div class="table-responsive">
        <table class="table table-bl mb-0">
          <thead>
            <tr><th>Codi</th><th>Títol</th><th>Matèria</th><th>Estat</th></tr>
          </thead>
          <tbody>
            <?php foreach ($exemplars_lot as $ex): ?>
            <tr>
              <td><span class="codi-exemplar"><?= htmlspecialchars($ex['codi']) ?></span></td>
              <td><?= htmlspecialchars($ex['titol']) ?></td>
              <td><?= htmlspecialchars($ex['materia_nom']) ?></td>
              <td><span class="badge badge-estat-<?= $ex['estat'] ?>"><?= ucfirst($ex['estat']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$exemplars_lot): ?>
            <tr><td colspan="4" class="text-muted text-center py-2">Sense exemplars en el lot.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Optatives -->
<div class="card">
  <div class="card-header-bl"><i class="bi bi-journal-plus"></i> Llibres d'optatives</div>
  <div class="card-body">
    <?php if (!$optatives): ?>
      <div class="alert alert-info mb-0">Cap llibre d'optativa assignat.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-bl mb-0">
          <thead>
            <tr><th>Codi</th><th>Títol</th><th>Matèria</th><th>Estat</th></tr>
          </thead>
          <tbody>
            <?php foreach ($optatives as $ex): ?>
            <tr>
              <td><span class="codi-exemplar"><?= htmlspecialchars($ex['codi']) ?></span></td>
              <td><?= htmlspecialchars($ex['titol']) ?></td>
              <td><?= htmlspecialchars($ex['materia_nom']) ?></td>
              <td><span class="badge badge-estat-<?= $ex['estat'] ?>"><?= ucfirst($ex['estat']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
