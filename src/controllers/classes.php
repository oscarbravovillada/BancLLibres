<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina = 'Classes';
$paginaActiva = 'classes';
$missatge = '';
$errorMsg = '';

// Eliminar classe (sols admin, POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accio']) && $_POST['accio'] === 'eliminar') {
    Auth::requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $alumnesCount = Database::fetchOne(
            "SELECT COUNT(*) n FROM alumnes WHERE classe_id = ? AND actiu = 1",
            [$id]
        )['n'] ?? 0;

        if ($alumnesCount > 0) {
            $errorMsg = "No es pot eliminar la classe: té $alumnesCount alumne/s assignat/s.";
        } else {
            Database::execute("DELETE FROM classes WHERE id = ?", [$id]);
            $missatge = "Classe eliminada correctament.";
        }
    }
}

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
