<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/helpers/Database.php';
require_once __DIR__ . '/src/helpers/Auth.php';

Auth::start();
$newMode = empty($_SESSION['dark_mode']) ? 1 : 0;
$_SESSION['dark_mode'] = $newMode;

if (Auth::check()) {
    Database::execute(
        "UPDATE usuaris SET dark_mode = ? WHERE id = ?",
        [$newMode, Auth::id()]
    );
}

header('Content-Type: application/json');
echo json_encode(['dark' => (bool)$newMode]);
exit;
