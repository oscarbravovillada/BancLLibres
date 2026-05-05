<?php include __DIR__ . '/layout_top.php'; ?>

<h2><i class="bi bi-exclamation-triangle"></i> Incidències</h2>

<div class="table-responsive mt-3">
  <table class="table table-hover table-sm">
    <thead>
      <tr>
        <th>Data</th>
        <th>Alumne</th>
        <th>Exemplar</th>
        <th>Títol</th>
        <th>Descripció</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($incidencies as $i): ?>
      <tr>
        <td><?= date('d/m/Y', strtotime($i['data_incidencia'])) ?></td>
        <td><?= htmlspecialchars($i['cognoms'] . ', ' . $i['nom']) ?></td>
        <td><?= htmlspecialchars($i['exemplar_codi']) ?></td>
        <td><?= htmlspecialchars($i['titol']) ?></td>
        <td><?= htmlspecialchars($i['descripcio'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$incidencies): ?>
      <tr><td colspan="5" class="text-center text-muted">Cap incidència.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
