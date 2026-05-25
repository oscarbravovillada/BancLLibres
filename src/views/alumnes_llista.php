<?php include __DIR__ . '/layout_top.php'; ?>

<div class="row g-4">

  <!-- Panel esquerra: llista de classes -->
  <div class="col-md-3">
    <div class="card">
      <div class="card-header-bl"><i class="bi bi-grid-fill"></i> Classes</div>
      <div class="list-group list-group-flush">
        <?php foreach ($classes as $c): ?>
          <a href="?classe_id=<?= $c['id'] ?>"
             class="list-group-item list-group-item-action py-3 <?= $classeSeleccionada == $c['id'] ? 'active' : '' ?>"
             style="<?= $classeSeleccionada == $c['id'] ? 'background:#1a237e;color:#fff;border-color:#1a237e' : '' ?>">
            <strong><?= htmlspecialchars($c['nom']) ?></strong>
            <br>
            <small style="opacity:.8"><?= htmlspecialchars($c['curs_nom']) ?></small>
          </a>
        <?php endforeach; ?>
        <?php if (!$classes): ?>
          <p class="p-3 text-muted small mb-0">No teniu classes assignades.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Panel dret: alumnes -->
  <div class="col-md-9">

    <?php if (!$classeSeleccionada): ?>
      <div class="alert alert-info">
        <i class="bi bi-arrow-left"></i> Seleccioneu una classe del panell esquerre per veure els alumnes.
      </div>

    <?php elseif (!$classeActual): ?>
      <div class="alert alert-danger">Classe no trobada.</div>

    <?php else: ?>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">
          <i class="bi bi-people-fill"></i> <?= htmlspecialchars($classeActual['nom']) ?>
          <small class="text-muted fw-normal" style="font-size:.85rem">
            (<?= count($alumnes) ?> alumnes)
          </small>
        </h4>
        <?php if ($usuari_rol === 'admin'): ?>
          <a href="<?= BASE_URL ?>/alumnes/nou.php?classe_id=<?= $classeSeleccionada ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus"></i> Nou alumne
          </a>
        <?php endif; ?>
      </div>

      <?php if (!$alumnes): ?>
        <div class="alert alert-info">Cap alumne en aquesta classe.</div>
      <?php else: ?>

        <div class="row g-3">
          <?php foreach ($alumnes as $a): ?>
          <div class="col-sm-6 col-xl-4">
            <div class="card h-100" style="border-left:4px solid <?= $a['pagaments_pendents'] > 0 ? '#c62828' : ($a['llibres_actius'] > 0 ? '#1565c0' : '#9e9e9e') ?>">
              <div class="card-body pb-2">

                <div class="fw-bold mb-1" style="font-size:1rem">
                  <?= htmlspecialchars($a['cognoms'] . ', ' . $a['nom']) ?>
                </div>

                <div class="text-muted mb-3" style="font-size:.85rem">
                  <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($a['email_familia'] ?: '—') ?>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                  <?php if ($a['llibres_actius'] > 0): ?>
                    <span class="badge badge-estat-actiu">
                      <i class="bi bi-book"></i> <?= $a['llibres_actius'] ?> llibre<?= $a['llibres_actius'] > 1 ? 's' : '' ?>
                    </span>
                  <?php else: ?>
                    <span class="badge" style="background:#9e9e9e;color:#fff">Sense llibres</span>
                  <?php endif; ?>

                  <?php if ($a['pagaments_pendents'] > 0): ?>
                    <span class="badge badge-estat-perdut">
                      <i class="bi bi-exclamation-circle"></i> <?= $a['pagaments_pendents'] ?> pendent<?= $a['pagaments_pendents'] > 1 ? 's' : '' ?>
                    </span>
                  <?php endif; ?>
                </div>

              </div>
              <div class="card-footer bg-transparent pt-2 pb-3 d-flex gap-2">
                <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $a['id'] ?>"
                   class="btn btn-primary btn-sm flex-fill">
                  <i class="bi bi-arrow-right-circle"></i> Préstecs
                </a>
                <a href="<?= BASE_URL ?>/alumnes/fitxa.php?id=<?= $a['id'] ?>"
                   class="btn btn-outline-secondary btn-sm">
                  <i class="bi bi-person-lines-fill"></i>
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
