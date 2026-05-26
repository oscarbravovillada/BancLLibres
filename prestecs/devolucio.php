<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Auth.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/../vendor/MailSender.php';
require_once __DIR__ . '/../vendor/PdfGenerator.php';

Auth::requireLogin();

$titolPagina  = 'Registrar devolució';
$paginaActiva = 'prestecs';

$alumne_id = (int)($_GET['alumne_id'] ?? 0);
if (!$alumne_id) {
    header('Location: ' . BASE_URL . '/prestecs/index.php');
    exit;
}

Auth::requireAccessToAlumne($alumne_id);

/* Alumne */
$alumne = Database::fetchOne(
    "SELECT a.*, c.nom AS classe_nom, CONCAT(u.nom,' ',u.cognoms) AS tutor_nom
     FROM alumnes a
     JOIN classes c ON a.classe_id = c.id
     LEFT JOIN usuaris u ON c.tutor_id = u.id
     WHERE a.id = ?",
    [$alumne_id]
);

/* Exemplars actius */
$exemplars = Database::fetchAll(
    "SELECT e.*, l.titol, m.nom AS materia_nom
     FROM exemplars e
     JOIN llibres l ON e.llibre_id = l.id
     JOIN materies m ON l.materia_id = m.id
     WHERE e.alumne_id = ?
     ORDER BY m.nom",
    [$alumne_id]
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::csrfCheck();

    $retornats    = [];
    $no_retornats = [];
    $pendents     = [];

    foreach ($_POST['estat'] as $id => $estat) {

        if ($estat === 'pendent') {

            Database::execute(
                "UPDATE prestecs SET estat = 'pendent', estat_prestec = 'pendent'
                 WHERE exemplar_id = ? AND alumne_id = ? AND estat = 'actiu'",
                [$id, $alumne_id]
            );

            $ex_pend = Database::fetchOne(
                "SELECT e.codi, l.titol FROM exemplars e
                 JOIN llibres l ON e.llibre_id = l.id WHERE e.id = ?",
                [$id]
            );

            Database::insert(
                "INSERT INTO historial (alumne_id, exemplar_id, accio, detalls, usuari_id) VALUES (?,?,?,?,?)",
                [$alumne_id, $id, 'pendent', "Pendent de retorn: {$ex_pend['codi']} — {$ex_pend['titol']}", Auth::id()]
            );

            $pendents[] = ['codi' => $ex_pend['codi'], 'titol' => $ex_pend['titol']];
        }

        if ($estat === 'retornat') {

            /* Alliberar exemplar */
            Database::execute(
                "UPDATE exemplars
                 SET alumne_id = NULL, lot_id = NULL, disponible = 1
                 WHERE id = ?",
                [$id]
            );

            /* Tancar préstec */
            Database::execute(
                "UPDATE prestecs SET estat = 'retornat', estat_prestec = 'retornat', data_devolucio = NOW()
                 WHERE exemplar_id = ? AND alumne_id = ? AND estat = 'actiu'",
                [$id, $alumne_id]
            );

            $ex_ret = Database::fetchOne(
                "SELECT e.codi, e.estat, l.titol, m.nom AS materia
                 FROM exemplars e
                 JOIN llibres l ON e.llibre_id = l.id
                 JOIN materies m ON l.materia_id = m.id
                 WHERE e.id = ?",
                [$id]
            );
            $retornats[] = [
                'codi'              => $ex_ret['codi'],
                'titol'             => $ex_ret['titol'],
                'materia'           => $ex_ret['materia'],
                'estat_inicial'     => $ex_ret['estat'],
                'estat_final'       => $ex_ret['estat'],
                'desperfectes_final'=> '',
            ];

            /* Historial */
            Database::insert(
                "INSERT INTO historial (alumne_id, exemplar_id, accio, detalls, usuari_id) VALUES (?,?,?,?,?)",
                [$alumne_id, $id, 'devolucio', "Retornat: {$ex_ret['codi']} — {$ex_ret['titol']}", Auth::id()]
            );
        }

        if ($estat === 'perdut') {

            /* Marcar com perdut */
            Database::execute(
                "UPDATE exemplars
                 SET estat = 'perdut', disponible = 0
                 WHERE id = ?",
                [$id]
            );

            /* Tancar préstec */
            Database::execute(
                "UPDATE prestecs SET estat = 'perdut', estat_prestec = 'perdut', data_devolucio = NOW()
                 WHERE exemplar_id = ? AND alumne_id = ? AND estat = 'actiu'",
                [$id, $alumne_id]
            );

            /* Registrar incidència */
            Database::insert(
                "INSERT INTO incidencies (alumne_id, exemplar_id, tipus, descripcio)
                 VALUES (?, ?, 'perdua', 'Pèrdua automàtica en devolució')",
                [$alumne_id, $id]
            );

            /* Historial */
            $ex_perd = Database::fetchOne("SELECT e.codi, l.titol FROM exemplars e JOIN llibres l ON e.llibre_id=l.id WHERE e.id=?", [$id]);
            Database::insert(
                "INSERT INTO historial (alumne_id, exemplar_id, accio, detalls, usuari_id) VALUES (?,?,?,?,?)",
                [$alumne_id, $id, 'perdua', "Perdut en devolució: {$ex_perd['codi']} — {$ex_perd['titol']}", Auth::id()]
            );

            $no_retornats[] = [
                'codi'  => $ex_perd['codi'],
                'titol' => $ex_perd['titol'],
                'motiu' => 'Perdut',
            ];
        }
    }

    /* ============================
       CÀRRECS PENDENTS DE PAGAMENT
       ============================ */
    $carrecs = Database::fetchAll(
        "SELECT i.tipus, i.descripcio, i.import_pagament, i.pagat, i.data_pagament,
                e.codi AS exemplar_codi, l.titol
         FROM incidencies i
         JOIN exemplars e ON i.exemplar_id = e.id
         JOIN llibres l   ON e.llibre_id = l.id
         WHERE i.alumne_id = ? AND i.ha_de_pagar = 1
         ORDER BY i.data_incidencia DESC",
        [$alumne_id]
    );

    /* ============================
       GENERAR PDF DE DEVOLUCIÓ
       ============================ */
    $dades_pdf = [
        'alumne_id'    => $alumne_id,
        'alumne'       => $alumne['nom'] . ' ' . $alumne['cognoms'],
        'classe'       => $alumne['classe_nom'],
        'tutor'        => $alumne['tutor_nom'],
        'retornats'    => $retornats,
        'no_retornats' => $no_retornats,
        'pendents'     => $pendents,
        'carrecs'      => $carrecs,
        'responsable'  => Auth::nom(),
    ];

    $fitxer_pdf = PdfGenerator::albaraDevolucio($dades_pdf);

    $albaraId = Database::insert(
        "INSERT INTO albarans (alumne_id, tipus, fitxer_pdf, data)
         VALUES (?, 'devolucio', ?, NOW())",
        [$alumne_id, $fitxer_pdf]
    );

    if (!empty($alumne['email_familia'])) {
        try { MailSender::enviarAlbara($albaraId, $alumne['email_familia'], $alumne['nom'] . ' ' . $alumne['cognoms'], 'devolucio'); } catch (\Throwable $e) { /* correu no configurat */ }
    }

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
    <i class="bi bi-arrow-return-left"></i>
    Registrar devolució —
    <?= htmlspecialchars($alumne['nom'] . ' ' . $alumne['cognoms']) ?>
    <small class="ms-2 fw-normal opacity-75"><?= htmlspecialchars($alumne['classe_nom']) ?></small>
  </div>
  <div class="card-body">

    <?php if (!$exemplars): ?>
      <div class="alert alert-info mb-0">Aquest alumne/a no té exemplars assignats.</div>
    <?php else: ?>

      <p class="text-muted mb-4">
        Indica l'estat de cada exemplar en el moment de la devolució.
        <strong>Retornat</strong>: alliberat del préstec. &nbsp;
        <strong>Perdut</strong>: es genera incidència automàtica. &nbsp;
        <strong>Pendent de retorn</strong>: queda assignat a l'alumne/a per a una devolució futura.
      </p>

      <form method="POST">
  <?= Auth::csrfField() ?>
        <div class="table-responsive">
          <table class="table table-bl mb-0">
            <thead>
              <tr>
                <th>Codi</th>
                <th>Títol</th>
                <th>Matèria</th>
                <th>Estat actual</th>
                <th>Acció</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($exemplars as $ex): ?>
              <tr>
                <td><span class="codi-exemplar"><?= htmlspecialchars($ex['codi']) ?></span></td>
                <td><?= htmlspecialchars($ex['titol']) ?></td>
                <td><?= htmlspecialchars($ex['materia_nom']) ?></td>
                <td><span class="badge badge-estat-<?= $ex['estat'] ?>"><?= ucfirst($ex['estat']) ?></span></td>
                <td style="min-width:180px">
                  <select name="estat[<?= $ex['id'] ?>]" class="form-select form-select-sm">
                    <option value="pendent">Pendent de retorn</option>
                    <option value="retornat">Retornat</option>
                    <option value="perdut">Perdut</option>
                  </select>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2 mt-4">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-arrow-return-left"></i> Confirmar devolució
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
