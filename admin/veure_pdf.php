<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

$fitxer = $_GET['f'] ?? '';
$ruta = PDF_DIR . $fitxer;

if (!$fitxer || !file_exists($ruta)) {
    die("PDF no trobat");
}

header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"$fitxer\"");
readfile($ruta);
exit;
