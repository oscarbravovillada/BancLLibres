<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina = 'Matèries';
$paginaActiva = 'materies';

$materies = Database::fetchAll(
    "SELECT * FROM materies ORDER BY nom"
);

include __DIR__ . '/../views/materies.php';
