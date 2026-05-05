<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina   = 'Fitxa alumne';
$paginaActiva  = 'alumnes';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . '/alumnes/llista.php');
    exit;
}

$alumne = Database::fetchOne(
    "SELECT a.*, c.nom AS classe_nom
     FROM alumnes a
     LEFT JOIN classes c ON a.classe_id = c.id
     WHERE a.id = ?",
    [$id]
);

if (!$alumne) {
    header('Location: ' . BASE_URL . '/alumnes/llista.php');
    exit;
}

$prestecs = Database::fetchAll(
    "SELECT p.*, e.codi AS exemplar_codi, l.titol
     FROM prestecs p
     JOIN exemplars e ON p.exemplar_id = e.id
     JOIN llibres l   ON e.llibre_id = l.id
     WHERE p.alumne_id = ?
     ORDER BY p.data_prestec DESC",
    [$id]
);

include __DIR__ . '/../views/alumnes_fitxa.php';
