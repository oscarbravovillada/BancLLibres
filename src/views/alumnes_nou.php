<?php include __DIR__ . '/layout_top.php'; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/alumnes/llista.php" class="btn btn-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Tornar a la llista
  </a>
</div>

<div class="card" style="max-width:520px">
  <div class="card-header-bl"><i class="bi bi-person-plus"></i> Nou alumne</div>
  <div class="card-body">

    <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Nom *</label>
        <input type="text" name="nom" class="form-control" required
               value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Cognoms *</label>
        <input type="text" name="cognoms" class="form-control" required
               value="<?= htmlspecialchars($_POST['cognoms'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Classe *</label>
        <select name="classe_id" class="form-select" required>
          <option value="">— Selecciona classe —</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>"
              <?= (($_POST['classe_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['nom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Email família</label>
        <input type="email" name="email_familia" class="form-control"
               value="<?= htmlspecialchars($_POST['email_familia'] ?? '') ?>"
               placeholder="per rebre albarans per correu">
      </div>
      <div class="mb-4">
        <label class="form-label">Telèfon família</label>
        <input type="text" name="telefon" class="form-control"
               value="<?= htmlspecialchars($_POST['telefon'] ?? '') ?>">
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-person-plus"></i> Crear alumne
        </button>
        <a href="<?= BASE_URL ?>/alumnes/llista.php" class="btn btn-secondary">
          <i class="bi bi-x-lg"></i> Cancel·lar
        </a>
      </div>
    </form>

  </div>
</div>

<div class="alert alert-info mt-3" style="max-width:520px">
  <i class="bi bi-info-circle"></i>
  Un cop creat l'alumne, podeu assignar-li el lot de llibres des de la seva fitxa de préstecs.
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
