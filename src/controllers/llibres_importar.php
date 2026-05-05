<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = "Importar llibres";
$paginaActiva = "import_llibres";
$missatge     = "";
$errors       = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml']) && $_FILES['xml']['error'] === UPLOAD_ERR_OK) {

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

include __DIR__ . '/../views/llibres_importar.php';
