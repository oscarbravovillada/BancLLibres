<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::requireLogin();

$titolPagina   = "Importar professorat";
$paginaActiva  = "import_professors";
$missatge      = "";
$errors        = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml']) && $_FILES['xml']['error'] === UPLOAD_ERR_OK) {

    $xmlFile = $_FILES['xml']['tmp_name'];
    $xml     = @simplexml_load_file($xmlFile);

    if (!$xml) {
        $errors[] = "No s'ha pogut llegir l'XML.";
    } else {

        $count = 0;

        foreach ($xml->docentes->docente as $d) {

            $nom       = trim((string)$d['nombre']);
            $cognom1   = trim((string)$d['apellido1']);
            $cognom2   = trim((string)$d['apellido2']);
            $cognoms   = trim($cognom1 . ' ' . $cognom2);
            $dni       = trim((string)$d['documento']);
            $email1    = trim((string)$d['email1']);
            $telefon   = trim((string)$d['telefono1']);

            if ($dni === '') {
                $errors[] = "Professor sense DNI, s'ignora: $nom $cognoms";
                continue;
            }

            // Si ja existeix usuari amb aquest DNI → actualitzem
            $usuari = Database::fetchOne(
                "SELECT * FROM usuaris WHERE document = ?",
                [$dni]
            );

            if ($usuari) {
                Database::execute(
                    "UPDATE usuaris
                     SET nom = ?, cognoms = ?, email = ?, telefon = ?, actiu = 1, rol = 'professor'
                     WHERE id = ?",
                    [$nom, $cognoms, $email1 ?: $usuari['email'], $telefon ?: $usuari['telefon'], $usuari['id']]
                );
            } else {
                // Generem username senzill: nom.cognom1 en minúscules
                $baseUsername = strtolower(
                    preg_replace('/[^a-z0-9]/', '',
                        iconv('UTF-8', 'ASCII//TRANSLIT', $nom)
                    ) . '.' .
                    preg_replace('/[^a-z0-9]/', '',
                        iconv('UTF-8', 'ASCII//TRANSLIT', $cognom1)
                    )
                );

                if ($baseUsername === '.') {
                    $baseUsername = strtolower($dni);
                }

                $username = $baseUsername;
                $i = 1;
                while (Database::fetchOne("SELECT id FROM usuaris WHERE username = ?", [$username])) {
                    $username = $baseUsername . $i;
                    $i++;
                }

                // Contrasenya aleatòria
                $plainPass = bin2hex(random_bytes(4));
                $hashPass  = password_hash($plainPass, PASSWORD_DEFAULT);

                Database::execute(
                    "INSERT INTO usuaris (username, password, nom, cognoms, email, rol, actiu, document, telefon)
                     VALUES (?,?,?,?,?,'professor',1,?,?)",
                    [$username, $hashPass, $nom, $cognoms, $email1 ?: null, $dni, $telefon ?: null]
                );
            }

            $count++;
        }

        $missatge = "Importació de professorat completada. Professors processats: $count.";
    }
}

include __DIR__ . '/../views/professors_importar.php';
