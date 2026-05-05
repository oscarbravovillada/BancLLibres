<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

if (Auth::rol() !== 'admin') {
    die("Accés denegat");
}

$file = basename($_GET['file'] ?? '');
$path = __DIR__ . '/../private/exports/' . $file;

if (!file_exists($path)) {
    die("Fitxer no trobat");
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $file . '"');
readfile($path);
exit;
