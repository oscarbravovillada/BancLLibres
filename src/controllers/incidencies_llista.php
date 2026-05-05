<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Incidències';
$paginaActiva = 'incidencies';

$incidencies = Database::fetchAll(
    "SELECT i.*, a.nom, a.cognoms,
            e.codi AS exemplar_codi,
            l.titol
     FROM incidencies i
     JOIN alumnes a   ON i.alumne_id = a.id
     JOIN exemplars e ON i.exemplar_id = e.id
     JOIN llibres l   ON e.llibre_id = l.id
     ORDER BY i.data_incidencia DESC
     LIMIT 100"
);

include __DIR__ . '/../views/incidencies_llista.php';
