<?php include __DIR__ . '/layout_top.php'; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/materies/materies.php" class="btn btn-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Tornar a matèries
  </a>
</div>

<div class="card" style="max-width:500px">
  <div class="card-header-bl"><i class="bi bi-plus-circle"></i> Nova matèria</div>
  <div class="card-body">
    <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Nom *</label>
        <input type="text" name="nom" class="form-control" required placeholder="p.ex. Sistemes Informàtics">
      </div>
      <div class="mb-3">
        <label class="form-label">Codi *</label>
        <input type="text" name="codi" class="form-control" required placeholder="p.ex. SI"
               maxlength="10" style="text-transform:uppercase">
        <div class="form-text">Codi curt per a la codificació dels exemplars.</div>
      </div>
      <div class="mb-4">
        <label class="form-label">Tipus</label>
        <select name="tipus" class="form-select">
          <option value="comuna">Comuna</option>
          <option value="optativa">Optativa</option>
        </select>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-plus-circle"></i> Crear matèria
        </button>
        <a href="<?= BASE_URL ?>/materies/materies.php" class="btn btn-secondary">
          <i class="bi bi-x-lg"></i> Cancel·lar
        </a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
