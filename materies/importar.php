<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = "Importar matèries";
$paginaActiva = "import_materies";

$missatge = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_FILES['xml']) &&
    $_FILES['xml']['error'] === UPLOAD_ERR_OK) {

    $xmlFile = $_FILES['xml']['tmp_name'];
    $xml = @simplexml_load_file($xmlFile);

    if (!$xml) {
        $errors[] = "No s'ha pogut llegir l'XML de matèries.";
    } else {

        $count = 0;

        foreach ($xml->materia as $m) {

            $nom  = trim((string)$m['nom']);
            $codi = trim((string)$m['codi']);

            if ($nom === '' || $codi === '') {
                $errors[] = "Matèria sense nom o codi, s'ignora.";
                continue;
            }

            // Comprovar si ja existeix
            $existeix = Database::fetchOne(
                "SELECT id FROM materies WHERE codi = ?",
                [$codi]
            );

            if ($existeix) {
                Database::execute(
                    "UPDATE materies SET nom = ? WHERE id = ?",
                    [$nom, $existeix['id']]
                );
            } else {
                Database::execute(
                    "INSERT INTO materies (nom, codi) VALUES (?, ?)",
                    [$nom, $codi]
                );
            }

            $count++;
        }

        $missatge = "Importació completada. Matèries processades: $count.";
    }
}

include __DIR__ . '/../src/views/layout_top.php';
?>

<div class="card">
  <div class="card-header-bl">
    <i class="bi bi-upload"></i> Importar matèries
  </div>

  <div class="card-body">

    <?php if ($missatge): ?>
      <div class="alert alert-success"><?= $missatge ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?>
          <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <label class="form-label fw-semibold">Fitxer XML</label>
      <input type="file" name="xml" accept=".xml" class="form-control mb-3" required>

      <button class="btn btn-primary">
        <i class="bi bi-upload"></i> Importar
      </button>
    </form>

  </div>
</div>

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
