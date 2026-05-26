<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

$fitxer = basename($_GET['f'] ?? '');
$ruta = PDF_DIR . $fitxer;

if (!$fitxer || !preg_match('/^[\w\-]+\.pdf$/i', $fitxer) || !file_exists($ruta)) {
    http_response_code(404);
    die("PDF no trobat");
}

header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"$fitxer\"");
readfile($ruta);
exit;
