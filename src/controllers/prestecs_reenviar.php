<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/../../vendor/MailSender.php';
require_once __DIR__ . '/../../vendor/PdfGenerator.php';

Auth::requireLogin();

$titolPagina  = 'Reenviar documents';
$paginaActiva = 'prestecs';

$alumne_id = (int)($_GET['alumne_id'] ?? 0);
if (!$alumne_id) {
    header('Location: ' . BASE_URL . '/prestecs/index.php');
    exit;
}

Auth::requireAccessToAlumne($alumne_id);

$alumne = Database::fetchOne(
    "SELECT a.*, c.nom AS classe_nom FROM alumnes a JOIN classes c ON a.classe_id = c.id WHERE a.id = ?",
    [$alumne_id]
);
if (!$alumne) die("Alumne no trobat");

$missatge = '';
$errorMsg = '';

/* Reenviar un albarà concret */
if (isset($_GET['reenviar'])) {
    $albaraId = (int)$_GET['reenviar'];

    if (empty($alumne['email_familia'])) {
        $errorMsg = "Aquest alumne no té correu de família configurat.";
    } else {
        $albara = Database::fetchOne("SELECT tipus FROM albarans WHERE id = ? AND alumne_id = ?", [$albaraId, $alumne_id]);
        if ($albara) {
            try {
                MailSender::enviarAlbara(
                    $albaraId,
                    $alumne['email_familia'],
                    $alumne['nom'] . ' ' . $alumne['cognoms'],
                    $albara['tipus']
                );
                $missatge = "Document reenviat a {$alumne['email_familia']}.";
            } catch (\Throwable $e) {
                $errorMsg = "No s'ha pogut enviar el correu. Comproveu la configuració del servidor de correu.";
            }
        }
    }
}

$albarans = Database::fetchAll(
    "SELECT * FROM albarans WHERE alumne_id = ? ORDER BY data DESC",
    [$alumne_id]
);

include __DIR__ . '/../views/prestecs_reenviar.php';
