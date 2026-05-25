<?php include __DIR__ . '/layout_top.php'; ?>

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

<?php include __DIR__ . '/layout_bottom.php'; ?>
