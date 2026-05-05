<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

if (Auth::rol() !== 'admin') {
    die("Accés denegat");
}

$dir = __DIR__ . '/../private/exports/';
$files = glob($dir . '*.csv');
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>Descarregar contrasenyes</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
body {
    background: #f5f7fa;
    font-family: "IBM Plex Sans", sans-serif;
}
.container-box {
    max-width: 700px;
    margin: 50px auto;
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.file-item {
    padding: 12px 18px;
    border-radius: 8px;
    background: #eef2f7;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.file-item:hover {
    background: #e2e8f0;
}
</style>

</head>
<body>

<div class="container-box">
    <h2 class="mb-4">
        <i class="bi bi-file-earmark-arrow-down"></i>
        Descarregar contrasenyes
    </h2>

    <?php if (empty($files)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            Encara no s'ha generat cap fitxer de contrasenyes.
        </div>
    <?php else: ?>
        <p class="text-muted">Selecciona un fitxer per descarregar-lo:</p>

        <?php foreach ($files as $file): ?>
            <div class="file-item">
                <strong><?= basename($file) ?></strong>
                <a class="btn btn-primary btn-sm"
                   href="<?= BASE_URL ?>/admin/download.php?file=<?= urlencode(basename($file)) ?>">
                    <i class="bi bi-download"></i> Descarregar
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>/index.php" class="btn btn-secondary mt-3">
        <i class="bi bi-arrow-left"></i> Tornar
    </a>
</div>

</body>
</html>
