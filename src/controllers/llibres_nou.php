<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Nou llibre';
$paginaActiva = 'llibres';

$materies = Database::fetchAll("SELECT id, nom FROM materies ORDER BY nom");
$cursos   = Database::fetchAll("SELECT id, codi FROM cursos ORDER BY codi");

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titol      = trim($_POST['titol'] ?? '');
    $isbn       = trim($_POST['isbn'] ?? '');
    $materia_id = (int)($_POST['materia_id'] ?? 0);
    $curs_id    = (int)($_POST['curs_id'] ?? 0);

    if ($titol === '' || !$materia_id || !$curs_id) {
        $errors[] = 'Títol, matèria i curs són obligatoris.';
    }

    if (!$errors) {
        Database::execute(
            "INSERT INTO llibres (titol, isbn, materia_id, curs_id, actiu)
             VALUES (?,?,?,?,1)",
            [$titol, $isbn, $materia_id, $curs_id]
        );
        header('Location: ' . BASE_URL . '/llibres/llibres.php');
        exit;
    }
}

include __DIR__ . '/../views/llibres_nou.php';
