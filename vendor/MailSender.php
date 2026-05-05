<?php
// src/helpers/MailSender.php
// Envia albarans per correu usant PHPMailer

require_once __DIR__ . '/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

class MailSender {

    /**
     * Envia un albarà en PDF al correu de la família.
     * Registra l'enviament a la taula enviaments_correu.
     *
     * @return bool  true si ok, false si error
     */
    public static function enviarAlbara(int $alaraId, string $emailDest, string $nomAlumne, string $tipusAlbara): bool {
        // Llegim la ruta del PDF
        $albara = Database::fetchOne(
            "SELECT fitxer_pdf FROM albarans WHERE id = ?", [$alaraId]
        );
        if (!$albara) return false;

        $rutaPdf = PDF_DIR . $albara['fitxer_pdf'];
        if (!file_exists($rutaPdf)) return false;

        $tipusText = [
            'prestec'   => 'Albarà de préstec',
            'devolucio' => 'Albarà de devolució',
            'incidencia'=> "Albarà d'incidència",
        ];
        $assumpte = ($tipusText[$tipusAlbara] ?? 'Document') . " — $nomAlumne";

        $mail = new PHPMailer(true);
        $resultat = 'ok';
        $missatgeError = null;

        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAIL_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($emailDest);
            $mail->Subject = $assumpte;
            $mail->Body    = self::cosCorreu($nomAlumne, $tipusAlbara);
            $mail->addAttachment($rutaPdf, basename($rutaPdf));

            $mail->send();
        } catch (MailException $e) {
            $resultat       = 'error';
            $missatgeError  = $e->getMessage();
        }

        // Registre a BD
        Database::insert(
            "INSERT INTO enviaments_correu (albara_id, email_dest, resultat, missatge_error)
             VALUES (?, ?, ?, ?)",
            [$alaraId, $emailDest, $resultat, $missatgeError]
        );

        return $resultat === 'ok';
    }

    private static function cosCorreu(string $nom, string $tipus): string {
        $tipusText = [
            'prestec'    => "s'ha realitzat el préstec de llibres",
            'devolucio'  => "s'ha registrat la devolució de llibres",
            'incidencia' => "s'ha registrat una incidència sobre un llibre",
        ];
        $accio = $tipusText[$tipus] ?? 'hi ha un document nou';

        return "Benvolguda família,

Us informem que per a l'alumne/a $nom {$accio} al Banc de Llibres de l'Institut.

Trobareu el document adjunt en format PDF.

Per a qualsevol consulta podeu contactar amb l'equip de coordinació pedagògica.

Atentament,
" . APP_NAME . "
" . self::CENTRE;
    }

    private const CENTRE = 'Institut Tecnològic';
}