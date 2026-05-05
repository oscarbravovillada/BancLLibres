<?php include __DIR__ . '/layout_top.php'; ?>

<h2><i class="bi bi-person-plus"></i> Nou alumne</h2>

<?php if ($errors): ?>
<div class="alert alert-danger">
  <?php foreach ($errors as $e): ?>
    <div><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" class="mt-3">
  <div class="mb-3">
    <label class="form-label">Nom</label>
    <input type="text" name="nom" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Cognoms</label>
    <input type="text" name="cognoms" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Email família</label>
    <input type="email" name="email_familia" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">Classe</label>
    <select name="classe_id" class="form-select" required>
      <option value="">— Selecciona classe —</option>
      <?php foreach ($classes as $c): ?>
        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn-primary">Guardar</button>
</form>

<?php include __DIR__ . '/layout_bottom.php'; ?>
