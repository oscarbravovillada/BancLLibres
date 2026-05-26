<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = "Importar classes";
$paginaActiva = "import_classes";
$missatge     = "";
$errors       = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml']) && $_FILES['xml']['error'] === UPLOAD_ERR_OK) {

    $xmlFile = $_FILES['xml']['tmp_name'];
    $xml     = @simplexml_load_file($xmlFile);

    if (!$xml) {
        $errors[] = "No s'ha pogut llegir l'XML de classes.";
    } else {

        $count = 0;

        foreach ($xml->classe as $c) {

            $nom       = trim((string)$c['nom']);        // ex: 1ASIR-A
            $codiCurs  = trim((string)$c['codi_curs']);  // ex: 1ASIR
            $tutorDni  = trim((string)$c['tutor_dni']);  // ex: 021111111J

            if ($nom === '') {
                $errors[] = "Classe sense nom, s'ignora.";
                continue;
            }

            // Buscar curs per codi
            $curs = Database::fetchOne(
                "SELECT id FROM cursos WHERE codi = ?",
                [$codiCurs]
            );
            if (!$curs) {
                $errors[] = "Curs no trobat per a la classe $nom: $codiCurs";
                $cursId = null;
            } else {
                $cursId = $curs['id'];
            }

            // Buscar tutor per DNI
            $tutorId = null;
            if ($tutorDni !== '') {
                $tutor = Database::fetchOne(
                    "SELECT id FROM usuaris WHERE document = ?",
                    [$tutorDni]
                );
                if ($tutor) {
                    $tutorId = $tutor['id'];
                } else {
                    $errors[] = "Tutor no trobat per a la classe $nom (DNI: $tutorDni)";
                }
            }

            // Comprovar si la classe ja existeix
            $classe = Database::fetchOne(
                "SELECT * FROM classes WHERE nom = ?",
                [$nom]
            );

            if ($classe) {
                Database::execute(
                    "UPDATE classes
                     SET curs_id = ?, tutor_id = ?, curs_escolar = ?
                     WHERE id = ?",
                    [$cursId ?: $classe['curs_id'], $tutorId, ANY_ESCOLAR, $classe['id']]
                );
            } else {
                Database::execute(
                    "INSERT INTO classes (curs_id, nom, tutor_id, curs_escolar)
                     VALUES (?,?,?,?)",
                    [$cursId, $nom, $tutorId, ANY_ESCOLAR]
                );
            }

            $count++;
        }

        $missatge = "Importació de classes completada. Classes processades: $count.";
    }
}

include __DIR__ . '/../src/views/layout_top.php'; ?>

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

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
