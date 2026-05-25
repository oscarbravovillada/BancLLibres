<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();
Auth::requireAdmin();

$titolPagina  = 'Usuaris';
$paginaActiva = 'usuaris';

$missatge = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accio = $_POST['accio'] ?? '';

    /* CREAR */
    if ($accio === 'crear') {
        $nom      = trim($_POST['nom']      ?? '');
        $cognoms  = trim($_POST['cognoms']  ?? '');
        $email    = trim($_POST['email']    ?? '');
        $rol      = $_POST['rol'] ?? 'professor';
        $password = trim($_POST['password'] ?? '');

        if ($nom === '' || $cognoms === '' || $email === '' || $password === '') {
            $errorMsg = 'Tots els camps marcats amb * són obligatoris.';
        } elseif (Database::fetchOne("SELECT id FROM usuaris WHERE email = ?", [$email])) {
            $errorMsg = "Ja existeix un usuari amb el correu {$email}.";
        } else {
            /* Generar username únic: nom.cognom1 */
            $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $cognoms)[0])) . '.'
                  . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nom));
            $username = $base;
            $i = 2;
            while (Database::fetchOne("SELECT id FROM usuaris WHERE username = ?", [$username])) {
                $username = $base . $i++;
            }

            Database::execute(
                "INSERT INTO usuaris (username, password, nom, cognoms, email, rol, actiu)
                 VALUES (?,?,?,?,?,?,1)",
                [$username, password_hash($password, PASSWORD_DEFAULT), $nom, $cognoms, $email, $rol]
            );
            $missatge = "Usuari {$nom} {$cognoms} creat. Username: {$username}";
        }
    }

    /* CANVIAR ROL */
    if ($accio === 'rol') {
        $id  = (int)($_POST['id']  ?? 0);
        $rol = $_POST['rol'] ?? '';
        if ($id && in_array($rol, ['admin', 'professor'])) {
            if ($id === Auth::id()) {
                $errorMsg = "No podeu canviar el vostre propi rol.";
            } else {
                Database::execute("UPDATE usuaris SET rol = ? WHERE id = ?", [$rol, $id]);
                $missatge = "Rol actualitzat.";
            }
        }
    }

    /* ACTIVAR / DESACTIVAR */
    if ($accio === 'toggle_actiu') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $id !== Auth::id()) {
            Database::execute(
                "UPDATE usuaris SET actiu = NOT actiu WHERE id = ?", [$id]
            );
            $missatge = "Estat de l'usuari actualitzat.";
        } else {
            $errorMsg = "No podeu desactivar el vostre propi compte.";
        }
    }

    /* RESET CONTRASENYA */
    if ($accio === 'reset_password') {
        $id       = (int)($_POST['id']       ?? 0);
        $password = trim($_POST['password']  ?? '');
        if ($id && strlen($password) >= 4) {
            Database::execute(
                "UPDATE usuaris SET password = ? WHERE id = ?",
                [password_hash($password, PASSWORD_DEFAULT), $id]
            );
            $missatge = "Contrasenya restablerta.";
        } else {
            $errorMsg = "La contrasenya ha de tenir almenys 4 caràcters.";
        }
    }

    if (!$errorMsg) {
        header('Location: ' . BASE_URL . '/usuaris.php?ok=1');
        exit;
    }
}

if (isset($_GET['ok'])) $missatge = 'Operació completada correctament.';

$usuaris = Database::fetchAll(
    "SELECT id, username, nom, cognoms, email, rol, actiu, created_at
     FROM usuaris ORDER BY cognoms, nom"
);

include __DIR__ . '/../views/usuaris.php';
