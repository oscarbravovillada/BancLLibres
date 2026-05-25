<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina = 'Classes';
$paginaActiva = 'classes';
$missatge = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAdmin();
    $accio = $_POST['accio'] ?? '';

    /* ── NOVA CLASSE ─────────────────────────────────────────── */
    if ($accio === 'nova') {
        $etapa     = $_POST['etapa']     ?? '';
        $tutor_id  = (int)($_POST['tutor_id'] ?? 0) ?: null;
        $curs_codi = '';
        $curs_nom  = '';
        $class_nom = '';

        if ($etapa === 'eso') {
            $num  = (int)($_POST['curs_num'] ?? 0);
            $grup = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['grup'] ?? ''));
            $map_codi = [1=>'1ESO',2=>'2ESO',3=>'3ESO',4=>'4ESO'];
            $map_nom  = [1=>'1r ESO',2=>'2n ESO',3=>'3r ESO',4=>'4t ESO'];
            if (!isset($map_codi[$num]) || $grup === '') {
                $errorMsg = 'Cal seleccionar el curs i el grup.';
            } else {
                $curs_codi = $map_codi[$num];
                $curs_nom  = $map_nom[$num];
                $class_nom = $curs_codi . '-' . $grup;
            }

        } elseif (in_array($etapa, ['cfgb','cfgm','cfgs'])) {
            $prefix    = strtoupper($etapa);
            $cicle     = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['codi_cicle'] ?? ''));
            $grup      = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['grup']       ?? ''));
            $nom_c     = trim($_POST['nom_complet'] ?? '');
            if ($cicle === '' || $grup === '') {
                $errorMsg = 'Cal indicar el codi del cicle i el grup.';
            } else {
                $curs_codi = $prefix . '-' . $cicle;
                $curs_nom  = $nom_c ?: ($prefix . ' ' . $cicle);
                $class_nom = $curs_codi . '-' . $grup;
            }

        } elseif ($etapa === 'altre') {
            $class_nom = trim($_POST['nom_classe'] ?? '');
            $curs_nom  = trim($_POST['nom_curs']   ?? '');
            $curs_codi = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $class_nom));
            if ($class_nom === '' || $curs_nom === '') {
                $errorMsg = 'Cal indicar el nom de la classe i el nom oficial del curs.';
            }
        } else {
            $errorMsg = 'Etapa no vàlida.';
        }

        if (!$errorMsg) {
            // Comprovar que la classe no existeix ja
            $existent = Database::fetchOne(
                "SELECT id FROM classes WHERE nom = ? AND curs_escolar = ?",
                [$class_nom, ANY_ESCOLAR]
            );
            if ($existent) {
                $errorMsg = "La classe «{$class_nom}» ja existeix aquest curs.";
            } else {
                // Trobar o crear el curs
                $curs = Database::fetchOne("SELECT id FROM cursos WHERE codi = ?", [$curs_codi]);
                if ($curs) {
                    $curs_id = $curs['id'];
                } else {
                    Database::execute(
                        "INSERT INTO cursos (codi, nom, actiu) VALUES (?, ?, 1)",
                        [$curs_codi, $curs_nom]
                    );
                    $curs_id = Database::lastInsertId();
                }

                // Crear la classe
                Database::execute(
                    "INSERT INTO classes (curs_id, nom, tutor_id, curs_escolar) VALUES (?, ?, ?, ?)",
                    [$curs_id, $class_nom, $tutor_id, ANY_ESCOLAR]
                );
                $missatge = "Classe «{$class_nom}» creada correctament.";
            }
        }
    }

    /* ── ELIMINAR CLASSE ─────────────────────────────────────── */
    if ($accio === 'eliminar') {
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
} // end POST

$tutors = Database::fetchAll(
    "SELECT id, CONCAT(nom,' ',cognoms) AS nom_complet
     FROM usuaris WHERE rol IN ('professor','admin') AND actiu = 1
     ORDER BY cognoms, nom"
);

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
