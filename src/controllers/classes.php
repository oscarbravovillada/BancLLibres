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
            CONCAT(u.nom,' ',u.cognoms) AS tutor_nom,
            (SELECT COUNT(*) FROM alumnes a WHERE a.classe_id = c.id AND a.actiu = 1) AS num_alumnes
     FROM classes c
     JOIN cursos cu ON c.curs_id = cu.id
     LEFT JOIN usuaris u ON c.tutor_id = u.id
     WHERE c.curs_escolar = ?
     ORDER BY cu.codi, c.nom",
    [ANY_ESCOLAR]
);

// Agrupar per etapa educativa
$grups = [
    'ESO'             => ['label' => 'ESO',            'color' => '#1565c0', 'icon' => 'bi-mortarboard',    'classes' => []],
    'CFGB'            => ['label' => 'Graus Bàsics',   'color' => '#e65100', 'icon' => 'bi-journal-text',   'classes' => []],
    'CFGM'            => ['label' => 'Graus Mitjans',  'color' => '#2e7d32', 'icon' => 'bi-journal-richtext','classes' => []],
    'CFGS'            => ['label' => 'Graus Superiors','color' => '#4a148c', 'icon' => 'bi-award',           'classes' => []],
    'ALTRES'          => ['label' => 'Altres',          'color' => '#455a64', 'icon' => 'bi-grid',           'classes' => []],
];

foreach ($classes as $c) {
    $codi = strtoupper($c['curs_codi']);
    if (str_contains($codi, 'ESO')) {
        $grups['ESO']['classes'][] = $c;
    } elseif (str_starts_with($codi, 'CFGB')) {
        $grups['CFGB']['classes'][] = $c;
    } elseif (str_starts_with($codi, 'CFGM')) {
        $grups['CFGM']['classes'][] = $c;
    } elseif (str_starts_with($codi, 'CFGS') || str_contains($codi, 'ASIX') || str_contains($codi, 'ASIR')) {
        $grups['CFGS']['classes'][] = $c;
    } else {
        $grups['ALTRES']['classes'][] = $c;
    }
}
// Eliminar grups buits
$grups = array_filter($grups, fn($g) => !empty($g['classes']));

include __DIR__ . '/../views/classes.php';
