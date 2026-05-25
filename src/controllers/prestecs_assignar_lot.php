<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Codis.php';
require_once __DIR__ . '/../../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/../../vendor/MailSender.php';
require_once __DIR__ . '/../../vendor/PdfGenerator.php';

Auth::requireLogin();

$titolPagina  = 'Assignar lot';
$paginaActiva = 'prestecs';

/* ============================
   0) VALIDAR ALUMNE
   ============================ */
$alumne_id = (int)($_GET['alumne_id'] ?? 0);
if (!$alumne_id) {
    header('Location: ' . BASE_URL . '/prestecs/index.php');
    exit;
}

/* ============================
   1) DADES DE L'ALUMNE
   ============================ */
$alumne = Database::fetchOne(
    "SELECT a.*,
            c.nom AS classe_nom,
            c.tutor_id,
            c.curs_id,
            cu.codi AS curs_codi,
            CONCAT(u.nom,' ',u.cognoms) AS tutor_nom
     FROM alumnes a
     JOIN classes c  ON a.classe_id = c.id
     JOIN cursos cu  ON c.curs_id = cu.id
     LEFT JOIN usuaris u ON c.tutor_id = u.id
     WHERE a.id = ?",
    [$alumne_id]
);

if (!$alumne) die("Alumne no trobat");

/* Si ja té lot → redirigir */
$lot_existent = Database::fetchOne("SELECT id FROM lots WHERE alumne_id = ?", [$alumne_id]);
if ($lot_existent) {
    header('Location: ' . BASE_URL . '/prestecs/prestecs.php?id=' . $alumne_id);
    exit;
}

/* ============================
   2) MATÈRIES I EXEMPLARS DISPONIBLES
   ============================ */
$materies = Database::fetchAll(
    "SELECT * FROM materies WHERE tipus = 'comuna' ORDER BY nom"
);

$exemplars_disponibles = [];
foreach ($materies as $m) {
    $exemplars_disponibles[$m['id']] = Database::fetchAll(
        "SELECT e.*, l.titol
         FROM exemplars e
         JOIN llibres l ON e.llibre_id = l.id
         WHERE l.materia_id = ?
           AND e.disponible = 1
           AND e.estat != 'perdut'
         ORDER BY e.codi",
        [$m['id']]
    );
}

/* ============================
   3) FORMULARI ENVIAT
   ============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $exemplarsSeleccionats = array_filter($_POST['exemplar'] ?? [], fn($v) => $v !== '' && $v > 0);

    if (empty($exemplarsSeleccionats)) {
        header('Location: ' . BASE_URL . '/prestecs/prestecs.php?id=' . $alumne_id . '&error=sense_exemplars');
        exit;
    }

    /* 3.1 Generar codi LOT amb la classe Codis */
    $codi_lot = Codis::lot($alumne['curs_codi']);

    /* 3.2 Crear lot */
    Database::insert(
        "INSERT INTO lots (alumne_id, codi, curs_id) VALUES (?, ?, ?)",
        [$alumne_id, $codi_lot, $alumne['curs_id']]
    );
    $lot_id = Database::lastInsertId();

    /* 3.3 Assignar exemplars i registrar préstecs */
    $exemplars_prestats = [];

    foreach ($exemplarsSeleccionats as $exemplar_id) {

        /* Obtenir codi de matèria per generar codi exemplar */
        $ex_info = Database::fetchOne(
            "SELECT e.id, e.estat, l.titol, m.nom AS materia, m.codi AS mat_codi
             FROM exemplars e
             JOIN llibres l  ON e.llibre_id = l.id
             JOIN materies m ON l.materia_id = m.id
             WHERE e.id = ?",
            [$exemplar_id]
        );

        /* Generar nou codi LOT per a l'exemplar */
        $nou_codi = Codis::exemplarLot($codi_lot, $ex_info['mat_codi']);

        /* Assignar exemplar al lot i actualitzar codi */
        Database::execute(
            "UPDATE exemplars SET alumne_id = ?, lot_id = ?, disponible = 0, codi = ? WHERE id = ?",
            [$alumne_id, $lot_id, $nou_codi, $exemplar_id]
        );

        /* Registrar préstec */
        Database::insert(
            "INSERT INTO prestecs (alumne_id, exemplar_id, lot_id, estat, estat_prestec) VALUES (?, ?, ?, 'actiu', 'actiu')",
            [$alumne_id, $exemplar_id, $lot_id]
        );

        /* Historial */
        Database::insert(
            "INSERT INTO historial (alumne_id, exemplar_id, accio, detalls, usuari_id) VALUES (?,?,?,?,?)",
            [$alumne_id, $exemplar_id, 'prestec', "Lot {$codi_lot} — {$ex_info['materia']}: {$nou_codi}", Auth::id()]
        );

        $exemplars_prestats[] = [
            'codi'          => $nou_codi,
            'titol'         => $ex_info['titol'],
            'materia'       => $ex_info['materia'],
            'estat_inicial' => $ex_info['estat'],
        ];
    }

    /* 3.4 Generar albarà PDF */
    $dades_pdf = [
        'alumne_id' => $alumne_id,
        'alumne'    => $alumne['nom'] . ' ' . $alumne['cognoms'],
        'classe'    => $alumne['classe_nom'],
        'tutor'     => $alumne['tutor_nom'] ?? '',
        'lot_codi'  => $codi_lot,
        'exemplars' => $exemplars_prestats,
    ];

    $fitxer_pdf = PdfGenerator::albaraPrestec($dades_pdf);

    $albaraId = Database::insert(
        "INSERT INTO albarans (alumne_id, tipus, fitxer_pdf, data) VALUES (?, 'prestec', ?, NOW())",
        [$alumne_id, $fitxer_pdf]
    );

    /* 3.5 Enviar correu */
    if (!empty($alumne['email_familia'])) {
        try { MailSender::enviarAlbara($albaraId, $alumne['email_familia'], $alumne['nom'] . ' ' . $alumne['cognoms'], 'prestec'); } catch (\Throwable $e) { }
    }

    header('Location: ' . BASE_URL . '/prestecs/prestecs.php?id=' . $alumne_id);
    exit;
}

include __DIR__ . '/../views/prestecs_assignar_lot.php';
