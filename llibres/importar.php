<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = "Importar llibres";
$paginaActiva = "import_llibres";
$missatge     = "";
$errors       = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml']) && $_FILES['xml']['error'] === UPLOAD_ERR_OK) {
    Auth::csrfCheck();

    $xmlFile = $_FILES['xml']['tmp_name'];
    $xml     = @simplexml_load_file($xmlFile);

    if (!$xml) {
        $errors[] = "No s'ha pogut llegir l'XML de llibres.";
    } else {

        $count = 0;

        foreach ($xml->llibre as $l) {

            $isbn        = trim((string)$l['isbn']);
            $titol       = trim((string)$l['titol']);
            $editorial   = trim((string)$l['editorial']);
            $materiaCodi = trim((string)$l['materia_codi']);
            $cursCodi    = trim((string)$l['curs_codi']);

            if ($titol === '') {
                $errors[] = "Llibre sense títol, s'ignora.";
                continue;
            }

            // Buscar curs
            $curs = Database::fetchOne(
                "SELECT id FROM cursos WHERE codi = ?",
                [$cursCodi]
            );
            if (!$curs) {
                $errors[] = "Curs no trobat per al llibre '$titol' (codi: $cursCodi)";
                $cursId = null;
            } else {
                $cursId = $curs['id'];
            }

            // Buscar matèria
            $materia = Database::fetchOne(
                "SELECT id FROM materies WHERE codi = ?",
                [$materiaCodi]
            );
            if (!$materia) {
                $errors[] = "Matèria no trobada per al llibre '$titol' (codi: $materiaCodi)";
                $materiaId = null;
            } else {
                $materiaId = $materia['id'];
            }

            // Comprovar si ja existeix per ISBN
            $llibre = null;
            if ($isbn !== '') {
                $llibre = Database::fetchOne(
                    "SELECT * FROM llibres WHERE isbn = ?",
                    [$isbn]
                );
            }

            if ($llibre) {
                Database::execute(
                    "UPDATE llibres
                     SET titol = ?, editorial = ?, materia_id = ?, curs_id = ?, actiu = 1
                     WHERE id = ?",
                    [$titol, $editorial ?: $llibre['editorial'], $materiaId ?: $llibre['materia_id'], $cursId ?: $llibre['curs_id'], $llibre['id']]
                );
            } else {
                Database::execute(
                    "INSERT INTO llibres (titol, isbn, editorial, materia_id, curs_id, actiu)
                     VALUES (?,?,?,?,?,1)",
                    [$titol, $isbn ?: null, $editorial ?: null, $materiaId, $cursId]
                );
            }

            $count++;
        }

        $missatge = "Importació de llibres completada. Llibres processats: $count.";
    }
}

include __DIR__ . '/../src/views/layout_top.php'; ?>

<h2><i class="bi bi-upload"></i> Importar llibres</h2>

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
  <?= Auth::csrfField() ?>
  <div class="mb-3">
    <label class="form-label">Fitxer XML de llibres</label>
    <input type="file" name="xml" class="form-control" required>
    <div class="form-text">
      Ha de contindre elements &lt;llibre&gt; amb atributs: isbn, titol, editorial, materia_codi, curs_codi.
    </div>
  </div>

  <button class="btn btn-primary">
    <i class="bi bi-upload"></i> Importar
  </button>
</form>

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
