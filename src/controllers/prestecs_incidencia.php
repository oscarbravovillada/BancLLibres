<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/../../vendor/PdfGenerator.php';
require_once __DIR__ . '/../../vendor/MailSender.php';

Auth::requireLogin();

$titolPagina  = "Registrar incidència";
$paginaActiva = "prestecs";

$alumne_id = (int)($_GET['alumne_id'] ?? 0);
if (!$alumne_id) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

Auth::requireAccessToAlumne($alumne_id);

$alumne = Database::fetchOne(
    "SELECT a.*, c.nom AS classe_nom, CONCAT(u.nom,' ',u.cognoms) AS tutor_nom
     FROM alumnes a
     JOIN classes c ON a.classe_id = c.id
     LEFT JOIN usuaris u ON c.tutor_id = u.id
     WHERE a.id = ?",
    [$alumne_id]
);
if (!$alumne) die("Alumne no trobat");

$exemplars = Database::fetchAll(
    "SELECT e.id, e.codi, l.titol, m.nom AS materia_nom
     FROM exemplars e
     JOIN llibres l ON e.llibre_id = l.id
     JOIN materies m ON l.materia_id = m.id
     WHERE e.alumne_id = ?
     ORDER BY m.nom",
    [$alumne_id]
);

$errors   = [];
$missatge = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exemplar_id = (int)($_POST['exemplar_id'] ?? 0);
    $tipus       = trim($_POST['tipus'] ?? '');
    $descripcio  = trim($_POST['descripcio'] ?? '');
    $ha_de_pagar = isset($_POST['ha_de_pagar']) ? 1 : 0;
    $import      = $ha_de_pagar ? (float)($_POST['import_pagament'] ?? 0) : null;

    $tipusValids = ['perdua', 'deteriorament_greu', 'extraviu', 'altre'];

    if (!$exemplar_id)                    $errors[] = "Cal seleccionar un exemplar.";
    if (!in_array($tipus, $tipusValids))  $errors[] = "Tipus d'incidència no vàlid.";
    if ($descripcio === '')               $errors[] = "La descripció és obligatòria.";

    if (!$errors) {
        Database::insert(
            "INSERT INTO incidencies
                (alumne_id, exemplar_id, tipus, descripcio, ha_de_pagar, import_pagament, registrat_per)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$alumne_id, $exemplar_id, $tipus, $descripcio, $ha_de_pagar, $import, Auth::id()]
        );

        /* Historial */
        Database::insert(
            "INSERT INTO historial (alumne_id, exemplar_id, accio, detalls, usuari_id) VALUES (?,?,?,?,?)",
            [$alumne_id, $exemplar_id, 'incidencia', "{$tipus}: {$descripcio}", Auth::id()]
        );

        if (in_array($tipus, ['perdua', 'extraviu'])) {
            Database::execute(
                "UPDATE exemplars SET estat = 'perdut', disponible = 0 WHERE id = ?",
                [$exemplar_id]
            );
            Database::execute(
                "UPDATE prestecs SET estat = 'perdut', estat_prestec = 'perdut'
                 WHERE exemplar_id = ? AND alumne_id = ? AND estat = 'actiu'",
                [$exemplar_id, $alumne_id]
            );
        }

        /* Generar albarà d'incidència */
        $ex_info = Database::fetchOne(
            "SELECT e.codi, l.titol, m.nom AS materia
             FROM exemplars e
             JOIN llibres l  ON e.llibre_id = l.id
             JOIN materies m ON l.materia_id = m.id
             WHERE e.id = ?",
            [$exemplar_id]
        );

        try {
            $fitxer_pdf = PdfGenerator::alaraIncidencia([
                'alumne_id'      => $alumne_id,
                'alumne'         => $alumne['nom'] . ' ' . $alumne['cognoms'],
                'classe'         => $alumne['classe_nom'],
                'tutor'          => $alumne['tutor_nom'] ?? '',
                'exemplar_codi'  => $ex_info['codi'],
                'exemplar_titol' => $ex_info['titol'],
                'materia'        => $ex_info['materia'],
            ]);

            $albaraId = Database::insert(
                "INSERT INTO albarans (alumne_id, tipus, fitxer_pdf, data) VALUES (?, 'incidencia', ?, NOW())",
                [$alumne_id, $fitxer_pdf]
            );

            if (!empty($alumne['email_familia'])) {
                try { MailSender::enviarAlbara($albaraId, $alumne['email_familia'], $alumne['nom'] . ' ' . $alumne['cognoms'], 'incidencia'); } catch (\Throwable $e) { }
            }
        } catch (\Throwable $e) { }

        header('Location: ' . BASE_URL . '/prestecs/prestecs.php?id=' . $alumne_id);
        exit;
    }
}

include __DIR__ . '/../views/prestecs_incidencia.php';
