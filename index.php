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

<!-- SELECTOR DE CLASSE (VERSIÓ GRAN I VISIBLE PER A PROFESSORS) -->
<div class="card mb-4" style="border-left: 6px solid #1e3a8a;">
  <div class="card-body">

    <label class="fw-bold mb-2" style="font-size:1.2rem;">
      <i class="bi bi-people-fill me-2"></i>
      Selecciona la classe
    </label>

    <form method="GET" id="selectorClasse">

      <select name="classe"
              class="form-select form-select-lg py-3"
              style="font-size:1.2rem; font-weight:600; border:2px solid #1e3a8a;"
              onchange="document.getElementById('selectorClasse').submit()">

        <option value="">— Totes les classes —</option>

        <?php foreach ($classes as $c): ?>
          <option value="<?= $c['id'] ?>"
            <?= ($classeSeleccionada == $c['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['nom']) ?>
          </option>
        <?php endforeach; ?>

      </select>

    </form>

  </div>
</div>


<!-- ───────────────────────────────────────────────
     TARGETES D'ESTADÍSTIQUES
─────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['Exemplars totals',   $stats['exemplars_total'],   'upc-scan',        '#1a4fa0'],
    ['En préstec',         $stats['exemplars_prestats'], 'arrow-right-circle','#0369a1'],
    ['Perduts',            $stats['exemplars_perduts'],  'x-circle',        '#b91c1c'],
    ['Incidències',        $stats['incidencies'],        'exclamation-triangle','#d97706'],
    ['Pendents de pagament',$stats['pendents_pagament'], 'cash',            '#be123c'],
    ['Alumnes actius',     $stats['alumnes'],            'people',          '#15803d'],
  ];
  foreach ($cards as [$label, $val, $ico, $col]): ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card p-3 h-100">
      <div style="color:<?= $col ?>;font-size:1.4rem"><i class="bi bi-<?= $ico ?>"></i></div>
      <div style="font-size:1.6rem;font-weight:700;line-height:1.2;color:<?= $col ?>"><?= $val ?></div>
      <div style="font-size:.75rem;color:#64748b;margin-top:2px"><?= $label ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ───────────────────────────────────────────────
     ÚLTIMS PRÉSTECS
─────────────────────────────────────────────── -->
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header-bl"><i class="bi bi-arrow-right-circle"></i> Últims préstecs</div>
      <div class="table-responsive">
        <table class="table table-bl mb-0">
          <thead><tr>
            <th>Data</th><th>Alumne/a</th><th>Exemplar</th><th>Títol</th><th>Estat</th>
          </tr></thead>
          <tbody>
          <?php foreach ($ultims_prestecs as $p): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($p['data_prestec'])) ?></td>
              <td><?= htmlspecialchars($p['alumne']) ?></td>
              <td><span class="codi-exemplar"><?= htmlspecialchars($p['exemplar_codi']) ?></span></td>
              <td><?= htmlspecialchars(mb_substr($p['titol'],0,40)) ?></td>
              <td><span class="badge badge-estat-<?= $p['estat_prestec'] ?>"><?= ucfirst($p['estat_prestec']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$ultims_prestecs): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">Cap préstec registrat</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ───────────────────────────────────────────────
       ACCIONS RÀPIDES
  ─────────────────────────────────────────────── -->
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header-bl"><i class="bi bi-lightning"></i> Accions ràpides</div>
      <div class="p-3 d-grid gap-2">
        <a href="<?= BASE_URL ?>/classes/classes.php" class="btn btn-bl-primary btn-sm">
          <i class="bi bi-people me-1"></i> Accedir a les classes
        </a>
        <a href="<?= BASE_URL ?>/exemplars/exemplars.php?accio=nou" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-plus-circle me-1"></i> Nou exemplar
        </a>
        <a href="<?= BASE_URL ?>/llibres/llibres.php?accio=nou" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-journal-plus me-1"></i> Nou llibre
        </a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/src/views/layout_bottom.php'; ?>
