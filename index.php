<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/helpers/Database.php';
require_once __DIR__ . '/src/helpers/Auth.php';

Auth::requireLogin();

$titolPagina = 'Inici';
$paginaActiva = 'inici';

/* ───────────────────────────────────────────────
   1) OBTENIR CLASSES I SELECCIÓ
   ─────────────────────────────────────────────── */
$classes = Database::fetchAll("SELECT id, nom FROM classes ORDER BY nom ASC");
$classeSeleccionada = $_GET['classe'] ?? '';

/* ───────────────────────────────────────────────
   2) ESTADÍSTIQUES (GLOBAL O PER CLASSE)
   ─────────────────────────────────────────────── */

if ($classeSeleccionada) {

    // Estadístiques filtrades per classe
    $stats = [
        'exemplars_total' => Database::fetchOne("
            SELECT COUNT(*) n
            FROM prestecs p
            JOIN alumnes a ON a.id = p.alumne_id
            WHERE a.classe_id = ?
        ", [$classeSeleccionada])['n'] ?? 0,

        'exemplars_prestats' => Database::fetchOne("
            SELECT COUNT(*) n
            FROM prestecs p
            JOIN alumnes a ON a.id = p.alumne_id
            WHERE p.estat_prestec='actiu' AND a.classe_id = ?
        ", [$classeSeleccionada])['n'] ?? 0,

        'exemplars_perduts' => Database::fetchOne("
            SELECT COUNT(*) n
            FROM prestecs p
            JOIN alumnes a ON a.id = p.alumne_id
            JOIN exemplars e ON e.id = p.exemplar_id
            WHERE e.estat='perdut' AND a.classe_id = ?
        ", [$classeSeleccionada])['n'] ?? 0,

        'incidencies' => Database::fetchOne("
            SELECT COUNT(*) n
            FROM incidencies i
            JOIN alumnes a ON a.id = i.alumne_id
            WHERE a.classe_id = ?
        ", [$classeSeleccionada])['n'] ?? 0,

        'pendents_pagament' => Database::fetchOne("
            SELECT COUNT(*) n
            FROM incidencies i
            JOIN alumnes a ON a.id = i.alumne_id
            WHERE i.ha_de_pagar=1 AND i.pagat=0 AND a.classe_id = ?
        ", [$classeSeleccionada])['n'] ?? 0,

        'alumnes' => Database::fetchOne("
            SELECT COUNT(*) n
            FROM alumnes
            WHERE actiu=1 AND classe_id = ?
        ", [$classeSeleccionada])['n'] ?? 0,
    ];

    // Últims préstecs filtrats
    $ultims_prestecs = Database::fetchAll("
        SELECT p.id, p.data_prestec, p.estat_prestec,
               CONCAT(a.nom,' ',a.cognoms) AS alumne,
               e.codi AS exemplar_codi, ll.titol
        FROM prestecs p
        JOIN alumnes a   ON a.id = p.alumne_id
        JOIN exemplars e ON e.id = p.exemplar_id
        JOIN llibres ll  ON ll.id = e.llibre_id
        WHERE a.classe_id = ?
        ORDER BY p.data_prestec DESC LIMIT 8
    ", [$classeSeleccionada]);

} else {

    // Estadístiques globals
    $stats = [
        'exemplars_total'   => Database::fetchOne("SELECT COUNT(*) n FROM exemplars")['n'] ?? 0,
        'exemplars_prestats'=> Database::fetchOne("SELECT COUNT(*) n FROM prestecs WHERE estat_prestec='actiu'")['n'] ?? 0,
        'exemplars_perduts' => Database::fetchOne("SELECT COUNT(*) n FROM exemplars WHERE estat='perdut'")['n'] ?? 0,
        'incidencies'       => Database::fetchOne("SELECT COUNT(*) n FROM incidencies")['n'] ?? 0,
        'pendents_pagament' => Database::fetchOne("SELECT COUNT(*) n FROM incidencies WHERE ha_de_pagar=1 AND pagat=0")['n'] ?? 0,
        'alumnes'           => Database::fetchOne("SELECT COUNT(*) n FROM alumnes WHERE actiu=1")['n'] ?? 0,
    ];

    // Últims préstecs globals
    $ultims_prestecs = Database::fetchAll("
        SELECT p.id, p.data_prestec, p.estat_prestec,
               CONCAT(a.nom,' ',a.cognoms) AS alumne,
               e.codi AS exemplar_codi, ll.titol
        FROM prestecs p
        JOIN alumnes a   ON a.id = p.alumne_id
        JOIN exemplars e ON e.id = p.exemplar_id
        JOIN llibres ll  ON ll.id = e.llibre_id
        ORDER BY p.data_prestec DESC LIMIT 8
    ");
}

include __DIR__ . '/src/views/layout_top.php';
?>

<!-- SELECTOR DE CLASSE -->
<div class="card mb-4">
  <div class="card-header-bl"><i class="bi bi-people-fill"></i> Filtra per classe</div>
  <div class="card-body">
    <form method="GET" id="selectorClasse">
      <select name="classe" class="form-select form-select-lg"
              onchange="document.getElementById('selectorClasse').submit()">
        <option value="">— Totes les classes —</option>
        <?php foreach ($classes as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($classeSeleccionada == $c['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['nom']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</div>

<!-- TARGETES D'ESTADÍSTIQUES -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['Exemplars totals',    $stats['exemplars_total'],    'upc-scan',            '#1565c0', '#e3f2fd'],
    ['En préstec ara',      $stats['exemplars_prestats'],  'arrow-right-circle-fill','#0277bd', '#e1f5fe'],
    ['Perduts',             $stats['exemplars_perduts'],   'x-octagon-fill',      '#c62828', '#ffebee'],
    ['Incidències totals',  $stats['incidencies'],         'exclamation-triangle-fill','#e65100','#fff3e0'],
    ['Pendent de pagament', $stats['pendents_pagament'],   'cash-stack',          '#6a1b9a', '#f3e5f5'],
    ['Alumnes actius',      $stats['alumnes'],             'people-fill',         '#2e7d32', '#e8f5e9'],
  ];
  foreach ($cards as [$label, $val, $ico, $color, $bg]): ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card" style="border-color:<?= $color ?>;background:<?= $bg ?>">
      <div class="stat-icon" style="color:<?= $color ?>"><i class="bi bi-<?= $ico ?>"></i></div>
      <div class="stat-num"  style="color:<?= $color ?>"><?= $val ?></div>
      <div class="stat-label"><?= $label ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ÚLTIMS PRÉSTECS + ACCIONS RÀPIDES -->
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header-bl"><i class="bi bi-clock-history"></i> Últims préstecs registrats</div>
      <div class="table-responsive">
        <table class="table table-bl mb-0">
          <thead><tr>
            <th>Data</th><th>Alumne/a</th><th>Codi</th><th>Títol del llibre</th><th>Estat</th>
          </tr></thead>
          <tbody>
          <?php foreach ($ultims_prestecs as $p): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($p['data_prestec'])) ?></td>
              <td><?= htmlspecialchars($p['alumne']) ?></td>
              <td><span class="codi-exemplar"><?= htmlspecialchars($p['exemplar_codi']) ?></span></td>
              <td><?= htmlspecialchars(mb_substr($p['titol'],0,38)) ?></td>
              <td><span class="badge badge-estat-<?= $p['estat_prestec'] ?>"><?= ucfirst($p['estat_prestec']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$ultims_prestecs): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">Cap préstec registrat encara</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header-bl"><i class="bi bi-lightning-fill"></i> Accions ràpides</div>
      <div class="p-3 d-grid gap-2">
        <a href="<?= BASE_URL ?>/classes/classes.php" class="btn btn-primary">
          <i class="bi bi-people-fill"></i> Veure les classes
        </a>
        <a href="<?= BASE_URL ?>/prestecs/index.php" class="btn btn-outline-primary">
          <i class="bi bi-list-ul"></i> Llista de préstecs
        </a>
        <a href="<?= BASE_URL ?>/exemplars/exemplars.php" class="btn btn-outline-secondary">
          <i class="bi bi-upc-scan"></i> Veure exemplars
        </a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/src/views/layout_bottom.php'; ?>
