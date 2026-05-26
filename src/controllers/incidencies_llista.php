<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Incidències';
$paginaActiva = 'incidencies';

/* Marcar com a pagat */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_pagat'])) {
    $inc_id = (int)($_POST['incidencia_id'] ?? 0);
    if ($inc_id) {
        Database::execute(
            "UPDATE incidencies SET pagat = 1, data_pagament = CURDATE() WHERE id = ?",
            [$inc_id]
        );
    }
    header('Location: ' . BASE_URL . '/incidencies/index.php?ok=pagat');
    exit;
}

$missatge = '';
if (isset($_GET['ok'])) $missatge = 'Incidència marcada com a pagada.';

if (Auth::rol() === 'admin') {
    $incidencies = Database::fetchAll(
        "SELECT i.*, a.nom, a.cognoms, c.nom AS classe_nom,
                e.codi AS exemplar_codi, l.titol
         FROM incidencies i
         JOIN alumnes a   ON i.alumne_id = a.id
         JOIN classes c   ON a.classe_id = c.id
         JOIN exemplars e ON i.exemplar_id = e.id
         JOIN llibres l   ON e.llibre_id = l.id
         ORDER BY i.data_incidencia DESC
         LIMIT 200"
    );
} else {
    $incidencies = Database::fetchAll(
        "SELECT i.*, a.nom, a.cognoms, c.nom AS classe_nom,
                e.codi AS exemplar_codi, l.titol
         FROM incidencies i
         JOIN alumnes a   ON i.alumne_id = a.id
         JOIN classes c   ON a.classe_id = c.id
         JOIN exemplars e ON i.exemplar_id = e.id
         JOIN llibres l   ON e.llibre_id = l.id
         JOIN professor_classe pc ON pc.classe_id = a.classe_id
         WHERE pc.professor_id = ?
         ORDER BY i.data_incidencia DESC
         LIMIT 200",
        [Auth::id()]
    );
}

include __DIR__ . '/../views/incidencies_llista.php';
