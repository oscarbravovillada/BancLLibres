<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Codis.php';

Auth::requireLogin();

$titolPagina  = 'Exemplars';
$paginaActiva = 'exemplars';

// FILTRES
$filtreLlibre = (int)($_GET['llibre_id'] ?? 0);
$filtreEstat  = $_GET['estat'] ?? '';
$filtreDisp   = isset($_GET['disponible']) ? (int)$_GET['disponible'] : -1;

// ACCIONS POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accio = $_POST['accio'] ?? '';

    // CREAR EXEMPLARS
    if ($accio === 'crear') {
        $llibreId = (int)($_POST['llibre_id'] ?? 0);
        $estat    = $_POST['estat'] ?? 'nou';
        $desp     = trim($_POST['desperfectes'] ?? '');
        $quant    = max(1, (int)($_POST['quantitat'] ?? 1));

        $llibre = Database::fetchOne(
            "SELECT l.*, m.codi AS mat_codi, m.tipus AS mat_tipus, cu.codi AS curs_codi
             FROM llibres l
             JOIN materies m ON l.materia_id=m.id
             JOIN cursos cu ON l.curs_id=cu.id
             WHERE l.id=?",
            [$llibreId]
        );

        if ($llibre) {
            for ($i = 0; $i < $quant; $i++) {
                if ($llibre['mat_tipus'] === 'optativa') {
                    $codi = Codis::exemplarOptativa($llibre['curs_codi'], $llibre['mat_codi']);
                } else {
                    $prefix = "STOCK-{$llibre['curs_codi']}-{$llibre['mat_codi']}-";
                    $row = Database::fetchOne("SELECT COUNT(*) AS c FROM exemplars WHERE codi LIKE ?", [$prefix.'%']);
                    $codi = $prefix . str_pad(($row['c'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
                }

                Database::insert(
                    "INSERT INTO exemplars (codi, llibre_id, estat, desperfectes)
                     VALUES (?,?,?,?)",
                    [$codi, $llibreId, $estat, $desp]
                );
            }
        }

        header('Location: ' . BASE_URL . '/exemplars/exemplars.php?llibre_id=' . $llibreId);
        exit;
    }

    // EDITAR EXEMPLAR
    if ($accio === 'editar') {
        $id    = (int)($_POST['id'] ?? 0);
        $estat = $_POST['estat'] ?? 'bo';
        $desp  = trim($_POST['desperfectes'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        Database::execute(
            "UPDATE exemplars SET estat=?, desperfectes=?, notes=? WHERE id=?",
            [$estat, $desp, $notes, $id]
        );

        header('Location: ' . BASE_URL . '/exemplars/exemplars.php?llibre_id=' . $filtreLlibre);
        exit;
    }
}

// CONSULTA EXEMPLARS
$where = ['1=1'];
$params = [];

if ($filtreLlibre) { $where[] = 'e.llibre_id=?'; $params[] = $filtreLlibre; }
if ($filtreEstat)  { $where[] = 'e.estat=?';     $params[] = $filtreEstat; }
if ($filtreDisp >= 0) { $where[] = 'e.disponible=?'; $params[] = $filtreDisp; }

$exemplars = Database::fetchAll(
    "SELECT e.*, l.titol AS ll_titol, m.nom AS mat_nom, m.codi AS mat_codi,
            m.tipus AS mat_tipus, cu.codi AS curs_codi
     FROM exemplars e
     JOIN llibres l ON e.llibre_id=l.id
     JOIN materies m ON l.materia_id=m.id
     JOIN cursos cu ON l.curs_id=cu.id
     WHERE " . implode(' AND ', $where) . "
     ORDER BY e.codi",
    $params
);

$llibreFiltrat = $filtreLlibre
    ? Database::fetchOne("SELECT l.*, m.nom AS mat_nom FROM llibres l JOIN materies m ON l.materia_id=m.id WHERE l.id=?", [$filtreLlibre])
    : null;

$totsLlibres = Database::fetchAll(
    "SELECT l.*, m.nom AS mat_nom
     FROM llibres l
     JOIN materies m ON l.materia_id=m.id
     WHERE l.actiu=1
     ORDER BY m.nom, l.titol"
);

include __DIR__ . '/../views/exemplars.php';
