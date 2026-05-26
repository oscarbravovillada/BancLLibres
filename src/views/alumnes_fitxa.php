<?php include __DIR__ . '/layout_top.php'; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/alumnes/llista.php" class="btn btn-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Tornar a la llista
  </a>
</div>

<!-- Dades personals -->
<div class="card mb-4">
  <div class="card-header-bl">
    <i class="bi bi-person-badge"></i>
    <?= htmlspecialchars($alumne['cognoms'] . ', ' . $alumne['nom']) ?>
  </div>
  <div class="card-body">
    <div class="row g-3 mb-4">
      <div class="col-sm-4">
        <span class="text-muted d-block" style="font-size:.85rem">Classe</span>
        <strong><?= htmlspecialchars($alumne['classe_nom'] ?: '—') ?></strong>
      </div>
      <div class="col-sm-4">
        <span class="text-muted d-block" style="font-size:.85rem">Tutor/a</span>
        <strong><?= htmlspecialchars($alumne['tutor_nom'] ?: '—') ?></strong>
      </div>
      <div class="col-sm-4">
        <span class="text-muted d-block" style="font-size:.85rem">Email família</span>
        <strong><?= htmlspecialchars($alumne['email_familia'] ?: '—') ?></strong>
      </div>
      <?php if (!empty($alumne['telefon_familia'])): ?>
      <div class="col-sm-4">
        <span class="text-muted d-block" style="font-size:.85rem">Telèfon família</span>
        <strong><?= htmlspecialchars($alumne['telefon_familia']) ?></strong>
      </div>
      <?php endif; ?>
      <?php if (!empty($lot)): ?>
      <div class="col-sm-4">
        <span class="text-muted d-block" style="font-size:.85rem">Codi lot</span>
        <span class="codi-exemplar"><?= htmlspecialchars($lot['codi']) ?></span>
      </div>
      <?php endif; ?>
    </div>

    <!-- Accions -->
    <div class="d-flex flex-wrap gap-2">
      <?php if (!$lot): ?>
      <a href="<?= BASE_URL ?>/prestecs/assignar_lot.php?alumne_id=<?= $alumne['id'] ?>" class="btn btn-primary">
        <i class="bi bi-box-seam"></i> Assignar lot
      </a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne['id'] ?>" class="btn btn-outline-primary">
        <i class="bi bi-eye"></i> Veure préstecs
      </a>
      <a href="<?= BASE_URL ?>/prestecs/afegir_optativa.php?alumne_id=<?= $alumne['id'] ?>" class="btn btn-outline-secondary">
        <i class="bi bi-plus-circle"></i> Afegir llibre d'optativa
      </a>
      <a href="<?= BASE_URL ?>/prestecs/devolucio.php?alumne_id=<?= $alumne['id'] ?>" class="btn btn-success">
        <i class="bi bi-arrow-return-left"></i> Registrar devolució
      </a>
      <a href="<?= BASE_URL ?>/prestecs/incidencia.php?alumne_id=<?= $alumne['id'] ?>" class="btn btn-warning">
        <i class="bi bi-exclamation-triangle"></i> Registrar incidència
      </a>
      <a href="<?= BASE_URL ?>/prestecs/reenviar.php?alumne_id=<?= $alumne['id'] ?>" class="btn btn-outline-info">
        <i class="bi bi-envelope"></i> Reenviar document
      </a>
      <a href="<?= BASE_URL ?>/prestecs/historial.php?alumne_id=<?= $alumne['id'] ?>" class="btn btn-outline-dark">
        <i class="bi bi-clock-history"></i> Historial
      </a>
    </div>
  </div>
</div>

<!-- Préstecs -->
<div class="card mb-4">
  <div class="card-header-bl"><i class="bi bi-arrow-right-circle-fill"></i> Préstecs</div>
  <div class="table-responsive">
    <table class="table table-bl mb-0">
      <thead>
        <tr>
          <th>Data</th>
          <th>Exemplar</th>
          <th>Títol</th>
          <th>Matèria</th>
          <th>Estat</th>
          <th>Retorn</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($prestecs as $p): ?>
        <tr>
          <td><?= date('d/m/Y', strtotime($p['data_prestec'])) ?></td>
          <td><span class="codi-exemplar"><?= htmlspecialchars($p['exemplar_codi']) ?></span></td>
          <td><?= htmlspecialchars(mb_substr($p['titol'], 0, 45)) ?></td>
          <td><?= htmlspecialchars($p['materia_nom']) ?></td>
          <td><span class="badge badge-estat-<?= $p['estat_prestec'] ?>"><?= ucfirst($p['estat_prestec']) ?></span></td>
          <td><?= $p['data_devolucio'] ? date('d/m/Y', strtotime($p['data_devolucio'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$prestecs): ?>
        <tr><td colspan="6" class="text-center text-muted py-3">Cap préstec registrat.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Incidències -->
<div class="card">
  <div class="card-header-bl"><i class="bi bi-exclamation-triangle-fill"></i> Incidències</div>
  <div class="table-responsive">
    <table class="table table-bl mb-0">
      <thead>
        <tr>
          <th>Data</th>
          <th>Exemplar</th>
          <th>Títol</th>
          <th>Tipus</th>
          <th>Descripció</th>
          <th>Pagament</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($incidencies as $i): ?>
        <tr>
          <td><?= date('d/m/Y', strtotime($i['data_incidencia'])) ?></td>
          <td><span class="codi-exemplar"><?= htmlspecialchars($i['exemplar_codi']) ?></span></td>
          <td><?= htmlspecialchars(mb_substr($i['titol'], 0, 40)) ?></td>
          <td>
            <?php
            $etiquetes = ['perdua'=>'Pèrdua','deteriorament_greu'=>'Deteriorament','extraviu'=>'Extraviu','altre'=>'Altre'];
            echo htmlspecialchars($etiquetes[$i['tipus']] ?? $i['tipus']);
            ?>
          </td>
          <td><?= htmlspecialchars(mb_substr($i['descripcio'], 0, 50)) ?></td>
          <td>
            <?php if (!$i['ha_de_pagar']): ?>
              <span class="text-muted">—</span>
            <?php elseif ($i['pagat']): ?>
              <span class="badge badge-estat-bo">Pagat</span>
            <?php else: ?>
              <span class="badge badge-estat-pendent">
                Pendent <?= $i['import_pagament'] ? number_format($i['import_pagament'],2).' €' : '' ?>
              </span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$incidencies): ?>
        <tr><td colspan="6" class="text-center text-muted py-3">Cap incidència registrada.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
