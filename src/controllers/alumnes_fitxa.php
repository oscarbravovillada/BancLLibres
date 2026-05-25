<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Fitxa alumne';
$paginaActiva = 'alumnes';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . '/alumnes/llista.php');
    exit;
}

$alumne = Database::fetchOne(
    "SELECT a.*, c.nom AS classe_nom, CONCAT(u.nom,' ',u.cognoms) AS tutor_nom
     FROM alumnes a
     LEFT JOIN classes c ON a.classe_id = c.id
     LEFT JOIN usuaris u ON c.tutor_id = u.id
     WHERE a.id = ?",
    [$id]
);

if (!$alumne) {
    header('Location: ' . BASE_URL . '/alumnes/llista.php');
    exit;
}

$lot = Database::fetchOne("SELECT * FROM lots WHERE alumne_id = ?", [$id]);

$prestecs = Database::fetchAll(
    "SELECT p.*, e.codi AS exemplar_codi, l.titol, m.nom AS materia_nom
     FROM prestecs p
     JOIN exemplars e ON p.exemplar_id = e.id
     JOIN llibres l   ON e.llibre_id = l.id
     JOIN materies m  ON l.materia_id = m.id
     WHERE p.alumne_id = ?
     ORDER BY p.data_prestec DESC",
    [$id]
);

$incidencies = Database::fetchAll(
    "SELECT i.*, e.codi AS exemplar_codi, l.titol
     FROM incidencies i
     JOIN exemplars e ON i.exemplar_id = e.id
     JOIN llibres l   ON e.llibre_id = l.id
     WHERE i.alumne_id = ?
     ORDER BY i.data_incidencia DESC",
    [$id]
);

include __DIR__ . '/../views/alumnes_fitxa.php';
