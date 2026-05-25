<?php
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Database.php';

require_once __DIR__ . '/../../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/../../vendor/MailSender.php';
require_once __DIR__ . '/../../vendor/PdfGenerator.php';

Auth::requireLogin();




$alumne_id = intval($_GET['alumne_id'] ?? 0);
if (!$alumne_id) die("Alumne no especificat");

/* Albarans de l'alumne */
$albarans = Database::fetchAll(
    "SELECT * FROM albarans WHERE alumne_id = ? ORDER BY data DESC",
    [$alumne_id]
);

/* Reenviament */
if (isset($_GET['reenviar'])) {

    $albaraId = intval($_GET['reenviar']);

    $alumne = Database::fetchOne(
        "SELECT nom, cognoms, email_familia FROM alumnes WHERE id = ?",
        [$alumne_id]
    );

    MailSender::enviarAlbara(
        $albaraId,
        $alumne['email_familia'],
        $alumne['nom'] . ' ' . $alumne['cognoms'],
        Database::fetchOne("SELECT tipus FROM albarans WHERE id = ?", [$albaraId])['tipus']
    );

    header("Location: prestecs_reenviar.php?alumne_id=" . $alumne_id);
    exit;
}

include __DIR__ . '/../views/prestecs_reenviar.php';
