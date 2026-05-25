<?php include __DIR__ . '/layout_top.php'; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne_id ?>" class="btn btn-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Tornar a la fitxa
  </a>
</div>

<div class="card">
  <div class="card-header-bl">
    <i class="bi bi-plus-circle"></i>
    Afegir llibre d'optativa —
    <?= htmlspecialchars($alumne['nom'] . ' ' . $alumne['cognoms']) ?>
    <small class="ms-2 fw-normal opacity-75"><?= htmlspecialchars($alumne['classe_nom']) ?></small>
  </div>
  <div class="card-body">

    <?php if (!$exemplars): ?>
      <div class="alert alert-warning mb-0">
        <i class="bi bi-exclamation-triangle"></i>
        No hi ha exemplars d'optatives disponibles en aquest moment.
      </div>
    <?php else: ?>

      <form method="POST">
        <div class="mb-4">
          <label class="form-label">Selecciona l'exemplar d'optativa</label>
          <select name="exemplar_id" class="form-select" required>
            <option value="">— Selecciona —</option>
            <?php foreach ($exemplars as $ex): ?>
              <option value="<?= $ex['id'] ?>">
                <?= htmlspecialchars($ex['materia_nom'] . ' — ' . $ex['codi'] . ' — ' . $ex['titol']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Assignar optativa
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
