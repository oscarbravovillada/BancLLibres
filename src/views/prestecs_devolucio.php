<?php include __DIR__ . '/layout_top.php'; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne_id ?>" class="btn btn-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Tornar a la fitxa
  </a>
</div>

<div class="card">
  <div class="card-header-bl">
    <i class="bi bi-arrow-return-left"></i>
    Registrar devolució —
    <?= htmlspecialchars($alumne['nom'] . ' ' . $alumne['cognoms']) ?>
    <small class="ms-2 fw-normal opacity-75"><?= htmlspecialchars($alumne['classe_nom']) ?></small>
  </div>
  <div class="card-body">

    <?php if (!$exemplars): ?>
      <div class="alert alert-info mb-0">Aquest alumne/a no té exemplars assignats.</div>
    <?php else: ?>

      <p class="text-muted mb-4">
        Indica l'estat de cada exemplar en el moment de la devolució.
        <strong>Retornat</strong>: alliberat del préstec. &nbsp;
        <strong>Perdut</strong>: es genera incidència automàtica. &nbsp;
        <strong>Pendent de retorn</strong>: queda assignat a l'alumne/a per a una devolució futura.
      </p>

      <form method="POST">
        <div class="table-responsive">
          <table class="table table-bl mb-0">
            <thead>
              <tr>
                <th>Codi</th>
                <th>Títol</th>
                <th>Matèria</th>
                <th>Estat actual</th>
                <th>Acció</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($exemplars as $ex): ?>
              <tr>
                <td><span class="codi-exemplar"><?= htmlspecialchars($ex['codi']) ?></span></td>
                <td><?= htmlspecialchars($ex['titol']) ?></td>
                <td><?= htmlspecialchars($ex['materia_nom']) ?></td>
                <td><span class="badge badge-estat-<?= $ex['estat'] ?>"><?= ucfirst($ex['estat']) ?></span></td>
                <td style="min-width:180px">
                  <select name="estat[<?= $ex['id'] ?>]" class="form-select form-select-sm">
                    <option value="pendent">Pendent de retorn</option>
                    <option value="retornat">Retornat</option>
                    <option value="perdut">Perdut</option>
                  </select>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2 mt-4">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-arrow-return-left"></i> Confirmar devolució
          </button>
          <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne_id ?>" class="btn btn-secondary">
            <i class="bi bi-x-lg"></i> Cancel·lar
          </a>
        </div>
      </form>

    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
