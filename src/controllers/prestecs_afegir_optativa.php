<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/../../vendor/MailSender.php';
require_once __DIR__ . '/../../vendor/PdfGenerator.php';

Auth::requireLogin();

$titolPagina  = 'Afegir optativa';
$paginaActiva = 'prestecs';

$alumne_id = (int)($_GET['alumne_id'] ?? 0);
if (!$alumne_id) {
    header('Location: ' . BASE_URL . '/prestecs/index.php');
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
     JOIN llibres l  ON e.llibre_id = l.id
     JOIN materies m ON l.materia_id = m.id
     WHERE m.tipus = 'optativa'
       AND e.disponible = 1
       AND e.estat != 'perdut'
     ORDER BY m.nom, e.codi"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exemplar_id = (int)($_POST['exemplar_id'] ?? 0);

    if (!$exemplar_id) {
        header('Location: ' . BASE_URL . '/prestecs/prestecs.php?id=' . $alumne_id . '&error=sense_exemplar');
        exit;
    }

    Database::execute(
        "UPDATE exemplars SET alumne_id = ?, disponible = 0 WHERE id = ?",
        [$alumne_id, $exemplar_id]
    );

    Database::insert(
        "INSERT INTO prestecs (alumne_id, exemplar_id, estat, estat_prestec) VALUES (?, ?, 'actiu', 'actiu')",
        [$alumne_id, $exemplar_id]
    );

    $ex = Database::fetchOne(
        "SELECT e.codi, l.titol, m.nom AS materia, e.estat AS estat_inicial
         FROM exemplars e
         JOIN llibres l  ON e.llibre_id = l.id
         JOIN materies m ON l.materia_id = m.id
         WHERE e.id = ?",
        [$exemplar_id]
    );

    try {
        $fitxer_pdf = PdfGenerator::albaraPrestec([
            'alumne_id'   => $alumne_id,
            'alumne'      => $alumne['nom'] . ' ' . $alumne['cognoms'],
            'classe'      => $alumne['classe_nom'],
            'tutor'       => $alumne['tutor_nom'] ?? '',
            'lot_codi'    => '',
            'exemplars'   => [$ex],
            'responsable' => Auth::nom(),
        ]);

        $albaraId = Database::insert(
            "INSERT INTO albarans (alumne_id, tipus, fitxer_pdf, data) VALUES (?, 'prestec', ?, NOW())",
            [$alumne_id, $fitxer_pdf]
        );

        if (!empty($alumne['email_familia'])) {
            try { MailSender::enviarAlbara($albaraId, $alumne['email_familia'], $alumne['nom'] . ' ' . $alumne['cognoms'], 'prestec'); } catch (\Throwable $e) { }
        }
    } catch (\Throwable $e) { }

    header('Location: ' . BASE_URL . '/prestecs/prestecs.php?id=' . $alumne_id);
    exit;
}

include __DIR__ . '/../views/prestecs_afegir_optativa.php';
