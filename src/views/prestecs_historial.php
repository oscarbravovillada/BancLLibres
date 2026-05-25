<?php include __DIR__ . '/layout_top.php'; ?>

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

<?php include __DIR__ . '/layout_bottom.php'; ?>
