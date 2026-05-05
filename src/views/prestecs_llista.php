<?php include __DIR__ . '/layout_top.php'; ?>

<h2><i class="bi bi-arrow-right-circle"></i> Préstecs</h2>

<div class="table-responsive mt-3">
  <table class="table table-hover table-sm">
    <thead>
      <tr>
        <th>Data</th>
        <th>Alumne</th>
        <th>Exemplar</th>
        <th>Títol</th>
        <th>Estat</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($prestecs as $p): ?>
      <tr>
        <td><?= date('d/m/Y', strtotime($p['data_prestec'])) ?></td>
        <td><?= htmlspecialchars($p['cognoms'] . ', ' . $p['nom']) ?></td>
        <td><?= htmlspecialchars($p['exemplar_codi']) ?></td>
        <td><?= htmlspecialchars($p['titol']) ?></td>
        <td><?= htmlspecialchars($p['estat_prestec']) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$prestecs): ?>
      <tr><td colspan="5" class="text-center text-muted">Cap préstec.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
