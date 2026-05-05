<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina = 'Llibres';
$paginaActiva = 'llibres';

$llibres = Database::fetchAll(
    "SELECT l.*, m.nom AS materia_nom, cu.codi AS curs_codi
     FROM llibres l
     JOIN materies m ON l.materia_id = m.id
     JOIN cursos cu ON l.curs_id = cu.id
     WHERE l.actiu = 1
     ORDER BY m.nom, l.titol"
);

include __DIR__ . '/../views/llibres.php';
