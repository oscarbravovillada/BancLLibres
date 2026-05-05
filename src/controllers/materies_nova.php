<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Nova matèria';
$paginaActiva = 'materies';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom  = trim($_POST['nom'] ?? '');
    $codi = trim($_POST['codi'] ?? '');

    if ($nom === '' || $codi === '') {
        $errors[] = 'Nom i codi són obligatoris.';
    }

    if (!$errors) {
        Database::execute(
            "INSERT INTO materies (nom, codi) VALUES (?,?)",
            [$nom, $codi]
        );
        header('Location: ' . BASE_URL . '/materies/materies.php');
        exit;
    }
}

include __DIR__ . '/../views/materies_nova.php';
