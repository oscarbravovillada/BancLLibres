<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Nou alumne';
$paginaActiva = 'alumnes';

function slug3nou(string $txt): string {
    $txt = iconv('UTF-8', 'ASCII//TRANSLIT', trim($txt)) ?: $txt;
    return substr(preg_replace('/[^a-z0-9]/', '', strtolower($txt)), 0, 3);
}

function generarEmailAlumne(string $nom, string $cognoms): string {
    $parts   = explode(' ', $cognoms, 2);
    $cognom1 = $parts[0] ?? '';
    $cognom2 = $parts[1] ?? '';
    $base    = slug3nou($nom) . slug3nou($cognom1) . slug3nou($cognom2);
    if ($base === '') $base = 'alumne';

    $username = $base;
    $i = 1;
    while (Database::fetchOne("SELECT id FROM alumnes WHERE email_institucional = ?",
           [$username . '@alu.edu.gva.es'])) {
        $username = $base . $i++;
    }
    return $username . '@alu.edu.gva.es';
}

$classes = Database::fetchAll(
    "SELECT id, nom FROM classes WHERE curs_escolar = ? ORDER BY nom",
    [ANY_ESCOLAR]
);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom           = trim($_POST['nom']           ?? '');
    $cognoms       = trim($_POST['cognoms']       ?? '');
    $email_familia = trim($_POST['email_familia'] ?? '');
    $telefon       = trim($_POST['telefon']       ?? '');
    $classe_id     = (int)($_POST['classe_id']    ?? 0);

    if ($nom === '' || $cognoms === '' || !$classe_id) {
        $errors[] = 'Nom, cognoms i classe són obligatoris.';
    }

    if (!$errors) {
        $emailInst = generarEmailAlumne($nom, $cognoms);
        Database::execute(
            "INSERT INTO alumnes (nom, cognoms, email_familia, telefon_familia, classe_id, actiu, email_institucional)
             VALUES (?,?,?,?,?,1,?)",
            [$nom, $cognoms, $email_familia, $telefon, $classe_id, $emailInst]
        );
        header('Location: ' . BASE_URL . '/alumnes/llista.php?classe_id=' . $classe_id);
        exit;
    }
}

include __DIR__ . '/../src/views/layout_top.php'; ?>

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

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
