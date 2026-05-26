<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Préstecs';
$paginaActiva = 'prestecs';

if (Auth::rol() === 'admin') {
    $prestecs = Database::fetchAll(
        "SELECT p.*, a.nom, a.cognoms,
                e.codi AS exemplar_codi,
                l.titol
         FROM prestecs p
         JOIN alumnes a   ON p.alumne_id = a.id
         JOIN exemplars e ON p.exemplar_id = e.id
         JOIN llibres l   ON e.llibre_id = l.id
         ORDER BY p.data_prestec DESC
         LIMIT 200"
    );
} else {
    $prestecs = Database::fetchAll(
        "SELECT p.*, a.nom, a.cognoms,
                e.codi AS exemplar_codi,
                l.titol
         FROM prestecs p
         JOIN alumnes a   ON p.alumne_id = a.id
         JOIN exemplars e ON p.exemplar_id = e.id
         JOIN llibres l   ON e.llibre_id = l.id
         JOIN professor_classe pc ON pc.classe_id = a.classe_id
         WHERE pc.professor_id = ?
         ORDER BY p.data_prestec DESC
         LIMIT 200",
        [Auth::id()]
    );
}

include __DIR__ . '/../views/prestecs_llista.php';
