<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina = 'Alumnes per classe';
$paginaActiva = 'alumnes';

$usuari_id = Auth::id();
$usuari_rol = Auth::rol();

$classeSeleccionada = (int)($_GET['classe_id'] ?? 0);

/* ============================================================
   1) OBTENIR CLASSES ACCESSIBLES
   ============================================================ */

if ($usuari_rol === 'admin') {

    $classes = Database::fetchAll(
        "SELECT c.*, cu.codi AS curs_codi, cu.nom AS curs_nom,
                CONCAT(u.nom,' ',u.cognoms) AS tutor_nom
         FROM classes c
         JOIN cursos cu ON c.curs_id = cu.id
         LEFT JOIN usuaris u ON c.tutor_id = u.id
         WHERE c.curs_escolar = ?
         ORDER BY c.nom",
        [ANY_ESCOLAR]
    );

} else {

    $classes = Database::fetchAll(
        "SELECT c.*, cu.codi AS curs_codi, cu.nom AS curs_nom,
                CONCAT(u.nom,' ',u.cognoms) AS tutor_nom
         FROM classes c
         JOIN cursos cu ON c.curs_id = cu.id
         LEFT JOIN usuaris u ON c.tutor_id = u.id
         JOIN professor_classe pc ON pc.classe_id = c.id
         WHERE pc.professor_id = ? AND c.curs_escolar = ?
         ORDER BY c.nom",
        [$usuari_id, ANY_ESCOLAR]
    );
}

/* ============================================================
   2) OBTENIR ALUMNES DE LA CLASSE SELECCIONADA
   ============================================================ */

$alumnes = [];
$classeActual = null;

if ($classeSeleccionada) {

    $classeActual = Database::fetchOne(
        "SELECT * FROM classes WHERE id = ?",
        [$classeSeleccionada]
    );

    if ($classeActual) {
        $alumnes = Database::fetchAll(
            "SELECT a.*,
                (SELECT COUNT(*)
                 FROM prestecs p
                 WHERE p.alumne_id = a.id AND p.estat_prestec = 'actiu') AS llibres_actius,
                (SELECT COUNT(*)
                 FROM incidencies i
                 WHERE i.alumne_id = a.id AND i.ha_de_pagar = 1 AND i.pagat = 0) AS pagaments_pendents
             FROM alumnes a
             WHERE a.classe_id = ? AND a.actiu = 1
             ORDER BY a.cognoms, a.nom",
            [$classeSeleccionada]
        );
    }
}

include __DIR__ . '/../views/alumnes_llista.php';
