<?php include __DIR__ . '/layout_top.php'; ?>

<div class="row mb-3">
  <div class="col">
    <h2><i class="bi bi-people"></i> Seleccionar classe i alumne</h2>
  </div>
</div>

<div class="row g-4">

  <!-- Llista de classes -->
  <div class="col-md-3">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <i class="bi bi-grid"></i> Classes
      </div>

      <div class="list-group list-group-flush">
        <?php foreach ($classes as $c): ?>
          <a href="?classe_id=<?= $c['id'] ?>"
             class="list-group-item list-group-item-action <?= $classeSeleccionada == $c['id'] ? 'active' : '' ?>">
            <strong><?= htmlspecialchars($c['nom']) ?></strong>
            <br><small><?= htmlspecialchars($c['curs_nom']) ?></small>
          </a>
        <?php endforeach; ?>

        <?php if (!$classes): ?>
          <p class="p-3 text-muted small">No teniu classes assignades.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Llista d'alumnes -->
  <div class="col-md-9">

    <?php if ($classeActual): ?>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="bi bi-people-fill"></i> <?= htmlspecialchars($classeActual['nom']) ?></h4>

        <?php if ($usuari_rol === 'admin'): ?>
          <a href="<?= BASE_URL ?>/alumne_nou.php?classe_id=<?= $classeSeleccionada ?>"
             class="btn btn-success btn-sm">
            <i class="bi bi-plus"></i> Nou alumne
          </a>
        <?php endif; ?>
      </div>

      <?php if ($alumnes): ?>
      <div class="row g-3">
        <?php foreach ($alumnes as $a): ?>
          <div class="col-sm-6 col-lg-4">
            <div class="card card-alumne h-100 shadow-sm">

              <div class="card-body">
                <h6 class="card-title mb-1">
                  <i class="bi bi-person"></i>
                  <?= htmlspecialchars($a['cognoms'] . ', ' . $a['nom']) ?>
                </h6>

                <p class="small text-muted mb-2">
                  <i class="bi bi-envelope"></i>
                  <?= htmlspecialchars($a['email_familia'] ?: '—') ?>
                </p>

                <?php if ($a['llibres_actius'] > 0): ?>
                  <span class="badge bg-primary"><?= $a['llibres_actius'] ?> llibres actius</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Sense llibres</span>
                <?php endif; ?>
              </div>

              <div class="card-footer bg-transparent">
                <a href="<?= BASE_URL ?>/alumnes/fitxa.php?id=<?= $a['id'] ?>"
                   class="btn btn-primary btn-sm w-100">
                  <i class="bi bi-person-lines-fill"></i> Obrir fitxa
                </a>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php else: ?>
        <div class="alert alert-info">Cap alumne en aquesta classe.</div>
      <?php endif; ?>

    <?php else: ?>
      <div class="alert alert-secondary">
        <i class="bi bi-arrow-left"></i> Seleccioneu una classe per veure els alumnes.
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
