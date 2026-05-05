<?php include __DIR__ . '/layout_top.php'; ?>

<h2><i class="bi bi-bookmark-plus"></i> Nova matèria</h2>

<?php if ($errors): ?>
<div class="alert alert-danger">
  <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" class="mt-3">
  <div class="mb-3">
    <label class="form-label">Nom</label>
    <input type="text" name="nom" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Codi</label>
    <input type="text" name="codi" class="form-control" required>
  </div>
  <button class="btn btn-primary">Guardar</button>
</form>

<?php include __DIR__ . '/layout_bottom.php'; ?>
