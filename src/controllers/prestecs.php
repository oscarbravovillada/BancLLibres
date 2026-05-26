<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = "Fitxa de l'alumne";
$paginaActiva = "prestecs";

$alumne_id = (int)($_GET['id'] ?? 0);
if (!$alumne_id) {
    header("Location: /BancLLibres/index.php");
    exit;
}

/* ============================================================
   1) DADES DE L'ALUMNE
   ============================================================ */
$alumne = Database::fetchOne(
    "SELECT a.*, c.nom AS classe_nom, CONCAT(u.nom,' ',u.cognoms) AS tutor_nom
     FROM alumnes a
     JOIN classes c ON a.classe_id = c.id
     LEFT JOIN usuaris u ON c.tutor_id = u.id
     WHERE a.id = ?",
    [$alumne_id]
);

if (!$alumne) {
    die("Alumne no trobat.");
}

Auth::requireAccessToAlumne($alumne_id);

/* ============================================================
   2) LOT ASSIGNAT
   ============================================================ */
$lot = Database::fetchOne(
    "SELECT * FROM lots WHERE alumne_id = ?",
    [$alumne_id]
);

/* ============================================================
   3) EXEMPLARS DEL LOT
   ============================================================ */
$exemplars_lot = [];
if ($lot) {
    $exemplars_lot = Database::fetchAll(
        "SELECT e.*, l.titol, m.nom AS materia_nom
         FROM exemplars e
         JOIN llibres l ON e.llibre_id = l.id
         JOIN materies m ON l.materia_id = m.id
         WHERE e.lot_id = ?
         ORDER BY m.nom",
        [$lot['id']]
    );
}

/* ============================================================
   4) EXEMPLARS INDIVIDUALS (OPTATIVES)
   ============================================================ */
$optatives = Database::fetchAll(
    "SELECT e.*, l.titol, m.nom AS materia_nom
     FROM exemplars e
     JOIN llibres l ON e.llibre_id = l.id
     JOIN materies m ON l.materia_id = m.id
     WHERE e.alumne_id = ? AND e.lot_id IS NULL
     ORDER BY m.nom",
    [$alumne_id]
);

/* ============================================================
   5) CARREGAR LA VISTA
   ============================================================ */
include __DIR__ . '/../views/prestecs.php';
