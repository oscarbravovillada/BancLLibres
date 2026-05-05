<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina = 'Classes';
$paginaActiva = 'classes';

$classes = Database::fetchAll(
    "SELECT c.*, cu.codi AS curs_codi, cu.nom AS curs_nom,
            CONCAT(u.nom,' ',u.cognoms) AS tutor_nom
     FROM classes c
     JOIN cursos cu ON c.curs_id = cu.id
     LEFT JOIN usuaris u ON c.tutor_id = u.id
     WHERE c.curs_escolar = ?
     ORDER BY cu.codi, c.nom",
    [ANY_ESCOLAR]
);

include __DIR__ . '/../views/classes.php';
