<?php include __DIR__ . '/layout_top.php'; ?>

<div class="card mb-4">
  <div class="card-header-bl">
    <i class="bi bi-box-seam"></i> Assignar lot — <?= htmlspecialchars($alumne['nom'] . ' ' . $alumne['cognoms']) ?>
  </div>
  <div class="card-body">
    <p class="text-muted mb-4">Classe: <strong><?= htmlspecialchars($alumne['classe_nom']) ?></strong></p>

    <form method="POST">

      <?php foreach ($materies as $m): ?>
        <div class="mb-3">
          <label class="form-label fw-semibold"><?= htmlspecialchars($m['nom']) ?></label>

          <?php if (empty($exemplars_disponibles[$m['id']])): ?>
            <div class="text-muted small">Sense exemplars disponibles</div>
          <?php else: ?>
            <select name="exemplar[<?= $m['id'] ?>]" class="form-select">
              <option value="">— No assignar —</option>
              <?php foreach ($exemplars_disponibles[$m['id']] as $ex): ?>
                <option value="<?= $ex['id'] ?>">
                  <?= htmlspecialchars($ex['codi'] . ' — ' . $ex['titol']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-box-seam"></i> Crear lot i assignar
        </button>
        <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne_id ?>" class="btn btn-secondary">
          <i class="bi bi-arrow-left"></i> Cancel·lar
        </a>
      </div>

    </form>
  </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
