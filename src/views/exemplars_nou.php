<?php include __DIR__ . '/layout_top.php'; ?>

<h2><i class="bi bi-upc-scan"></i> Nou exemplar</h2>

<?php if ($errors): ?>
<div class="alert alert-danger">
  <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" class="mt-3">
  <div class="mb-3">
    <label class="form-label">Llibre</label>
    <select name="llibre_id" class="form-select" required>
      <option value="">— Selecciona llibre —</option>
      <?php foreach ($llibres as $l): ?>
        <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['titol']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Codi exemplar</label>
    <input type="text" name="codi" class="form-control" required>
  </div>
  <button class="btn btn-primary">Guardar</button>
</form>

<?php include __DIR__ . '/layout_bottom.php'; ?>
