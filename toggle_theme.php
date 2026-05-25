<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/helpers/Auth.php';

Auth::start();
$_SESSION['dark_mode'] = empty($_SESSION['dark_mode']) ? 1 : 0;

header('Content-Type: application/json');
echo json_encode(['dark' => (bool)$_SESSION['dark_mode']]);
exit;
