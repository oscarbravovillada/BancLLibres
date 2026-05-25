<?php include __DIR__ . '/layout_top.php'; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/llibres/llibres.php" class="btn btn-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Tornar a llibres
  </a>
</div>

<div class="card" style="max-width:540px">
  <div class="card-header-bl"><i class="bi bi-plus-circle"></i> Nou llibre</div>
  <div class="card-body">
    <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Títol *</label>
        <input type="text" name="titol" class="form-control" required
               placeholder="p.ex. Sistemes Informàtics 1">
      </div>
      <div class="mb-3">
        <label class="form-label">ISBN</label>
        <input type="text" name="isbn" class="form-control" placeholder="p.ex. 978-84-...">
      </div>
      <div class="mb-3">
        <label class="form-label">Editorial</label>
        <input type="text" name="editorial" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Matèria *</label>
        <select name="materia_id" class="form-select" required>
          <option value="">— Selecciona matèria —</option>
          <?php foreach ($materies as $m): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-4">
        <label class="form-label">Curs *</label>
        <select name="curs_id" class="form-select" required>
          <option value="">— Selecciona curs —</option>
          <?php foreach ($cursos as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['codi']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-plus-circle"></i> Crear llibre
        </button>
        <a href="<?= BASE_URL ?>/llibres/llibres.php" class="btn btn-secondary">
          <i class="bi bi-x-lg"></i> Cancel·lar
        </a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
