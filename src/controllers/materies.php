<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Matèries';
$paginaActiva = 'materies';
$missatge = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAdmin();
    $accio = $_POST['accio'] ?? '';

    /* CREAR */
    if ($accio === 'crear') {
        $nom     = trim($_POST['nom']  ?? '');
        $codi    = strtoupper(trim($_POST['codi'] ?? ''));
        $tipus   = in_array($_POST['tipus'] ?? '', ['comuna','optativa']) ? $_POST['tipus'] : 'comuna';
        $curs_id = (int)($_POST['curs_id'] ?? 0) ?: null;

        if ($nom === '' || $codi === '') {
            $errorMsg = 'Nom i codi són obligatoris.';
        } else {
            Database::execute(
                "INSERT INTO materies (nom, codi, tipus, curs_id) VALUES (?,?,?,?)",
                [$nom, $codi, $tipus, $curs_id]
            );
            $missatge = "Matèria «{$nom}» creada.";
        }
    }

    /* EDITAR */
    if ($accio === 'editar') {
        $id      = (int)($_POST['id'] ?? 0);
        $nom     = trim($_POST['nom']  ?? '');
        $codi    = strtoupper(trim($_POST['codi'] ?? ''));
        $tipus   = in_array($_POST['tipus'] ?? '', ['comuna','optativa']) ? $_POST['tipus'] : 'comuna';
        $curs_id = (int)($_POST['curs_id'] ?? 0) ?: null;

        if ($id && $nom !== '' && $codi !== '') {
            Database::execute(
                "UPDATE materies SET nom=?, codi=?, tipus=?, curs_id=? WHERE id=?",
                [$nom, $codi, $tipus, $curs_id, $id]
            );
            $missatge = "Matèria actualitzada.";
        }
    }

    /* ELIMINAR */
    if ($accio === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $usos = Database::fetchOne(
                "SELECT COUNT(*) n FROM llibres WHERE materia_id = ?", [$id]
            )['n'] ?? 0;
            if ($usos > 0) {
                $errorMsg = "No es pot eliminar: hi ha {$usos} llibre(s) que usen aquesta matèria.";
            } else {
                $nom = Database::fetchOne("SELECT nom FROM materies WHERE id=?", [$id])['nom'] ?? '';
                Database::execute("DELETE FROM materies WHERE id=?", [$id]);
                $missatge = "Matèria «{$nom}» eliminada.";
            }
        }
    }

    if (!$errorMsg) {
        header('Location: ' . BASE_URL . '/materies/materies.php' . ($missatge ? '?ok=1' : ''));
        exit;
    }
}

if (isset($_GET['ok'])) $missatge = 'Operació completada correctament.';

$cursos = Database::fetchAll("SELECT id, codi, nom FROM cursos ORDER BY codi");

$materies = Database::fetchAll(
    "SELECT m.*,
            cu.codi AS curs_codi,
            cu.nom  AS curs_nom,
            COUNT(DISTINCT l.id) AS num_llibres,
            (SELECT cu2.codi
             FROM cursos cu2
             JOIN llibres l2 ON l2.curs_id = cu2.id
             WHERE l2.materia_id = m.id AND l2.actiu = 1
             GROUP BY cu2.codi ORDER BY COUNT(*) DESC LIMIT 1
            ) AS derived_curs
     FROM materies m
     LEFT JOIN cursos cu ON cu.id = m.curs_id
     LEFT JOIN llibres l ON l.materia_id = m.id AND l.actiu = 1
     GROUP BY m.id
     ORDER BY cu.codi, m.nom"
);

// ── Agrupar per etapa ──────────────────────────────────────────────────
$grups = [
    'ESO'    => ['label' => 'ESO',            'color' => '#1565c0', 'icon' => 'bi-mortarboard',     'materies' => []],
    'CFGB'   => ['label' => 'Graus Bàsics',   'color' => '#e65100', 'icon' => 'bi-journal-text',    'materies' => []],
    'CFGM'   => ['label' => 'Graus Mitjans',  'color' => '#2e7d32', 'icon' => 'bi-journal-richtext', 'materies' => []],
    'CFGS'   => ['label' => 'Graus Superiors','color' => '#4a148c', 'icon' => 'bi-award',            'materies' => []],
    'OPT'    => ['label' => 'Optatives',      'color' => '#0277bd', 'icon' => 'bi-star',             'materies' => []],
    'ALTRES' => ['label' => 'Sense assignar', 'color' => '#78909c', 'icon' => 'bi-question-circle',  'materies' => []],
];

foreach ($materies as $m) {
    // El codi de referència per agrupar: curs_id > derived_curs
    $ref = strtoupper($m['curs_codi'] ?? $m['derived_curs'] ?? '');

    if ($m['tipus'] === 'optativa') {
        $grups['OPT']['materies'][] = $m;
    } elseif (str_contains($ref, 'ESO')) {
        $grups['ESO']['materies'][] = $m;
    } elseif (str_starts_with($ref, 'CFGB')) {
        $grups['CFGB']['materies'][] = $m;
    } elseif (str_starts_with($ref, 'CFGM')) {
        $grups['CFGM']['materies'][] = $m;
    } elseif (str_starts_with($ref, 'CFGS') || str_contains($ref, 'ASIX') || str_contains($ref, 'ASIR')) {
        $grups['CFGS']['materies'][] = $m;
    } else {
        $grups['ALTRES']['materies'][] = $m;
    }
}

$grups = array_filter($grups, fn($g) => !empty($g['materies']));

include __DIR__ . '/../views/materies.php';
