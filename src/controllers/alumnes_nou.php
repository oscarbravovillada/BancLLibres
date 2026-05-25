<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Nou alumne';
$paginaActiva = 'alumnes';

function slug3nou(string $txt): string {
    $txt = iconv('UTF-8', 'ASCII//TRANSLIT', trim($txt)) ?: $txt;
    return substr(preg_replace('/[^a-z0-9]/', '', strtolower($txt)), 0, 3);
}

function generarEmailAlumne(string $nom, string $cognoms): string {
    $parts   = explode(' ', $cognoms, 2);
    $cognom1 = $parts[0] ?? '';
    $cognom2 = $parts[1] ?? '';
    $base    = slug3nou($nom) . slug3nou($cognom1) . slug3nou($cognom2);
    if ($base === '') $base = 'alumne';

    $username = $base;
    $i = 1;
    while (Database::fetchOne("SELECT id FROM alumnes WHERE email_institucional = ?",
           [$username . '@alu.edu.gva.es'])) {
        $username = $base . $i++;
    }
    return $username . '@alu.edu.gva.es';
}

$classes = Database::fetchAll(
    "SELECT id, nom FROM classes WHERE curs_escolar = ? ORDER BY nom",
    [ANY_ESCOLAR]
);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom           = trim($_POST['nom']           ?? '');
    $cognoms       = trim($_POST['cognoms']       ?? '');
    $email_familia = trim($_POST['email_familia'] ?? '');
    $telefon       = trim($_POST['telefon']       ?? '');
    $classe_id     = (int)($_POST['classe_id']    ?? 0);

    if ($nom === '' || $cognoms === '' || !$classe_id) {
        $errors[] = 'Nom, cognoms i classe són obligatoris.';
    }

    if (!$errors) {
        $emailInst = generarEmailAlumne($nom, $cognoms);
        Database::execute(
            "INSERT INTO alumnes (nom, cognoms, email_familia, telefon_familia, classe_id, actiu, email_institucional)
             VALUES (?,?,?,?,?,1,?)",
            [$nom, $cognoms, $email_familia, $telefon, $classe_id, $emailInst]
        );
        header('Location: ' . BASE_URL . '/alumnes/llista.php?classe_id=' . $classe_id);
        exit;
    }
}

include __DIR__ . '/../views/alumnes_nou.php';
