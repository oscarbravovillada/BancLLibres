<?php include __DIR__ . '/layout_top.php'; ?>

<h2><i class="bi bi-journal-plus"></i> Nou llibre</h2>

<?php if ($errors): ?>
<div class="alert alert-danger">
  <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" class="mt-3">
  <div class="mb-3">
    <label class="form-label">Títol</label>
    <input type="text" name="titol" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">ISBN</label>
    <input type="text" name="isbn" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">Matèria</label>
    <select name="materia_id" class="form-select" required>
      <option value="">— Selecciona matèria —</option>
      <?php foreach ($materies as $m): ?>
        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Curs</label>
    <select name="curs_id" class="form-select" required>
      <option value="">— Selecciona curs —</option>
      <?php foreach ($cursos as $c): ?>
        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['codi']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn-primary">Guardar</button>
</form>

<?php include __DIR__ . '/layout_bottom.php'; ?>
