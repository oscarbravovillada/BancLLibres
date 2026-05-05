<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = "Importar alumnes";
$paginaActiva = "import_alumnes";
$missatge     = "";
$errors       = [];

// Helper per netejar i agafar 3 lletres
function slug3($txt) {
    $txt = trim($txt);
    $txt = iconv('UTF-8', 'ASCII//TRANSLIT', $txt);
    $txt = strtolower($txt);
    $txt = preg_replace('/[^a-z0-9]/', '', $txt);
    return substr($txt, 0, 3);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml']) && $_FILES['xml']['error'] === UPLOAD_ERR_OK) {

    $xmlFile = $_FILES['xml']['tmp_name'];
    $xml     = @simplexml_load_file($xmlFile);

    if (!$xml) {
        $errors[] = "No s'ha pogut llegir l'XML d'alumnes.";
    } else {

        $count = 0;

        foreach ($xml->alumne as $a) {

            $nia            = trim((string)$a['nia']);
            $dni            = trim((string)$a['dni']);
            $nom            = trim((string)$a['nom']);
            $cognom1        = trim((string)$a['cognom1']);
            $cognom2        = trim((string)$a['cognom2']);
            $cognoms        = trim($cognom1 . ' ' . $cognom2);
            $classeNom      = trim((string)$a['classe']);
            $emailFamilia   = trim((string)$a['email_familia']);
            $telefonFamilia = trim((string)$a['telefon_familia']);

            if ($nia === '' && $dni === '') {
                $errors[] = "Alumne sense NIA ni DNI, s'ignora: $nom $cognoms";
                continue;
            }

            // Buscar classe per nom
            $classe = Database::fetchOne(
                "SELECT id FROM classes WHERE nom = ?",
                [$classeNom]
            );
            $classeId = $classe ? $classe['id'] : null;

            // Comprovem si ja existeix alumne per NIA o DNI
            $alumne = null;
            if ($nia !== '') {
                $alumne = Database::fetchOne("SELECT * FROM alumnes WHERE nia = ?", [$nia]);
            }
            if (!$alumne && $dni !== '') {
                $alumne = Database::fetchOne("SELECT * FROM alumnes WHERE dni = ?", [$dni]);
            }

            // Generar username 3+3
            $uNom   = slug3($nom);
            $uCog   = slug3($cognom1);
            $baseUsername = $uNom . $uCog;
            if ($baseUsername === '') {
                $baseUsername = strtolower($nia ?: $dni);
            }

            $username = $baseUsername;
            $i = 1;
            while (Database::fetchOne("SELECT id FROM usuaris WHERE username = ?", [$username])) {
                $username = $baseUsername . $i;
                $i++;
            }

            $emailInstitucional = $username . '@alu.edu.gva.es';

            // GENERAR SEMPRE CONTRASENYA PER AL CSV
            $random = substr(bin2hex(random_bytes(2)), 0, 3);
            $dniDigits = preg_replace('/\D/', '', $dni);
            $last3 = substr($dniDigits, -3);
            $plainPass = strtolower($random . $last3);
            $hashPass = password_hash($plainPass, PASSWORD_DEFAULT);

            // Si l'alumne ja existeix
            if ($alumne && $alumne['usuari_id']) {

                $usuariId = $alumne['usuari_id'];

                Database::execute(
                    "UPDATE usuaris
                     SET nom = ?, cognoms = ?, email = ?, document = ?, rol = 'alumne', actiu = 1
                     WHERE id = ?",
                    [$nom, $cognoms, $emailInstitucional, $dni ?: $alumne['dni'], $usuariId]
                );

            } else {

                // Crear usuari nou
                Database::execute(
                    "INSERT INTO usuaris (username, password, nom, cognoms, email, rol, actiu, document, telefon)
                     VALUES (?,?,?,?,?,'alumne',1,?,NULL)",
                    [$username, $hashPass, $nom, $cognoms, $emailInstitucional, $dni ?: null]
                );

                $usuariId = Database::lastInsertId();
            }

            // Insertar o actualitzar alumne
            if ($alumne) {
                Database::execute(
                    "UPDATE alumnes
                     SET usuari_id = ?, nom = ?, cognoms = ?, classe_id = ?, email_familia = ?, telefon_familia = ?,
                         nia = ?, dni = ?, email_institucional = ?, actiu = 1
                     WHERE id = ?",
                    [
                        $usuariId, $nom, $cognoms, $classeId,
                        $emailFamilia ?: $alumne['email_familia'],
                        $telefonFamilia ?: $alumne['telefon_familia'],
                        $nia ?: $alumne['nia'],
                        $dni ?: $alumne['dni'],
                        $emailInstitucional,
                        $alumne['id']
                    ]
                );
            } else {
                Database::execute(
                    "INSERT INTO alumnes
                     (usuari_id, nom, cognoms, classe_id, email_familia, telefon_familia, actiu, nia, dni, email_institucional)
                     VALUES (?,?,?,?,?,?,1,?,?,?)",
                    [
                        $usuariId, $nom, $cognoms, $classeId,
                        $emailFamilia ?: null,
                        $telefonFamilia ?: null,
                        $nia ?: null,
                        $dni ?: null,
                        $emailInstitucional
                    ]
                );
            }

            // --- GENERAR CSV PER CLASSE ---
            $csvDir = __DIR__ . '/../../private/exports/';
            if (!is_dir($csvDir)) mkdir($csvDir, 0777, true);

            $classeFitxer = preg_replace('/[^a-zA-Z0-9_-]/', '_', $classeNom);
            $csvPath = $csvDir . 'contrasenyes_' . $classeFitxer . '.csv';

            if (!file_exists($csvPath)) {
                file_put_contents($csvPath, "Nom complet;Email;Usuari;Contrasenya;DNI;Classe\n");
            }

            $line = sprintf(
                "%s %s %s;%s;%s;%s;%s;%s\n",
                $nom,
                $cognom1,
                $cognom2,
                $emailInstitucional,
                $username,
                $plainPass,
                $dni,
                $classeNom
            );

            file_put_contents($csvPath, $line, FILE_APPEND);

            $count++;
        }

        $missatge = "Importació d'alumnes completada. Alumnes processats: $count.";
    }
}

include __DIR__ . '/../views/alumnes_importar.php';
