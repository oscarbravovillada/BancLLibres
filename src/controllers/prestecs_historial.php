<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Database.php';

Auth::requireLogin();

$titolPagina  = 'Historial';
$paginaActiva = 'prestecs';

$alumne_id = (int)($_GET['alumne_id'] ?? 0);
if (!$alumne_id) {
    header('Location: ' . BASE_URL . '/prestecs/index.php');
    exit;
}

Auth::requireAccessToAlumne($alumne_id);

$alumne = Database::fetchOne(
    "SELECT a.*, c.nom AS classe_nom FROM alumnes a JOIN classes c ON a.classe_id = c.id WHERE a.id = ?",
    [$alumne_id]
);
if (!$alumne) die("Alumne no trobat");

$historial = Database::fetchAll(
    "SELECT h.id, h.accio, h.detalls, h.creat_at,
            e.codi AS exemplar_codi
     FROM historial h
     LEFT JOIN exemplars e ON e.id = h.exemplar_id
     WHERE h.alumne_id = ?
     ORDER BY h.creat_at DESC",
    [$alumne_id]
);

include __DIR__ . '/../views/prestecs_historial.php';
