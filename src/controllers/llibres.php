<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Llibres';
$paginaActiva = 'llibres';

$missatge = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accio = $_POST['accio'] ?? '';

    /* EDITAR */
    if ($accio === 'editar') {
        $id         = (int)($_POST['id'] ?? 0);
        $titol      = trim($_POST['titol']      ?? '');
        $isbn       = trim($_POST['isbn']       ?? '');
        $editorial  = trim($_POST['editorial']  ?? '');
        $materia_id = (int)($_POST['materia_id'] ?? 0);
        $curs_id    = (int)($_POST['curs_id']    ?? 0);

        if ($id && $titol !== '' && $materia_id && $curs_id) {
            Database::execute(
                "UPDATE llibres SET titol=?, isbn=?, editorial=?, materia_id=?, curs_id=? WHERE id=?",
                [$titol, $isbn, $editorial, $materia_id, $curs_id, $id]
            );
            $missatge = 'Llibre actualitzat.';
        }
    }

    /* ELIMINAR */
    if ($accio === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $usos = Database::fetchOne(
                "SELECT COUNT(*) n FROM exemplars WHERE llibre_id = ?", [$id]
            )['n'] ?? 0;

            if ($usos > 0) {
                $errorMsg = "No es pot eliminar: hi ha {$usos} exemplar(s) d'aquest llibre.";
            } else {
                $titol = Database::fetchOne("SELECT titol FROM llibres WHERE id=?", [$id])['titol'] ?? '';
                Database::execute("DELETE FROM llibres WHERE id=?", [$id]);
                $missatge = "Llibre «{$titol}» eliminat.";
            }
        }
    }

    if (!$errorMsg) {
        header('Location: ' . BASE_URL . '/llibres/llibres.php' . ($missatge ? '?ok=1' : ''));
        exit;
    }
}

if (isset($_GET['ok'])) $missatge = 'Operació completada correctament.';

/* Filtre per matèria */
$filtreMateria = (int)($_GET['materia_id'] ?? 0);

$whereClause = 'l.actiu = 1';
$params = [];
if ($filtreMateria) {
    $whereClause .= ' AND l.materia_id = ?';
    $params[] = $filtreMateria;
}

$llibres = Database::fetchAll(
    "SELECT l.*, m.nom AS materia_nom, m.codi AS mat_codi,
            cu.codi AS curs_codi,
            COUNT(e.id) AS num_exemplars,
            SUM(e.disponible) AS num_disponibles
     FROM llibres l
     JOIN materies m  ON l.materia_id = m.id
     JOIN cursos cu   ON l.curs_id = cu.id
     LEFT JOIN exemplars e ON e.llibre_id = l.id
     WHERE {$whereClause}
     GROUP BY l.id
     ORDER BY m.nom, l.titol",
    $params
);

$materies = Database::fetchAll(
    "SELECT m.id, m.nom, m.codi, m.tipus,
            COUNT(l.id) AS num_llibres,
            COALESCE(SUM(
                (SELECT COUNT(*) FROM exemplars e WHERE e.llibre_id = l.id)
            ), 0) AS num_exemplars
     FROM materies m
     LEFT JOIN llibres l ON l.materia_id = m.id AND l.actiu = 1
     GROUP BY m.id
     ORDER BY m.tipus ASC, m.nom ASC"
);
$cursos = Database::fetchAll("SELECT id, codi FROM cursos ORDER BY codi");

$materiaActual = $filtreMateria
    ? Database::fetchOne("SELECT id, nom, codi, tipus FROM materies WHERE id=?", [$filtreMateria])
    : null;

include __DIR__ . '/../views/llibres.php';
