<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

if (Auth::rol() !== 'admin') {
    die("Accés denegat");
}

$file = basename($_GET['file'] ?? '');
$path = __DIR__ . '/../private/exports/' . $file;

if (!$file || !preg_match('/^[\w\-]+\.csv$/i', $file) || !file_exists($path)) {
    http_response_code(404);
    die("Fitxer no trobat");
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $file . '"');
readfile($path);
exit;
