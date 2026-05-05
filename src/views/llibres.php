<?php include __DIR__ . '/layout_top.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2><i class="bi bi-journal-bookmark"></i> Llibres</h2>

  <a href="<?= BASE_URL ?>/llibres/nou.php" class="btn btn-success btn-sm">
    <i class="bi bi-plus"></i> Nou llibre
  </a>
</div>

<div class="table-responsive mt-3">
  <table class="table table-hover">
    ...

<div class="table-responsive mt-3">
  <table class="table table-hover">
    <thead>
      <tr>
        <th>Títol</th>
        <th>Matèria</th>
        <th>Curs</th>
        <th>Accions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($llibres as $l): ?>
      <tr>
        <td><?= htmlspecialchars($l['titol']) ?></td>
        <td><?= htmlspecialchars($l['materia_nom']) ?></td>
        <td><?= htmlspecialchars($l['curs_codi']) ?></td>
        <td>
          <a href="<?= BASE_URL ?>/exemplars/exemplars.php?llibre_id=<?= $l['id'] ?>"
             class="btn btn-sm btn-outline-primary">
            <i class="bi bi-collection"></i> Exemplars
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
