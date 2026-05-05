<?php include __DIR__ . '/layout_top.php'; ?>

<h2><i class="bi bi-upload"></i> Importar classes</h2>

<?php if (!empty($missatge)): ?>
  <div class="alert alert-success mt-3"><?= htmlspecialchars($missatge) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger mt-3">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="mt-4">
  <div class="mb-3">
    <label class="form-label">Fitxer XML de classes</label>
    <input type="file" name="xml" class="form-control" required>
    <div class="form-text">
      Ha de contindre elements &lt;classe&gt; amb atributs: nom, codi_curs, tutor_dni.
    </div>
  </div>

  <button class="btn btn-primary">
    <i class="bi bi-upload"></i> Importar
  </button>
</form>

<?php include __DIR__ . '/layout_bottom.php'; ?>
