<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/helpers/Database.php';
require_once __DIR__ . '/src/helpers/Auth.php';


Auth::start();
Auth::logout();

header('Location: ' . BASE_URL . '/login.php');
exit;
