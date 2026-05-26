<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

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

            // Generar username 3+3+3
            $uNom         = slug3($nom);
            $uCog1        = slug3($cognom1);
            $uCog2        = slug3($cognom2);
            $baseUsername = $uNom . $uCog1 . $uCog2;
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

include __DIR__ . '/../src/views/layout_top.php'; ?>

<div class="row g-4">

  <!-- Formulari -->
  <div class="col-md-5">
    <div class="card">
      <div class="card-header-bl"><i class="bi bi-upload"></i> Importar alumnes des de XML</div>
      <div class="card-body">

        <?php if (!empty($missatge)): ?>
          <div class="alert alert-success"><?= htmlspecialchars($missatge) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label fw-semibold">Fitxer XML</label>
            <input type="file" name="xml" class="form-control" accept=".xml" required>
            <div class="form-text">Format XML descrit a la dreta. Màx. 10 MB.</div>
          </div>
          <button class="btn btn-primary w-100">
            <i class="bi bi-upload"></i> Importar alumnes
          </button>
        </form>

        <hr>
        <div class="text-muted" style="font-size:.85rem">
          <i class="bi bi-info-circle me-1"></i>
          <strong>Comportament:</strong>
          <ul class="mt-1 mb-0 ps-3">
            <li>Si l'alumne <strong>ja existeix</strong> (per NIA o DNI), s'actualitzen les dades.</li>
            <li>Si la classe <strong>no existeix</strong> a la BD, el camp <code>classe_id</code> queda buit.</li>
            <li>Es genera automàticament l'<strong>email institucional</strong>:<br>
              <code>nom[3] + cognom1[3] + cognom2[3] @alu.edu.gva.es</code><br>
              Exemple: <em>Oscar Bravo Villada</em> → <code>oscbravil@alu.edu.gva.es</code>
            </li>
            <li>Es genera un <strong>CSV de contrasenyes</strong> per classe a <code>private/exports/</code>.</li>
          </ul>
        </div>

      </div>
    </div>
  </div>

  <!-- Documentació del format -->
  <div class="col-md-7">
    <div class="card">
      <div class="card-header-bl"><i class="bi bi-file-earmark-code"></i> Format del fitxer XML</div>
      <div class="card-body">

        <p class="mb-2" style="font-size:.9rem">
          Element arrel: <code>&lt;alumnes&gt;</code>. Cada alumne és un element
          <code>&lt;alumne /&gt;</code> amb els atributs següents:
        </p>

        <table class="table table-sm table-bordered" style="font-size:.82rem">
          <thead class="table-dark">
            <tr>
              <th>Atribut</th>
              <th>Obligatori</th>
              <th>Descripció</th>
              <th>Exemple</th>
            </tr>
          </thead>
          <tbody>
            <tr><td><code>nia</code></td><td>*</td><td>Número d'identificació de l'alumne</td><td><code>10000001</code></td></tr>
            <tr><td><code>dni</code></td><td>*</td><td>DNI o NIE de l'alumne</td><td><code>10000001A</code></td></tr>
            <tr class="table-warning"><td colspan="4" class="text-center fw-semibold" style="font-size:.78rem">* Cal almenys <code>nia</code> o <code>dni</code> (o tots dos)</td></tr>
            <tr><td><code>nom</code></td><td>Sí</td><td>Nom de pila</td><td><code>Oscar</code></td></tr>
            <tr><td><code>cognom1</code></td><td>Sí</td><td>Primer cognom</td><td><code>Bravo</code></td></tr>
            <tr><td><code>cognom2</code></td><td>No</td><td>Segon cognom</td><td><code>Villada</code></td></tr>
            <tr><td><code>classe</code></td><td>Sí</td><td>Nom exacte de la classe a la BD</td><td><code>1ASIX-A</code></td></tr>
            <tr><td><code>email_familia</code></td><td>No</td><td>Correu de la família (per als albarans)</td><td><code>familia@gmail.com</code></td></tr>
            <tr><td><code>telefon_familia</code></td><td>No</td><td>Telèfon de contacte</td><td><code>600111001</code></td></tr>
          </tbody>
        </table>

        <p class="fw-semibold mb-1" style="font-size:.88rem">Classes disponibles a la BD:</p>
        <div class="d-flex flex-wrap gap-1 mb-3">
          <?php
          $classesDisp = Database::fetchAll("SELECT nom FROM classes ORDER BY nom");
          foreach ($classesDisp as $cl):
          ?>
            <span class="codi-exemplar"><?= htmlspecialchars($cl['nom']) ?></span>
          <?php endforeach; ?>
        </div>

        <p class="fw-semibold mb-1" style="font-size:.88rem">Exemple de fitxer XML:</p>
        <pre class="bg-light border rounded p-3" style="font-size:.78rem;overflow-x:auto"><?= htmlspecialchars('<?xml version="1.0" encoding="UTF-8"?>
<alumnes>

  <alumne
    nia="10000001"
    dni="10000001A"
    nom="Oscar"
    cognom1="Bravo"
    cognom2="Villada"
    classe="1ASIX-A"
    email_familia="familia@gmail.com"
    telefon_familia="600111001"
  />

  <alumne
    nia="10000002"
    dni="10000002B"
    nom="Maria"
    cognom1="García"
    cognom2="López"
    classe="1ASIX-A"
    email_familia="maria.fam@gmail.com"
    telefon_familia="600111002"
  />

</alumnes>') ?></pre>

      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
