<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Auth.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/../vendor/PdfGenerator.php';
require_once __DIR__ . '/../vendor/MailSender.php';

Auth::requireLogin();

$titolPagina  = "Registrar incidència";
$paginaActiva = "prestecs";

$alumne_id = (int)($_GET['alumne_id'] ?? 0);
if (!$alumne_id) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

Auth::requireAccessToAlumne($alumne_id);

$alumne = Database::fetchOne(
    "SELECT a.*, c.nom AS classe_nom, CONCAT(u.nom,' ',u.cognoms) AS tutor_nom
     FROM alumnes a
     JOIN classes c ON a.classe_id = c.id
     LEFT JOIN usuaris u ON c.tutor_id = u.id
     WHERE a.id = ?",
    [$alumne_id]
);
if (!$alumne) die("Alumne no trobat");

$exemplars = Database::fetchAll(
    "SELECT e.id, e.codi, l.titol, m.nom AS materia_nom
     FROM exemplars e
     JOIN llibres l ON e.llibre_id = l.id
     JOIN materies m ON l.materia_id = m.id
     WHERE e.alumne_id = ?
     ORDER BY m.nom",
    [$alumne_id]
);

$errors   = [];
$missatge = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exemplar_id = (int)($_POST['exemplar_id'] ?? 0);
    $tipus       = trim($_POST['tipus'] ?? '');
    $descripcio  = trim($_POST['descripcio'] ?? '');
    $ha_de_pagar = isset($_POST['ha_de_pagar']) ? 1 : 0;
    $import      = $ha_de_pagar ? (float)($_POST['import_pagament'] ?? 0) : null;

    $tipusValids = ['perdua', 'deteriorament_greu', 'extraviu', 'altre'];

    if (!$exemplar_id)                    $errors[] = "Cal seleccionar un exemplar.";
    if (!in_array($tipus, $tipusValids))  $errors[] = "Tipus d'incidència no vàlid.";
    if ($descripcio === '')               $errors[] = "La descripció és obligatòria.";

    if (!$errors) {
        Database::insert(
            "INSERT INTO incidencies
                (alumne_id, exemplar_id, tipus, descripcio, ha_de_pagar, import_pagament, registrat_per)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$alumne_id, $exemplar_id, $tipus, $descripcio, $ha_de_pagar, $import, Auth::id()]
        );

        /* Historial */
        Database::insert(
            "INSERT INTO historial (alumne_id, exemplar_id, accio, detalls, usuari_id) VALUES (?,?,?,?,?)",
            [$alumne_id, $exemplar_id, 'incidencia', "{$tipus}: {$descripcio}", Auth::id()]
        );

        if (in_array($tipus, ['perdua', 'extraviu'])) {
            Database::execute(
                "UPDATE exemplars SET estat = 'perdut', disponible = 0 WHERE id = ?",
                [$exemplar_id]
            );
            Database::execute(
                "UPDATE prestecs SET estat = 'perdut', estat_prestec = 'perdut'
                 WHERE exemplar_id = ? AND alumne_id = ? AND estat = 'actiu'",
                [$exemplar_id, $alumne_id]
            );
        }

        /* Generar albarà d'incidència */
        $ex_info = Database::fetchOne(
            "SELECT e.codi, l.titol, m.nom AS materia
             FROM exemplars e
             JOIN llibres l  ON e.llibre_id = l.id
             JOIN materies m ON l.materia_id = m.id
             WHERE e.id = ?",
            [$exemplar_id]
        );

        try {
            $fitxer_pdf = PdfGenerator::alaraIncidencia([
                'alumne_id'      => $alumne_id,
                'alumne'         => $alumne['nom'] . ' ' . $alumne['cognoms'],
                'classe'         => $alumne['classe_nom'],
                'tutor'          => $alumne['tutor_nom'] ?? '',
                'exemplar_codi'  => $ex_info['codi'],
                'exemplar_titol' => $ex_info['titol'],
                'materia'        => $ex_info['materia'],
                'responsable'    => Auth::nom(),
            ]);

            $albaraId = Database::insert(
                "INSERT INTO albarans (alumne_id, tipus, fitxer_pdf, data) VALUES (?, 'incidencia', ?, NOW())",
                [$alumne_id, $fitxer_pdf]
            );

            if (!empty($alumne['email_familia'])) {
                try { MailSender::enviarAlbara($albaraId, $alumne['email_familia'], $alumne['nom'] . ' ' . $alumne['cognoms'], 'incidencia'); } catch (\Throwable $e) { }
            }
        } catch (\Throwable $e) { }

        header('Location: ' . BASE_URL . '/prestecs/prestecs.php?id=' . $alumne_id);
        exit;
    }
}

include __DIR__ . '/../src/views/layout_top.php'; ?>

<div class="card mb-4">
  <div class="card-header-bl">
    <i class="bi bi-exclamation-triangle"></i> Registrar incidència
  </div>
  <div class="card-body">

    <p class="mb-3">
      <strong><?= htmlspecialchars($alumne['nom'] . ' ' . $alumne['cognoms']) ?></strong>
      — <?= htmlspecialchars($alumne['classe_nom']) ?>
    </p>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?>
          <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$exemplars): ?>
      <div class="alert alert-info">Aquest alumne no té exemplars assignats.</div>
      <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne_id ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Tornar
      </a>
    <?php else: ?>

    <form method="POST">

      <div class="mb-3">
        <label class="form-label fw-semibold">Exemplar afectat</label>
        <select name="exemplar_id" class="form-select" required>
          <option value="">— Selecciona un exemplar —</option>
          <?php foreach ($exemplars as $ex): ?>
            <option value="<?= $ex['id'] ?>" <?= (($_POST['exemplar_id'] ?? '') == $ex['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($ex['codi'] . ' — ' . $ex['titol'] . ' (' . $ex['materia_nom'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Tipus d'incidència</label>
        <select name="tipus" class="form-select" required>
          <option value="">— Selecciona —</option>
          <option value="perdua"            <?= (($_POST['tipus'] ?? '') === 'perdua')            ? 'selected' : '' ?>>Pèrdua</option>
          <option value="extraviu"          <?= (($_POST['tipus'] ?? '') === 'extraviu')          ? 'selected' : '' ?>>Extraviu</option>
          <option value="deteriorament_greu"<?= (($_POST['tipus'] ?? '') === 'deteriorament_greu') ? 'selected' : '' ?>>Deteriorament greu</option>
          <option value="altre"             <?= (($_POST['tipus'] ?? '') === 'altre')             ? 'selected' : '' ?>>Altre</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Descripció</label>
        <textarea name="descripcio" class="form-control" rows="3" required
          placeholder="Descriu la incidència..."><?= htmlspecialchars($_POST['descripcio'] ?? '') ?></textarea>
      </div>

      <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="ha_de_pagar" name="ha_de_pagar"
          <?= isset($_POST['ha_de_pagar']) ? 'checked' : '' ?>
          onchange="document.getElementById('import_wrap').style.display=this.checked?'block':'none'">
        <label class="form-check-label" for="ha_de_pagar">Ha de pagar</label>
      </div>

      <div class="mb-3" id="import_wrap" style="display:<?= isset($_POST['ha_de_pagar']) ? 'block' : 'none' ?>">
        <label class="form-label">Import (€)</label>
        <input type="number" name="import_pagament" class="form-control" style="max-width:150px"
          step="0.01" min="0" value="<?= htmlspecialchars($_POST['import_pagament'] ?? '') ?>">
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-warning">
          <i class="bi bi-exclamation-triangle"></i> Registrar incidència
        </button>
        <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne_id ?>" class="btn btn-secondary">
          <i class="bi bi-arrow-left"></i> Cancel·lar
        </a>
      </div>

    </form>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
