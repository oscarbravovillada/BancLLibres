<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Nou exemplar';
$paginaActiva = 'exemplars';

$llibres = Database::fetchAll(
    "SELECT id, titol FROM llibres WHERE actiu = 1 ORDER BY titol"
);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

include __DIR__ . '/../views/exemplars_nou.php';
