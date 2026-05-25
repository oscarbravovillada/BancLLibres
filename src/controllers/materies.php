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
    $accio = $_POST['accio'] ?? '';

    /* CREAR */
    if ($accio === 'crear') {
        $nom   = trim($_POST['nom']  ?? '');
        $codi  = strtoupper(trim($_POST['codi'] ?? ''));
        $tipus = $_POST['tipus'] ?? 'comuna';

        if ($nom === '' || $codi === '') {
            $errorMsg = 'Nom i codi són obligatoris.';
        } else {
            Database::execute(
                "INSERT INTO materies (nom, codi, tipus) VALUES (?,?,?)",
                [$nom, $codi, $tipus]
            );
            $missatge = "Matèria «{$nom}» creada correctament.";
        }
    }

    /* EDITAR */
    if ($accio === 'editar') {
        $id    = (int)($_POST['id'] ?? 0);
        $nom   = trim($_POST['nom']  ?? '');
        $codi  = strtoupper(trim($_POST['codi'] ?? ''));
        $tipus = $_POST['tipus'] ?? 'comuna';

        if ($id && $nom !== '' && $codi !== '') {
            Database::execute(
                "UPDATE materies SET nom=?, codi=?, tipus=? WHERE id=?",
                [$nom, $codi, $tipus, $id]
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

$materies = Database::fetchAll(
    "SELECT m.*, COUNT(l.id) AS num_llibres
     FROM materies m
     LEFT JOIN llibres l ON l.materia_id = m.id
     GROUP BY m.id
     ORDER BY m.nom"
);

include __DIR__ . '/../views/materies.php';
