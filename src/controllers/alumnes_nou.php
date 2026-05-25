<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Nou alumne';
$paginaActiva = 'alumnes';

$classes = Database::fetchAll(
    "SELECT id, nom FROM classes WHERE curs_escolar = ? ORDER BY nom",
    [ANY_ESCOLAR]
);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom           = trim($_POST['nom'] ?? '');
    $cognoms       = trim($_POST['cognoms'] ?? '');
    $email_familia = trim($_POST['email_familia'] ?? '');
    $classe_id     = (int)($_POST['classe_id'] ?? 0);

    if ($nom === '' || $cognoms === '' || !$classe_id) {
        $errors[] = 'Nom, cognoms i classe són obligatoris.';
    }

    if (!$errors) {

        // 1) Crear alumne
        Database::execute(
            "INSERT INTO alumnes (nom, cognoms, email_familia, classe_id, actiu)
             VALUES (?,?,?,?,1)",
            [$nom, $cognoms, $email_familia, $classe_id]
        );

        $alumne_id = Database::lastInsertId();

        // 2) Obtenir dades de la classe (inclou curs_id)
        $classe = Database::fetchOne(
            "SELECT c.*, cu.codi AS curs_codi
             FROM classes c
             JOIN cursos cu ON cu.id = c.curs_id
             WHERE c.id = ?",
            [$classe_id]
        );

        // 3) Crear lot automàtic
        $lotCodi = 'LOT-' . $classe['codi'] . '-' . str_pad($alumne_id, 3, '0', STR_PAD_LEFT);

        Database::query(
            "INSERT INTO lots (alumne_id, codi, curs_id)
             VALUES (?, ?, ?)",
            [$alumne_id, $lotCodi, $classe['curs_id']]
        );

        $lot_id = Database::lastInsertId();

        // -------------------------------
        // 4) ASSIGNACIÓ AUTOMÀTICA DE LLIBRES
        // -------------------------------

        // 4A) OBLIGATORIS
        $llibresObligatoris = Database::fetchAll(
            "SELECT id FROM llibres 
             WHERE curs_id = ? 
             AND tipus = 'obligatori'
             AND actiu = 1",
            [$classe['curs_id']]
        );

        // 4B) OPTATIVES (ESO)
        $llibresOptatives = Database::fetchAll(
            "SELECT id FROM llibres 
             WHERE curs_id = ? 
             AND tipus = 'optativa'
             AND actiu = 1",
            [$classe['curs_id']]
        );

        // 4C) MÒDULS FP (ASIX, SMX, APSD…)
        $llibresModuls = Database::fetchAll(
            "SELECT id FROM llibres 
             WHERE curs_id = ? 
             AND tipus = 'modul'
             AND actiu = 1",
            [$classe['curs_id']]
        );

        // Funció interna per assignar exemplars
        $assignar = function($llibre_id) use ($lot_id, $alumne_id) {

            $exemplar = Database::fetchOne(
                "SELECT id FROM exemplars 
                 WHERE llibre_id = ? 
                 AND disponible = 1 
                 LIMIT 1",
                [$llibre_id]
            );

            if ($exemplar) {
                Database::query(
                    "UPDATE exemplars 
                     SET disponible = 0, lot_id = ?, alumne_id = ?
                     WHERE id = ?",
                    [$lot_id, $alumne_id, $exemplar['id']]
                );
            }
        };

        // Assignar obligatoris
        foreach ($llibresObligatoris as $l) {
            $assignar($l['id']);
        }

        // Assignar optatives
        foreach ($llibresOptatives as $l) {
            $assignar($l['id']);
        }

        // Assignar mòduls FP
        foreach ($llibresModuls as $l) {
            $assignar($l['id']);
        }

        // -------------------------------

        // 5) Redirigir
        header('Location: ' . BASE_URL . '/alumnes/llista.php?classe_id=' . $classe_id);
        exit;
    }
}

include __DIR__ . '/../views/alumnes_nou.php';
