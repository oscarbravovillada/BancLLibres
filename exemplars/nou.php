<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Nou exemplar';
$paginaActiva = 'exemplars';

$llibres = Database::fetchAll(
    "SELECT id, titol FROM llibres WHERE actiu = 1 ORDER BY titol"
);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::csrfCheck();
    $llibre_id = (int)($_POST['llibre_id'] ?? 0);
    $codi      = trim($_POST['codi'] ?? '');

    if (!$llibre_id || $codi === '') {
        $errors[] = 'Llibre i codi són obligatoris.';
    }

    if (!$errors) {
        Database::execute(
            "INSERT INTO exemplars (llibre_id, codi, estat)
             VALUES (?,?, 'disponible')",
            [$llibre_id, $codi]
        );
        header('Location: ' . BASE_URL . '/exemplars/exemplars.php');
        exit;
    }
}

include __DIR__ . '/../src/views/layout_top.php'; ?>

<h2><i class="bi bi-upc-scan"></i> Nou exemplar</h2>

<?php if ($errors): ?>
<div class="alert alert-danger">
  <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" class="mt-3">
  <?= Auth::csrfField() ?>
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

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
