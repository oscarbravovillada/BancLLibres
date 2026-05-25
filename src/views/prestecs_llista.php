<?php include __DIR__ . '/layout_top.php'; ?>

<div class="card">
  <div class="card-header-bl"><i class="bi bi-arrow-right-circle-fill"></i> Llista de préstecs</div>
  <div class="table-responsive">
    <table class="table table-bl mb-0">
      <thead>
        <tr>
          <th>Data</th>
          <th>Alumne/a</th>
          <th>Exemplar</th>
          <th>Títol</th>
          <th>Estat</th>
          <th class="text-end">Accions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($prestecs as $p): ?>
        <tr>
          <td><?= date('d/m/Y', strtotime($p['data_prestec'])) ?></td>
          <td>
            <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $p['alumne_id'] ?>">
              <?= htmlspecialchars($p['cognoms'] . ', ' . $p['nom']) ?>
            </a>
          </td>
          <td><span class="codi-exemplar"><?= htmlspecialchars($p['exemplar_codi']) ?></span></td>
          <td><?= htmlspecialchars(mb_substr($p['titol'], 0, 45)) ?></td>
          <td><span class="badge badge-estat-<?= $p['estat_prestec'] ?>"><?= ucfirst($p['estat_prestec']) ?></span></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" title="Fitxa alumne"
               href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $p['alumne_id'] ?>">
              <i class="bi bi-person"></i>
            </a>
            <a class="btn btn-sm btn-success" title="Registrar devolució"
               href="<?= BASE_URL ?>/prestecs/devolucio.php?alumne_id=<?= $p['alumne_id'] ?>">
              <i class="bi bi-arrow-return-left"></i>
            </a>
            <a class="btn btn-sm btn-warning" title="Registrar incidència"
               href="<?= BASE_URL ?>/prestecs/incidencia.php?alumne_id=<?= $p['alumne_id'] ?>">
              <i class="bi bi-exclamation-triangle"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$prestecs): ?>
        <tr><td colspan="6" class="text-center text-muted py-3">Cap préstec registrat.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
