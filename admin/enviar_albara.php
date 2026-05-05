<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Auth.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/MailSender.php';

Auth::requireLogin();

if (Auth::rol() !== 'admin') {
    die("Accés denegat");
}

$fitxer = $_GET['albara'] ?? '';
$alumneId = intval($_GET['alumne'] ?? 0);

if (!$fitxer || !$alumneId) {
    die("Falten paràmetres");
}

$alumne = Database::fetchOne(
    "SELECT nom, cognoms, email_familia FROM alumnes WHERE id = ?",
    [$alumneId]
);

if (!$alumne) die("Alumne no trobat");

$email = $alumne['email_familia'];
$nom = $alumne['nom'] . ' ' . $alumne['cognoms'];

// Determinar tipus segons nom del fitxer
if (str_starts_with($fitxer, 'prestec')) $tipus = 'prestec';
elseif (str_starts_with($fitxer, 'devolucio')) $tipus = 'devolucio';
else $tipus = 'incidencia';

// Enviar correu
$ok = MailSender::enviarAlbara($alumneId, $email, $nom, $tipus);

if ($ok) {
    echo "<h2>Correu enviat correctament a $email</h2>";
} else {
    echo "<h2>Error enviant el correu</h2>";
}
