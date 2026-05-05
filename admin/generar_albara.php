<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Auth.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../vendor/PdfGenerator.php';   // ← RUTA CORRECTA

Auth::requireLogin();

if (Auth::rol() !== 'admin') {
    die("Accés denegat");
}

$tipus = $_GET['tipus'] ?? '';
$alumneId = intval($_GET['alumne'] ?? 0);

if (!$tipus || !$alumneId) {
    die("Falten paràmetres");
}

// Carregar dades de l’alumne
$alumne = Database::fetchOne(
    "SELECT a.*, c.nom AS classe_nom
     FROM alumnes a
     LEFT JOIN classes c ON a.classe_id = c.id
     WHERE a.id = ?",
    [$alumneId]
);

if (!$alumne) die("Alumne no trobat");

$dades = [
    'alumne_id' => $alumneId,
    'alumne'    => $alumne['nom'] . ' ' . $alumne['cognoms'],
    'classe'    => $alumne['classe_nom'],
    'data'      => date('d/m/Y H:i'),
];

// Generar PDF segons tipus
switch ($tipus) {
    case 'prestec':
        $fitxer = PdfGenerator::albaraPrestec($dades);
       $ruta = PDF_DIR . $fitxer;

header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"$fitxer\"");
header("Content-Length: " . filesize($ruta));

readfile($ruta);
exit;

}

// Mostrar PDF directament
header("Location: " . BASE_URL . "/admin/veure_pdf.php?f=" . $fitxer);exit;
