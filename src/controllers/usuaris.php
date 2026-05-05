<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina = 'Usuaris';
$paginaActiva = 'usuaris';

$usuaris = Database::fetchAll(
    "SELECT id, nom, cognoms, email, rol
     FROM usuaris
     ORDER BY cognoms, nom"
);

include __DIR__ . '/../views/usuaris.php';
