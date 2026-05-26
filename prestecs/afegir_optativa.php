<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Auth.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/../vendor/MailSender.php';
require_once __DIR__ . '/../vendor/PdfGenerator.php';

Auth::requireLogin();

$titolPagina  = 'Afegir optativa';
$paginaActiva = 'prestecs';

$alumne_id = (int)($_GET['alumne_id'] ?? 0);
if (!$alumne_id) {
    header('Location: ' . BASE_URL . '/prestecs/index.php');
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
     JOIN llibres l  ON e.llibre_id = l.id
     JOIN materies m ON l.materia_id = m.id
     WHERE m.tipus = 'optativa'
       AND e.disponible = 1
       AND e.estat != 'perdut'
     ORDER BY m.nom, e.codi"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::csrfCheck();
    $exemplar_id = (int)($_POST['exemplar_id'] ?? 0);

    if (!$exemplar_id) {
        header('Location: ' . BASE_URL . '/prestecs/prestecs.php?id=' . $alumne_id . '&error=sense_exemplar');
        exit;
    }

    Database::execute(
        "UPDATE exemplars SET alumne_id = ?, disponible = 0 WHERE id = ?",
        [$alumne_id, $exemplar_id]
    );

    Database::insert(
        "INSERT INTO prestecs (alumne_id, exemplar_id, estat, estat_prestec) VALUES (?, ?, 'actiu', 'actiu')",
        [$alumne_id, $exemplar_id]
    );

    $ex = Database::fetchOne(
        "SELECT e.codi, l.titol, m.nom AS materia, e.estat AS estat_inicial
         FROM exemplars e
         JOIN llibres l  ON e.llibre_id = l.id
         JOIN materies m ON l.materia_id = m.id
         WHERE e.id = ?",
        [$exemplar_id]
    );

    try {
        $fitxer_pdf = PdfGenerator::albaraPrestec([
            'alumne_id'   => $alumne_id,
            'alumne'      => $alumne['nom'] . ' ' . $alumne['cognoms'],
            'classe'      => $alumne['classe_nom'],
            'tutor'       => $alumne['tutor_nom'] ?? '',
            'lot_codi'    => '',
            'exemplars'   => [$ex],
            'responsable' => Auth::nom(),
        ]);

        $albaraId = Database::insert(
            "INSERT INTO albarans (alumne_id, tipus, fitxer_pdf, data) VALUES (?, 'prestec', ?, NOW())",
            [$alumne_id, $fitxer_pdf]
        );

        if (!empty($alumne['email_familia'])) {
            try { MailSender::enviarAlbara($albaraId, $alumne['email_familia'], $alumne['nom'] . ' ' . $alumne['cognoms'], 'prestec'); } catch (\Throwable $e) { }
        }
    } catch (\Throwable $e) { }

    header('Location: ' . BASE_URL . '/prestecs/prestecs.php?id=' . $alumne_id);
    exit;
}

include __DIR__ . '/../src/views/layout_top.php'; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne_id ?>" class="btn btn-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Tornar a la fitxa
  </a>
</div>

<div class="card">
  <div class="card-header-bl">
    <i class="bi bi-plus-circle"></i>
    Afegir llibre d'optativa —
    <?= htmlspecialchars($alumne['nom'] . ' ' . $alumne['cognoms']) ?>
    <small class="ms-2 fw-normal opacity-75"><?= htmlspecialchars($alumne['classe_nom']) ?></small>
  </div>
  <div class="card-body">

    <?php if (!$exemplars): ?>
      <div class="alert alert-warning mb-0">
        <i class="bi bi-exclamation-triangle"></i>
        No hi ha exemplars d'optatives disponibles en aquest moment.
      </div>
    <?php else: ?>

      <form method="POST">
  <?= Auth::csrfField() ?>
        <div class="mb-4">
          <label class="form-label">Selecciona l'exemplar d'optativa</label>
          <select name="exemplar_id" class="form-select" required>
            <option value="">— Selecciona —</option>
            <?php foreach ($exemplars as $ex): ?>
              <option value="<?= $ex['id'] ?>">
                <?= htmlspecialchars($ex['materia_nom'] . ' — ' . $ex['codi'] . ' — ' . $ex['titol']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Assignar optativa
          </button>
          <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $alumne_id ?>" class="btn btn-secondary">
            <i class="bi bi-x-lg"></i> Cancel·lar
          </a>
        </div>
      </form>

    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
