<?php include __DIR__ . '/layout_top.php'; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne_id ?>" class="btn btn-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Tornar a la fitxa
  </a>
</div>

<?php if ($missatge): ?>
<div class="alert alert-success"><?= htmlspecialchars($missatge) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header-bl">
    <i class="bi bi-envelope"></i>
    Reenviar documents —
    <?= htmlspecialchars($alumne['nom'] . ' ' . $alumne['cognoms']) ?>
    <small class="ms-2 fw-normal opacity-75"><?= htmlspecialchars($alumne['classe_nom']) ?></small>
  </div>
  <div class="card-body">

    <?php if (empty($alumne['email_familia'])): ?>
      <div class="alert alert-warning mb-0">
        <i class="bi bi-exclamation-triangle"></i>
        Aquest alumne no té correu de família configurat. No es poden enviar documents per correu.
      </div>
    <?php else: ?>
      <p class="text-muted mb-3">
        <i class="bi bi-envelope-at"></i>
        Els documents s'enviaran a: <strong><?= htmlspecialchars($alumne['email_familia']) ?></strong>
      </p>
    <?php endif; ?>

    <?php if (!$albarans): ?>
      <div class="alert alert-info mb-0">
        <i class="bi bi-info-circle"></i>
        No hi ha cap document generat per a aquest alumne.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-bl mb-0">
          <thead>
            <tr>
              <th>Data</th>
              <th>Tipus</th>
              <th>Fitxer</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($albarans as $a): ?>
            <tr>
              <td><?= date('d/m/Y H:i', strtotime($a['data'])) ?></td>
              <td>
                <?php
                $etiquetes = ['prestec'=>'Albarà préstec','devolucio'=>'Albarà devolució','incidencia'=>"Albarà incidència"];
                echo htmlspecialchars($etiquetes[$a['tipus']] ?? ucfirst($a['tipus']));
                ?>
              </td>
              <td>
                <?php if ($a['fitxer_pdf'] && file_exists(PDF_DIR . basename($a['fitxer_pdf']))): ?>
                  <span class="codi-exemplar"><?= htmlspecialchars(basename($a['fitxer_pdf'])) ?></span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?php if (!empty($alumne['email_familia'])): ?>
                <a href="?alumne_id=<?= $alumne_id ?>&reenviar=<?= $a['id'] ?>"
                   class="btn btn-sm btn-outline-info"
                   onclick="return confirm('Reenviar el document a <?= htmlspecialchars($alumne['email_familia'], ENT_QUOTES) ?>?')">
                  <i class="bi bi-envelope-arrow-up"></i> Reenviar
                </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
