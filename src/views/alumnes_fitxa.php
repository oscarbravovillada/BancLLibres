<?php include __DIR__ . '/layout_top.php'; ?>

<h2><i class="bi bi-person-lines-fill"></i> Fitxa alumne</h2>

<div class="card mt-3 mb-3">
  <div class="card-body">
    <h4><?= htmlspecialchars($alumne['cognoms'] . ', ' . $alumne['nom']) ?></h4>
    <div class="mt-3">

    <!-- Botó PDF préstec -->
    <a class="btn btn-primary"
       href="<?= BASE_URL ?>/admin/generar_albara.php?tipus=prestec&alumne=<?= $alumne['id'] ?>">
        <i class="bi bi-file-earmark-pdf"></i> Generar albarà de préstec
    </a>

    <!-- Botó PDF devolució -->
    <a class="btn btn-warning"
       href="<?= BASE_URL ?>/admin/generar_albara.php?tipus=devolucio&alumne=<?= $alumne['id'] ?>">
        <i class="bi bi-file-earmark-arrow-down"></i> Generar albarà de devolució
    </a>

    <!-- Botó PDF incidència -->
    <a class="btn btn-danger"
       href="<?= BASE_URL ?>/admin/generar_albara.php?tipus=incidencia&alumne=<?= $alumne['id'] ?>">
        <i class="bi bi-exclamation-triangle"></i> Generar albarà d'incidència
    </a>

</div>

    <p class="mb-1"><strong>Classe:</strong> <?= htmlspecialchars($alumne['classe_nom'] ?: '—') ?></p>
    <p class="mb-1"><strong>Email família:</strong> <?= htmlspecialchars($alumne['email_familia'] ?: '—') ?></p>
  </div>
</div>

<h5>Préstecs</h5>
<div class="table-responsive">
  <table class="table table-sm">
    <thead>
      <tr>
        <th>Data</th>
        <th>Exemplar</th>
        <th>Títol</th>
        <th>Estat</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($prestecs as $p): ?>
      <tr>
        <td><?= date('d/m/Y', strtotime($p['data_prestec'])) ?></td>
        <td><?= htmlspecialchars($p['exemplar_codi']) ?></td>
        <td><?= htmlspecialchars($p['titol']) ?></td>
        <td><?= htmlspecialchars($p['estat_prestec']) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$prestecs): ?>
      <tr><td colspan="4" class="text-muted text-center">Cap préstec registrat.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
