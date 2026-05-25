<?php include __DIR__ . '/layout_top.php'; ?>

<div class="card mb-4">
  <div class="card-header-bl">
    <i class="bi bi-exclamation-triangle"></i> Registrar incidència
  </div>
  <div class="card-body">

    <p class="mb-3">
      <strong><?= htmlspecialchars($alumne['nom'] . ' ' . $alumne['cognoms']) ?></strong>
      — <?= htmlspecialchars($alumne['classe_nom']) ?>
    </p>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?>
          <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$exemplars): ?>
      <div class="alert alert-info">Aquest alumne no té exemplars assignats.</div>
      <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne_id ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Tornar
      </a>
    <?php else: ?>

    <form method="POST">

      <div class="mb-3">
        <label class="form-label fw-semibold">Exemplar afectat</label>
        <select name="exemplar_id" class="form-select" required>
          <option value="">— Selecciona un exemplar —</option>
          <?php foreach ($exemplars as $ex): ?>
            <option value="<?= $ex['id'] ?>" <?= (($_POST['exemplar_id'] ?? '') == $ex['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($ex['codi'] . ' — ' . $ex['titol'] . ' (' . $ex['materia_nom'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Tipus d'incidència</label>
        <select name="tipus" class="form-select" required>
          <option value="">— Selecciona —</option>
          <option value="perdua"            <?= (($_POST['tipus'] ?? '') === 'perdua')            ? 'selected' : '' ?>>Pèrdua</option>
          <option value="extraviu"          <?= (($_POST['tipus'] ?? '') === 'extraviu')          ? 'selected' : '' ?>>Extraviu</option>
          <option value="deteriorament_greu"<?= (($_POST['tipus'] ?? '') === 'deteriorament_greu') ? 'selected' : '' ?>>Deteriorament greu</option>
          <option value="altre"             <?= (($_POST['tipus'] ?? '') === 'altre')             ? 'selected' : '' ?>>Altre</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Descripció</label>
        <textarea name="descripcio" class="form-control" rows="3" required
          placeholder="Descriu la incidència..."><?= htmlspecialchars($_POST['descripcio'] ?? '') ?></textarea>
      </div>

      <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="ha_de_pagar" name="ha_de_pagar"
          <?= isset($_POST['ha_de_pagar']) ? 'checked' : '' ?>
          onchange="document.getElementById('import_wrap').style.display=this.checked?'block':'none'">
        <label class="form-check-label" for="ha_de_pagar">Ha de pagar</label>
      </div>

      <div class="mb-3" id="import_wrap" style="display:<?= isset($_POST['ha_de_pagar']) ? 'block' : 'none' ?>">
        <label class="form-label">Import (€)</label>
        <input type="number" name="import_pagament" class="form-control" style="max-width:150px"
          step="0.01" min="0" value="<?= htmlspecialchars($_POST['import_pagament'] ?? '') ?>">
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-warning">
          <i class="bi bi-exclamation-triangle"></i> Registrar incidència
        </button>
        <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne_id ?>" class="btn btn-secondary">
          <i class="bi bi-arrow-left"></i> Cancel·lar
        </a>
      </div>

    </form>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
