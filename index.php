<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/helpers/Database.php';
require_once __DIR__ . '/src/helpers/Auth.php';

Auth::requireLogin();

$titolPagina = 'Inici';
$paginaActiva = 'inici';

// ── Salutació i data ──────────────────────────────────────────
$hora = (int)date('G');
$salutacio = $hora < 12 ? 'Bon dia' : ($hora < 20 ? 'Bona tarda' : 'Bona nit');
$diesCa  = ['diumenge','dilluns','dimarts','dimecres','dijous','divendres','dissabte'];
$mesosCa = ['','gener','febrer','març','abril','maig','juny','juliol','agost',
            'setembre','octubre','novembre','desembre'];
$dataAvui = $diesCa[(int)date('w')] . ', ' . date('j') . ' de '
          . $mesosCa[(int)date('n')] . ' de ' . date('Y');

// ── Estadístiques globals (pills) ────────────────────────────
$stats = [
    'exemplars_total'    => Database::fetchOne("SELECT COUNT(*) n FROM exemplars")['n'] ?? 0,
    'exemplars_prestats' => Database::fetchOne("SELECT COUNT(*) n FROM prestecs WHERE estat_prestec='actiu'")['n'] ?? 0,
    'exemplars_perduts'  => Database::fetchOne("SELECT COUNT(*) n FROM exemplars WHERE estat='perdut'")['n'] ?? 0,
    'incidencies'        => Database::fetchOne("SELECT COUNT(*) n FROM incidencies")['n'] ?? 0,
    'pendents_pagament'  => Database::fetchOne("SELECT COUNT(*) n FROM incidencies WHERE ha_de_pagar=1 AND pagat=0")['n'] ?? 0,
    'alumnes'            => Database::fetchOne("SELECT COUNT(*) n FROM alumnes WHERE actiu=1")['n'] ?? 0,
];

// ── Classes amb estadístiques per classe ─────────────────────
$esAdmin = Auth::rol() === 'admin';

$classeStatsQuery = "
    SELECT c.id, c.nom,
      (SELECT COUNT(*) FROM alumnes WHERE classe_id=c.id AND actiu=1) AS alumnes_actius,
      (SELECT COUNT(*) FROM prestecs p
       JOIN alumnes a ON a.id=p.alumne_id
       WHERE a.classe_id=c.id AND p.estat_prestec='actiu') AS prestecs_actius,
      (SELECT COUNT(*) FROM incidencies i
       JOIN alumnes a ON a.id=i.alumne_id
       WHERE a.classe_id=c.id) AS incidencies
    FROM classes c";

if ($esAdmin) {
    $classeStats = Database::fetchAll($classeStatsQuery . " ORDER BY c.nom");
} else {
    $classeStats = Database::fetchAll(
        $classeStatsQuery . " JOIN professor_classe pc ON pc.classe_id=c.id AND pc.professor_id=? ORDER BY c.nom",
        [Auth::id()]
    );
}

// ── Activitat recent ─────────────────────────────────────────
$recentQuery = "
    SELECT p.id, p.data_prestec, p.estat_prestec,
           CONCAT(a.nom,' ',a.cognoms) AS alumne,
           c.nom AS classe, e.codi AS exemplar_codi, ll.titol
    FROM prestecs p
    JOIN alumnes a   ON a.id = p.alumne_id
    JOIN classes c   ON c.id = a.classe_id
    JOIN exemplars e ON e.id = p.exemplar_id
    JOIN llibres ll  ON ll.id = e.llibre_id";

if ($esAdmin) {
    $activitat = Database::fetchAll($recentQuery . " ORDER BY p.data_prestec DESC LIMIT 6");
} else {
    $activitat = Database::fetchAll(
        $recentQuery . " JOIN professor_classe pc ON pc.classe_id=a.classe_id AND pc.professor_id=?
         ORDER BY p.data_prestec DESC LIMIT 6",
        [Auth::id()]
    );
}

include __DIR__ . '/src/views/layout_top.php';
?>

<style>
/* ── Welcome banner ─────────────────────────────────────────── */
.welcome-banner {
  background: linear-gradient(135deg, #1a237e 0%, #283593 55%, #1565c0 100%);
  color: #fff;
  border-radius: 14px;
  padding: 1.4rem 1.8rem;
  margin-bottom: 1.1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}
.welcome-greeting { font-size: 1.5rem; font-weight: 700; margin-bottom: .15rem; }
.welcome-date     { font-size: .88rem; opacity: .8; }
.welcome-role {
  background: rgba(255,255,255,.15);
  border: 1px solid rgba(255,255,255,.25);
  border-radius: 10px;
  padding: .7rem 1.2rem;
  text-align: center;
  white-space: nowrap;
  min-width: 130px;
}
.welcome-role i    { font-size: 1.6rem; display: block; margin-bottom: .3rem; }
.welcome-role strong { display: block; font-size: .95rem; font-weight: 700; }
.welcome-role small  { font-size: .78rem; opacity: .75; }

/* ── Stat pills ─────────────────────────────────────────────── */
.stat-pills {
  display: flex;
  gap: .55rem;
  flex-wrap: wrap;
  margin-bottom: 1.3rem;
}
.stat-pill {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  padding: .42rem .9rem;
  border-radius: 30px;
  font-size: .86rem;
  font-weight: 600;
  background: var(--bl-bg-card);
  box-shadow: var(--bl-card-shadow);
  border: 1.5px solid transparent;
  white-space: nowrap;
  color: var(--bl-text-body);
}
.stat-pill .pill-num { font-size: 1rem; font-weight: 800; }
.stat-pill.warn  { border-color: #ef9a9a; }
.stat-pill.alert { border-color: #ce93d8; }

/* ── Class mini-cards grid ──────────────────────────────────── */
.class-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
  gap: .85rem;
}
.class-mini-card {
  background: var(--bl-bg-card);
  border-radius: 11px;
  padding: 1rem .95rem .9rem;
  box-shadow: var(--bl-card-shadow);
  border-top: 4px solid #1a237e;
  transition: box-shadow .15s, transform .15s;
  display: flex;
  flex-direction: column;
  gap: .5rem;
}
.class-mini-card:hover {
  box-shadow: 0 4px 18px rgba(0,0,0,.16);
  transform: translateY(-2px);
}
.class-mini-name {
  font-size: 1.2rem; font-weight: 800;
  color: #1a237e; line-height: 1.1;
}
[data-bs-theme="dark"] .class-mini-name { color: #90caf9; }
.class-mini-chips { display: flex; gap: .4rem; flex-wrap: wrap; }
.chip {
  display: inline-flex; align-items: center; gap: .28rem;
  font-size: .76rem; font-weight: 600;
  padding: .18rem .5rem; border-radius: 20px;
}
.chip-blue   { background: #e3f2fd; color: #1565c0; }
.chip-green  { background: #e8f5e9; color: #2e7d32; }
.chip-orange { background: #fff3e0; color: #e65100; }
.chip-red    { background: #ffebee; color: #c62828; }
[data-bs-theme="dark"] .chip-blue   { background: #0d2545; color: #90caf9; }
[data-bs-theme="dark"] .chip-green  { background: #0d2b0d; color: #86efac; }
[data-bs-theme="dark"] .chip-orange { background: #2a2010; color: #fcd34d; }
[data-bs-theme="dark"] .chip-red    { background: #2a1215; color: #fca5a5; }

/* ── Quick actions ──────────────────────────────────────────── */
.qa-list { display: flex; flex-direction: column; gap: .55rem; padding: 1rem; }
.qa-btn {
  display: flex; align-items: center; gap: .8rem;
  padding: .75rem 1rem;
  border-radius: 10px;
  font-weight: 600; font-size: .93rem;
  text-decoration: none;
  transition: all .15s;
  border: 2px solid transparent;
  color: inherit;
}
.qa-btn i { font-size: 1.25rem; flex-shrink: 0; }
.qa-sub   { font-size: .76rem; font-weight: 400; opacity: .72; margin-top: .05rem; }
.qa-blue   { background: #1a237e; color: #fff !important; }
.qa-blue:hover { background: #283593; color: #fff !important; }
.qa-green  { background: #e8f5e9; color: #2e7d32 !important; border-color: #a5d6a7; }
.qa-green:hover  { background: #2e7d32; color: #fff !important; border-color: #2e7d32; }
.qa-orange { background: #fff3e0; color: #e65100 !important; border-color: #ffcc80; }
.qa-orange:hover { background: #e65100; color: #fff !important; border-color: #e65100; }
.qa-gray { background: var(--bl-bg-page); color: var(--bl-text-body) !important; border-color: var(--bl-border); }
.qa-gray:hover { background: var(--bl-border); }
[data-bs-theme="dark"] .qa-green  { background: #0d2b0d; color: #86efac !important; border-color: #2e7d32; }
[data-bs-theme="dark"] .qa-green:hover  { background: #2e7d32; color: #fff !important; }
[data-bs-theme="dark"] .qa-orange { background: #2a2010; color: #fcd34d !important; border-color: #e65100; }
[data-bs-theme="dark"] .qa-orange:hover { background: #e65100; color: #fff !important; }
</style>

<!-- WELCOME BANNER -->
<div class="welcome-banner">
  <div>
    <div class="welcome-greeting"><?= $salutacio ?>, <?= htmlspecialchars(Auth::nom()) ?>!</div>
    <div class="welcome-date"><i class="bi bi-calendar3 me-1"></i><?= ucfirst($dataAvui) ?> &nbsp;·&nbsp; Curs <?= ANY_ESCOLAR ?></div>
  </div>
  <div class="welcome-role">
    <?php if ($esAdmin): ?>
      <i class="bi bi-shield-fill-check"></i>
      <strong>Administrador/a</strong>
      <small>Accés total</small>
    <?php else: ?>
      <i class="bi bi-person-badge-fill"></i>
      <strong>Professor/a</strong>
      <small><?= count($classeStats) ?> classe<?= count($classeStats) != 1 ? 's' : '' ?> assignada<?= count($classeStats) != 1 ? 's' : '' ?></small>
    <?php endif; ?>
  </div>
</div>

<!-- STAT PILLS -->
<div class="stat-pills">
  <div class="stat-pill">
    <i class="bi bi-upc-scan" style="color:#1565c0"></i>
    <span class="pill-num"><?= $stats['exemplars_total'] ?></span> exemplars
  </div>
  <div class="stat-pill">
    <i class="bi bi-arrow-right-circle-fill" style="color:#0277bd"></i>
    <span class="pill-num"><?= $stats['exemplars_prestats'] ?></span> en préstec
  </div>
  <div class="stat-pill <?= $stats['exemplars_perduts'] > 0 ? 'warn' : '' ?>">
    <i class="bi bi-x-octagon-fill" style="color:#c62828"></i>
    <span class="pill-num" <?= $stats['exemplars_perduts'] > 0 ? 'style="color:#c62828"' : '' ?>><?= $stats['exemplars_perduts'] ?></span> perduts
  </div>
  <div class="stat-pill">
    <i class="bi bi-exclamation-triangle-fill" style="color:#e65100"></i>
    <span class="pill-num"><?= $stats['incidencies'] ?></span> incidències
  </div>
  <div class="stat-pill <?= $stats['pendents_pagament'] > 0 ? 'alert' : '' ?>">
    <i class="bi bi-cash-stack" style="color:#6a1b9a"></i>
    <span class="pill-num" <?= $stats['pendents_pagament'] > 0 ? 'style="color:#6a1b9a"' : '' ?>><?= $stats['pendents_pagament'] ?></span> pendent pagament
  </div>
  <div class="stat-pill">
    <i class="bi bi-people-fill" style="color:#2e7d32"></i>
    <span class="pill-num"><?= $stats['alumnes'] ?></span> alumnes actius
  </div>
</div>

<!-- CLASSES + ACCIONS RÀPIDES -->
<div class="row g-3 mb-3">

  <!-- Classes -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header-bl">
        <i class="bi bi-people-fill"></i>
        <?= $esAdmin ? 'Totes les classes' : 'Les meues classes' ?>
      </div>
      <div class="p-3">
        <?php if (empty($classeStats)): ?>
          <p class="text-muted py-2 mb-0">
            <?= $esAdmin ? 'No hi ha classes registrades.' : 'No tens cap classe assignada. Contacta amb l\'administrador.' ?>
          </p>
        <?php else: ?>
        <div class="class-grid">
          <?php foreach ($classeStats as $c):
            $incAlert = (int)$c['incidencies'] > 0;
          ?>
          <div class="class-mini-card">
            <div class="class-mini-name"><?= htmlspecialchars($c['nom']) ?></div>
            <div class="class-mini-chips">
              <span class="chip chip-blue">
                <i class="bi bi-people-fill"></i> <?= $c['alumnes_actius'] ?> alumnes
              </span>
              <span class="chip chip-green">
                <i class="bi bi-arrow-right-circle-fill"></i> <?= $c['prestecs_actius'] ?> en préstec
              </span>
              <?php if ($incAlert): ?>
              <span class="chip <?= (int)$c['incidencies'] > 2 ? 'chip-red' : 'chip-orange' ?>">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?= $c['incidencies'] ?> <?= (int)$c['incidencies'] === 1 ? 'incidència' : 'incidències' ?>
              </span>
              <?php endif; ?>
            </div>
            <a href="<?= BASE_URL ?>/alumnes/llista.php?classe_id=<?= $c['id'] ?>"
               class="btn btn-sm btn-primary mt-auto">
              <i class="bi bi-eye"></i> Veure alumnes
            </a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Accions ràpides -->
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header-bl"><i class="bi bi-lightning-fill"></i> Accions ràpides</div>
      <div class="qa-list">
        <a href="<?= BASE_URL ?>/classes/classes.php" class="qa-btn qa-blue">
          <i class="bi bi-journals"></i>
          <span>
            <div>Assignar lot de llibres</div>
            <div class="qa-sub">Accedir a les classes</div>
          </span>
        </a>
        <a href="<?= BASE_URL ?>/prestecs/devolucio.php" class="qa-btn qa-green">
          <i class="bi bi-arrow-return-left"></i>
          <span>
            <div>Registrar devolució</div>
            <div class="qa-sub">Per codi d'exemplar</div>
          </span>
        </a>
        <a href="<?= BASE_URL ?>/incidencies/index.php" class="qa-btn qa-orange">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>
            <div>Incidències</div>
            <div class="qa-sub">Registrar o consultar</div>
          </span>
        </a>
        <a href="<?= BASE_URL ?>/prestecs/index.php" class="qa-btn qa-gray">
          <i class="bi bi-list-ul"></i>
          <span>
            <div>Llista de préstecs</div>
            <div class="qa-sub">Tots els préstecs actius</div>
          </span>
        </a>
        <?php if ($esAdmin): ?>
        <a href="<?= BASE_URL ?>/alumnes/llista.php" class="qa-btn qa-gray">
          <i class="bi bi-person-lines-fill"></i>
          <span>
            <div>Gestionar alumnes</div>
            <div class="qa-sub">Llistat complet d'alumnes</div>
          </span>
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- ACTIVITAT RECENT -->
<div class="card">
  <div class="card-header-bl d-flex justify-content-between align-items-center">
    <span><i class="bi bi-clock-history"></i> Activitat recent</span>
    <a href="<?= BASE_URL ?>/prestecs/index.php"
       style="color:#fff;font-size:.83rem;font-weight:400;opacity:.8;text-decoration:none">
      Veure tots <i class="bi bi-arrow-right"></i>
    </a>
  </div>
  <div class="table-responsive">
    <table class="table table-bl mb-0">
      <thead><tr>
        <th>Data</th><th>Alumne/a</th><th>Classe</th><th>Codi</th><th>Títol</th><th>Estat</th>
      </tr></thead>
      <tbody>
      <?php foreach ($activitat as $p): ?>
        <tr>
          <td style="white-space:nowrap;font-size:.88rem"><?= date('d/m/Y', strtotime($p['data_prestec'])) ?></td>
          <td><?= htmlspecialchars($p['alumne']) ?></td>
          <td><span style="font-size:.82rem;color:var(--bl-text-muted)"><?= htmlspecialchars($p['classe']) ?></span></td>
          <td><span class="codi-exemplar"><?= htmlspecialchars($p['exemplar_codi']) ?></span></td>
          <td style="font-size:.9rem"><?= htmlspecialchars(mb_substr($p['titol'],0,36)) ?></td>
          <td><span class="badge badge-estat-<?= $p['estat_prestec'] ?>"><?= ucfirst($p['estat_prestec']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$activitat): ?>
        <tr><td colspan="6" class="text-center text-muted py-3">Cap préstec registrat encara</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/src/views/layout_bottom.php'; ?>
