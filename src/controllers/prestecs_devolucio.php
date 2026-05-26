<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/../../vendor/MailSender.php';
require_once __DIR__ . '/../../vendor/PdfGenerator.php';

Auth::requireLogin();

$titolPagina  = 'Registrar devolució';
$paginaActiva = 'prestecs';

$alumne_id = (int)($_GET['alumne_id'] ?? 0);
if (!$alumne_id) {
    header('Location: ' . BASE_URL . '/prestecs/index.php');
    exit;
}

Auth::requireAccessToAlumne($alumne_id);

/* Alumne */
$alumne = Database::fetchOne(
    "SELECT a.*, c.nom AS classe_nom, CONCAT(u.nom,' ',u.cognoms) AS tutor_nom
     FROM alumnes a
     JOIN classes c ON a.classe_id = c.id
     LEFT JOIN usuaris u ON c.tutor_id = u.id
     WHERE a.id = ?",
    [$alumne_id]
);

/* Exemplars actius */
$exemplars = Database::fetchAll(
    "SELECT e.*, l.titol, m.nom AS materia_nom
     FROM exemplars e
     JOIN llibres l ON e.llibre_id = l.id
     JOIN materies m ON l.materia_id = m.id
     WHERE e.alumne_id = ?
     ORDER BY m.nom",
    [$alumne_id]
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $retornats    = [];
    $no_retornats = [];
    $pendents     = [];

    foreach ($_POST['estat'] as $id => $estat) {

        if ($estat === 'pendent') {

            Database::execute(
                "UPDATE prestecs SET estat = 'pendent', estat_prestec = 'pendent'
                 WHERE exemplar_id = ? AND alumne_id = ? AND estat = 'actiu'",
                [$id, $alumne_id]
            );

            $ex_pend = Database::fetchOne(
                "SELECT e.codi, l.titol FROM exemplars e
                 JOIN llibres l ON e.llibre_id = l.id WHERE e.id = ?",
                [$id]
            );

            Database::insert(
                "INSERT INTO historial (alumne_id, exemplar_id, accio, detalls, usuari_id) VALUES (?,?,?,?,?)",
                [$alumne_id, $id, 'pendent', "Pendent de retorn: {$ex_pend['codi']} — {$ex_pend['titol']}", Auth::id()]
            );

            $pendents[] = ['codi' => $ex_pend['codi'], 'titol' => $ex_pend['titol']];
        }

        if ($estat === 'retornat') {

            /* Alliberar exemplar */
            Database::execute(
                "UPDATE exemplars
                 SET alumne_id = NULL, lot_id = NULL, disponible = 1
                 WHERE id = ?",
                [$id]
            );

            /* Tancar préstec */
            Database::execute(
                "UPDATE prestecs SET estat = 'retornat', estat_prestec = 'retornat', data_devolucio = NOW()
                 WHERE exemplar_id = ? AND alumne_id = ? AND estat = 'actiu'",
                [$id, $alumne_id]
            );

            $ex_ret = Database::fetchOne(
                "SELECT e.codi, e.estat, l.titol, m.nom AS materia
                 FROM exemplars e
                 JOIN llibres l ON e.llibre_id = l.id
                 JOIN materies m ON l.materia_id = m.id
                 WHERE e.id = ?",
                [$id]
            );
            $retornats[] = [
                'codi'              => $ex_ret['codi'],
                'titol'             => $ex_ret['titol'],
                'materia'           => $ex_ret['materia'],
                'estat_inicial'     => $ex_ret['estat'],
                'estat_final'       => $ex_ret['estat'],
                'desperfectes_final'=> '',
            ];

            /* Historial */
            Database::insert(
                "INSERT INTO historial (alumne_id, exemplar_id, accio, detalls, usuari_id) VALUES (?,?,?,?,?)",
                [$alumne_id, $id, 'devolucio', "Retornat: {$ex_ret['codi']} — {$ex_ret['titol']}", Auth::id()]
            );
        }

        if ($estat === 'perdut') {

            /* Marcar com perdut */
            Database::execute(
                "UPDATE exemplars
                 SET estat = 'perdut', disponible = 0
                 WHERE id = ?",
                [$id]
            );

            /* Tancar préstec */
            Database::execute(
                "UPDATE prestecs SET estat = 'perdut', estat_prestec = 'perdut', data_devolucio = NOW()
                 WHERE exemplar_id = ? AND alumne_id = ? AND estat = 'actiu'",
                [$id, $alumne_id]
            );

            /* Registrar incidència */
            Database::insert(
                "INSERT INTO incidencies (alumne_id, exemplar_id, tipus, descripcio)
                 VALUES (?, ?, 'perdua', 'Pèrdua automàtica en devolució')",
                [$alumne_id, $id]
            );

            /* Historial */
            $ex_perd = Database::fetchOne("SELECT e.codi, l.titol FROM exemplars e JOIN llibres l ON e.llibre_id=l.id WHERE e.id=?", [$id]);
            Database::insert(
                "INSERT INTO historial (alumne_id, exemplar_id, accio, detalls, usuari_id) VALUES (?,?,?,?,?)",
                [$alumne_id, $id, 'perdua', "Perdut en devolució: {$ex_perd['codi']} — {$ex_perd['titol']}", Auth::id()]
            );

            $no_retornats[] = [
                'codi'  => $ex_perd['codi'],
                'titol' => $ex_perd['titol'],
                'motiu' => 'Perdut',
            ];
        }
    }

    /* ============================
       CÀRRECS PENDENTS DE PAGAMENT
       ============================ */
    $carrecs = Database::fetchAll(
        "SELECT i.tipus, i.descripcio, i.import_pagament, i.pagat, i.data_pagament,
                e.codi AS exemplar_codi, l.titol
         FROM incidencies i
         JOIN exemplars e ON i.exemplar_id = e.id
         JOIN llibres l   ON e.llibre_id = l.id
         WHERE i.alumne_id = ? AND i.ha_de_pagar = 1
         ORDER BY i.data_incidencia DESC",
        [$alumne_id]
    );

    /* ============================
       GENERAR PDF DE DEVOLUCIÓ
       ============================ */
    $dades_pdf = [
        'alumne_id'    => $alumne_id,
        'alumne'       => $alumne['nom'] . ' ' . $alumne['cognoms'],
        'classe'       => $alumne['classe_nom'],
        'tutor'        => $alumne['tutor_nom'],
        'retornats'    => $retornats,
        'no_retornats' => $no_retornats,
        'pendents'     => $pendents,
        'carrecs'      => $carrecs,
        'responsable'  => Auth::nom(),
    ];

    $fitxer_pdf = PdfGenerator::albaraDevolucio($dades_pdf);

    $albaraId = Database::insert(
        "INSERT INTO albarans (alumne_id, tipus, fitxer_pdf, data)
         VALUES (?, 'devolucio', ?, NOW())",
        [$alumne_id, $fitxer_pdf]
    );

    if (!empty($alumne['email_familia'])) {
        try { MailSender::enviarAlbara($albaraId, $alumne['email_familia'], $alumne['nom'] . ' ' . $alumne['cognoms'], 'devolucio'); } catch (\Throwable $e) { /* correu no configurat */ }
    }

    header('Location: ' . BASE_URL . '/prestecs/prestecs.php?id=' . $alumne_id);
    exit;
}

include __DIR__ . '/../views/prestecs_devolucio.php';
