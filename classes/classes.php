<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

$titolPagina = 'Classes';
$paginaActiva = 'classes';
$missatge = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAdmin();
    $accio = $_POST['accio'] ?? '';

    /* ── NOVA CLASSE ─────────────────────────────────────────── */
    if ($accio === 'nova') {
        $etapa     = $_POST['etapa']     ?? '';
        $tutor_id  = (int)($_POST['tutor_id'] ?? 0) ?: null;
        $curs_codi = '';
        $curs_nom  = '';
        $class_nom = '';

        if ($etapa === 'eso') {
            $num  = (int)($_POST['curs_num'] ?? 0);
            $grup = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['grup'] ?? ''));
            $map_codi = [1=>'1ESO',2=>'2ESO',3=>'3ESO',4=>'4ESO'];
            $map_nom  = [1=>'1r ESO',2=>'2n ESO',3=>'3r ESO',4=>'4t ESO'];
            if (!isset($map_codi[$num]) || $grup === '') {
                $errorMsg = 'Cal seleccionar el curs i el grup.';
            } else {
                $curs_codi = $map_codi[$num];
                $curs_nom  = $map_nom[$num];
                $class_nom = $curs_codi . '-' . $grup;
            }

        } elseif (in_array($etapa, ['cfgb','cfgm','cfgs'])) {
            $prefix    = strtoupper($etapa);
            $cicle     = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['codi_cicle'] ?? ''));
            $grup      = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['grup']       ?? ''));
            $nom_c     = trim($_POST['nom_complet'] ?? '');
            if ($cicle === '' || $grup === '') {
                $errorMsg = 'Cal indicar el codi del cicle i el grup.';
            } else {
                $curs_codi = $prefix . '-' . $cicle;
                $curs_nom  = $nom_c ?: ($prefix . ' ' . $cicle);
                $class_nom = $curs_codi . '-' . $grup;
            }

        } elseif ($etapa === 'altre') {
            $class_nom = trim($_POST['nom_classe'] ?? '');
            $curs_nom  = trim($_POST['nom_curs']   ?? '');
            $curs_codi = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $class_nom));
            if ($class_nom === '' || $curs_nom === '') {
                $errorMsg = 'Cal indicar el nom de la classe i el nom oficial del curs.';
            }
        } else {
            $errorMsg = 'Etapa no vàlida.';
        }

        if (!$errorMsg) {
            // Comprovar que la classe no existeix ja
            $existent = Database::fetchOne(
                "SELECT id FROM classes WHERE nom = ? AND curs_escolar = ?",
                [$class_nom, ANY_ESCOLAR]
            );
            if ($existent) {
                $errorMsg = "La classe «{$class_nom}» ja existeix aquest curs.";
            } else {
                // Trobar o crear el curs
                $curs = Database::fetchOne("SELECT id FROM cursos WHERE codi = ?", [$curs_codi]);
                if ($curs) {
                    $curs_id = $curs['id'];
                } else {
                    Database::execute(
                        "INSERT INTO cursos (codi, nom, actiu) VALUES (?, ?, 1)",
                        [$curs_codi, $curs_nom]
                    );
                    $curs_id = Database::lastInsertId();
                }

                // Crear la classe
                Database::execute(
                    "INSERT INTO classes (curs_id, nom, tutor_id, curs_escolar) VALUES (?, ?, ?, ?)",
                    [$curs_id, $class_nom, $tutor_id, ANY_ESCOLAR]
                );
                $missatge = "Classe «{$class_nom}» creada correctament.";
            }
        }
    }

    /* ── ELIMINAR CLASSE ─────────────────────────────────────── */
    if ($accio === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $alumnesCount = Database::fetchOne(
                "SELECT COUNT(*) n FROM alumnes WHERE classe_id = ? AND actiu = 1",
                [$id]
            )['n'] ?? 0;

            if ($alumnesCount > 0) {
                $errorMsg = "No es pot eliminar la classe: té $alumnesCount alumne/s assignat/s.";
            } else {
                Database::execute("DELETE FROM classes WHERE id = ?", [$id]);
                $missatge = "Classe eliminada correctament.";
            }
        }
    }
} // end POST

$tutors = Database::fetchAll(
    "SELECT id, CONCAT(nom,' ',cognoms) AS nom_complet
     FROM usuaris WHERE rol IN ('professor','admin') AND actiu = 1
     ORDER BY cognoms, nom"
);

$classes = Database::fetchAll(
    "SELECT c.*, cu.codi AS curs_codi, cu.nom AS curs_nom,
            CONCAT(u.nom,' ',u.cognoms) AS tutor_nom,
            (SELECT COUNT(*) FROM alumnes a WHERE a.classe_id = c.id AND a.actiu = 1) AS num_alumnes
     FROM classes c
     JOIN cursos cu ON c.curs_id = cu.id
     LEFT JOIN usuaris u ON c.tutor_id = u.id
     WHERE c.curs_escolar = ?
     ORDER BY cu.codi, c.nom",
    [ANY_ESCOLAR]
);

// Agrupar per etapa educativa
$grups = [
    'ESO'             => ['label' => 'ESO',            'color' => '#1565c0', 'icon' => 'bi-mortarboard',    'classes' => []],
    'CFGB'            => ['label' => 'Graus Bàsics',   'color' => '#e65100', 'icon' => 'bi-journal-text',   'classes' => []],
    'CFGM'            => ['label' => 'Graus Mitjans',  'color' => '#2e7d32', 'icon' => 'bi-journal-richtext','classes' => []],
    'CFGS'            => ['label' => 'Graus Superiors','color' => '#4a148c', 'icon' => 'bi-award',           'classes' => []],
    'ALTRES'          => ['label' => 'Altres',          'color' => '#455a64', 'icon' => 'bi-grid',           'classes' => []],
];

foreach ($classes as $c) {
    $codi = strtoupper($c['curs_codi']);
    if (str_contains($codi, 'ESO')) {
        $grups['ESO']['classes'][] = $c;
    } elseif (str_starts_with($codi, 'CFGB')) {
        $grups['CFGB']['classes'][] = $c;
    } elseif (str_starts_with($codi, 'CFGM')) {
        $grups['CFGM']['classes'][] = $c;
    } elseif (str_starts_with($codi, 'CFGS') || str_contains($codi, 'ASIX') || str_contains($codi, 'ASIR')) {
        $grups['CFGS']['classes'][] = $c;
    } else {
        $grups['ALTRES']['classes'][] = $c;
    }
}
// Eliminar grups buits
$grups = array_filter($grups, fn($g) => !empty($g['classes']));

include __DIR__ . '/../src/views/layout_top.php'; ?>

<?php if ($missatge): ?>
  <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($missatge) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
  <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<!-- Capçalera -->
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-0" style="color:#1a237e">
      <i class="bi bi-building me-2"></i>Classes
    </h3>
    <p class="text-muted mb-0 mt-1" style="font-size:.9rem">
      <?= array_sum(array_map(fn($g) => count($g['classes']), $grups)) ?> classes
      · <?= array_sum(array_column($classes, 'num_alumnes')) ?> alumnes
      · curs <?= ANY_ESCOLAR ?>
    </p>
  </div>
  <?php if (Auth::rol() === 'admin'): ?>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaClasse">
    <i class="bi bi-plus-circle me-1"></i> Nova classe
  </button>
  <?php endif; ?>
</div>

<!-- Acordió per etapes -->
<div class="accordion" id="acordioClasses">

<?php foreach ($grups as $clau => $grup): ?>

<?php
  $totalAlumnes = array_sum(array_column($grup['classes'], 'num_alumnes'));
  $id = 'grup-' . strtolower($clau);
?>

<div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-sm">

  <!-- Capçalera del grup -->
  <h2 class="accordion-header">
    <button class="accordion-button collapsed fw-semibold py-3"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#<?= $id ?>"
            style="background:<?= $grup['color'] ?>18;color:<?= $grup['color'] ?>;font-size:1rem">
      <i class="bi <?= $grup['icon'] ?> me-2" style="font-size:1.1rem"></i>
      <?= htmlspecialchars($grup['label']) ?>
      <span class="ms-3 d-flex gap-2">
        <span class="badge rounded-pill" style="background:<?= $grup['color'] ?>;color:#fff;font-size:.72rem;font-weight:600">
          <?= count($grup['classes']) ?> classe<?= count($grup['classes']) != 1 ? 's' : '' ?>
        </span>
        <?php if ($totalAlumnes > 0): ?>
        <span class="badge rounded-pill" style="background:<?= $grup['color'] ?>33;color:<?= $grup['color'] ?>;font-size:.72rem;font-weight:600;border:1px solid <?= $grup['color'] ?>55">
          <?= $totalAlumnes ?> alumne<?= $totalAlumnes != 1 ? 's' : '' ?>
        </span>
        <?php endif; ?>
      </span>
    </button>
  </h2>

  <!-- Contingut del grup -->
  <div id="<?= $id ?>" class="accordion-collapse collapse"
       data-bs-parent="#acordioClasses">
    <div class="accordion-body pt-3 pb-4" style="background:<?= $grup['color'] ?>08">

      <div class="row g-3">
        <?php foreach ($grup['classes'] as $c): ?>
        <div class="col-sm-6 col-lg-4 col-xl-3">
          <div class="classe-card h-100" style="--color:<?= $grup['color'] ?>">
            <div class="classe-card-header">
              <span class="classe-card-nom"><?= htmlspecialchars($c['nom']) ?></span>
              <?php if ($c['num_alumnes'] > 0): ?>
              <span class="classe-card-badge" style="background:<?= $grup['color'] ?>22;color:<?= $grup['color'] ?>">
                <?= $c['num_alumnes'] ?> alum.
              </span>
              <?php else: ?>
              <span class="classe-card-badge" style="background:#f5f5f5;color:#aaa">
                Buida
              </span>
              <?php endif; ?>
            </div>
            <div class="classe-card-curs text-muted"><?= htmlspecialchars($c['curs_nom']) ?></div>
            <div class="classe-card-tutor">
              <i class="bi bi-person-badge me-1" style="color:<?= $grup['color'] ?>"></i>
              <?= htmlspecialchars($c['tutor_nom'] ?: 'Sense tutor assignat') ?>
            </div>
            <div class="classe-card-footer">
              <a href="<?= BASE_URL ?>/alumnes/llista.php?classe_id=<?= $c['id'] ?>"
                 class="btn btn-sm flex-fill fw-semibold"
                 style="background:<?= $grup['color'] ?>;color:#fff;border:none">
                <i class="bi bi-people-fill me-1"></i>Alumnes
              </a>
              <?php if (Auth::rol() === 'admin'): ?>
              <form method="POST" action="<?= BASE_URL ?>/classes/classes.php" class="m-0"
                    onsubmit="return confirm('Eliminar la classe «<?= htmlspecialchars($c['nom'], ENT_QUOTES) ?>»?')">
                <input type="hidden" name="accio" value="eliminar">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

<?php endforeach; ?>
</div>

<?php if (empty($grups)): ?>
<div class="card">
  <div class="card-body text-center py-5">
    <i class="bi bi-building" style="font-size:3rem;color:#ccc"></i>
    <p class="mt-3 text-muted">No hi ha classes registrades per al curs <?= ANY_ESCOLAR ?>.</p>
  </div>
</div>
<?php endif; ?>

<?php if (Auth::rol() === 'admin'): ?>
<!-- ===================== MODAL NOVA CLASSE ===================== -->
<div class="modal fade" id="modalNovaClasse" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content" id="formNovaClasse">
      <input type="hidden" name="accio" value="nova">

      <div class="modal-header card-header-bl" style="border-radius:0">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i> Nova classe</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- Pas 1: Tipus d'etapa -->
        <div class="mb-4">
          <label class="form-label fw-semibold">Etapa educativa</label>
          <div class="row g-2" id="etapaBtns">
            <?php
            $etapes = [
              'eso'   => ['ESO',            'bi-mortarboard',     '#1565c0'],
              'cfgb'  => ['Grau Bàsic',     'bi-journal-text',    '#e65100'],
              'cfgm'  => ['Grau Mitjà',     'bi-journal-richtext','#2e7d32'],
              'cfgs'  => ['Grau Superior',  'bi-award',           '#4a148c'],
              'altre' => ['Altre',          'bi-grid',            '#455a64'],
            ];
            foreach ($etapes as $val => [$label, $icon, $color]): ?>
            <div class="col-6 col-md-auto">
              <input type="radio" name="etapa" id="etapa_<?= $val ?>" value="<?= $val ?>"
                     class="btn-check" required>
              <label class="btn btn-outline-secondary etapa-btn w-100" for="etapa_<?= $val ?>"
                     data-color="<?= $color ?>">
                <i class="bi <?= $icon ?> d-block mb-1" style="font-size:1.4rem"></i>
                <span style="font-size:.82rem;font-weight:600"><?= $label ?></span>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Pas 2: Camps específics (ocults fins triar etapa) -->

        <!-- ESO -->
        <div id="seccio-eso" class="seccio-etapa d-none">
          <div class="row g-3 align-items-end">
            <div class="col-sm-6">
              <label class="form-label fw-semibold">Curs</label>
              <div class="d-flex gap-2 flex-wrap">
                <?php foreach ([1=>'1r',2=>'2n',3=>'3r',4=>'4t'] as $n => $label): ?>
                <div>
                  <input type="radio" name="curs_num" id="curs<?= $n ?>" value="<?= $n ?>" class="btn-check">
                  <label class="btn btn-outline-primary" for="curs<?= $n ?>"><?= $label ?></label>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="col-sm-3">
              <label class="form-label fw-semibold">Grup</label>
              <input type="text" name="grup" id="grup_eso" class="form-control text-uppercase"
                     maxlength="3" placeholder="A">
            </div>
          </div>
        </div>

        <!-- FP (CFGB / CFGM / CFGS) -->
        <div id="seccio-fp" class="seccio-etapa d-none">
          <div class="row g-3">
            <div class="col-sm-4">
              <label class="form-label fw-semibold">Codi del cicle</label>
              <div class="input-group">
                <span class="input-group-text fw-bold" id="fp-prefix" style="min-width:60px">CFGM</span>
                <input type="text" name="codi_cicle" id="codi_cicle" class="form-control text-uppercase"
                       placeholder="SMX" maxlength="10">
              </div>
              <div class="form-text">Sigles del cicle (ex: SMX, ASIX, IO)</div>
            </div>
            <div class="col-sm-2">
              <label class="form-label fw-semibold">Grup</label>
              <input type="text" name="grup" id="grup_fp" class="form-control text-uppercase"
                     maxlength="3" placeholder="A">
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold">Nom complet del cicle</label>
              <input type="text" name="nom_complet" id="nom_complet" class="form-control"
                     placeholder="Sistemes Microinformàtics i Xarxes">
              <div class="form-text">Opcional si el cicle ja existeix</div>
            </div>
          </div>
        </div>

        <!-- Altre -->
        <div id="seccio-altre" class="seccio-etapa d-none">
          <div class="row g-3">
            <div class="col-sm-5">
              <label class="form-label fw-semibold">Nom de la classe</label>
              <input type="text" name="nom_classe" id="nom_classe" class="form-control text-uppercase"
                     placeholder="1BAT-A" maxlength="50">
              <div class="form-text">Codi intern (ex: 1BAT-A, 2BAT-B)</div>
            </div>
            <div class="col-sm-7">
              <label class="form-label fw-semibold">Nom oficial del curs</label>
              <input type="text" name="nom_curs" id="nom_curs" class="form-control"
                     placeholder="1r Batxillerat" maxlength="100">
            </div>
          </div>
        </div>

        <!-- Vista prèvia del nom generat -->
        <div id="previa" class="d-none mt-3 p-3 rounded-3" style="background:#e8eaf6">
          <div style="font-size:.82rem;color:#555">La classe es crearà com:</div>
          <div id="previa-nom" class="fw-bold mt-1" style="font-size:1.3rem;font-family:'IBM Plex Mono',monospace;color:#1a237e"></div>
        </div>

        <hr class="my-3">

        <!-- Tutor (sempre visible) -->
        <div class="col-sm-8">
          <label class="form-label fw-semibold">Tutor/a <span class="text-muted fw-normal">(opcional)</span></label>
          <select name="tutor_id" class="form-select">
            <option value="">— Sense tutor assignat —</option>
            <?php foreach ($tutors as $t): ?>
            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nom_complet']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel·lar</button>
        <button type="submit" class="btn btn-primary" id="btnCrear" disabled>
          <i class="bi bi-plus-circle me-1"></i> Crear classe
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  const radios   = document.querySelectorAll('input[name="etapa"]');
  const seccions = document.querySelectorAll('.seccio-etapa');
  const previa   = document.getElementById('previa');
  const previaNom= document.getElementById('previa-nom');
  const btnCrear = document.getElementById('btnCrear');
  const prefix   = document.getElementById('fp-prefix');

  function actualitzar() {
    const etapa = document.querySelector('input[name="etapa"]:checked')?.value;
    seccions.forEach(s => s.classList.add('d-none'));

    // Color del botó actiu
    document.querySelectorAll('.etapa-btn').forEach(b => {
      b.style.borderColor = '';
      b.style.backgroundColor = '';
      b.style.color = '';
    });
    const selLabel = document.querySelector(`label[for="etapa_${etapa}"]`);
    if (selLabel) {
      const c = selLabel.dataset.color;
      selLabel.style.borderColor = c;
      selLabel.style.backgroundColor = c + '18';
      selLabel.style.color = c;
    }

    if (etapa === 'eso')   document.getElementById('seccio-eso').classList.remove('d-none');
    if (['cfgb','cfgm','cfgs'].includes(etapa)) {
      document.getElementById('seccio-fp').classList.remove('d-none');
      prefix.textContent = etapa.toUpperCase();
    }
    if (etapa === 'altre') document.getElementById('seccio-altre').classList.remove('d-none');

    recalcularNom();
  }

  function recalcularNom() {
    const etapa = document.querySelector('input[name="etapa"]:checked')?.value;
    let nom = '';

    if (etapa === 'eso') {
      const num   = document.querySelector('input[name="curs_num"]:checked')?.value;
      const codis = {1:'1ESO',2:'2ESO',3:'3ESO',4:'4ESO'};
      const grup  = document.getElementById('grup_eso').value.toUpperCase().trim();
      if (num && grup) nom = (codis[num] || '') + '-' + grup;

    } else if (['cfgb','cfgm','cfgs'].includes(etapa)) {
      const cicle = document.getElementById('codi_cicle').value.toUpperCase().replace(/[^A-Z0-9]/g,'');
      const grup  = document.getElementById('grup_fp').value.toUpperCase().trim();
      if (cicle && grup) nom = etapa.toUpperCase() + '-' + cicle + '-' + grup;

    } else if (etapa === 'altre') {
      nom = document.getElementById('nom_classe').value.toUpperCase().trim();
    }

    if (nom) {
      previaNom.textContent = nom;
      previa.classList.remove('d-none');
      btnCrear.disabled = false;
    } else {
      previa.classList.add('d-none');
      btnCrear.disabled = true;
    }
  }

  radios.forEach(r => r.addEventListener('change', actualitzar));

  // Escolta canvis en tots els camps de text i radios de curs_num
  document.querySelectorAll('#formNovaClasse input[type="text"], input[name="curs_num"]')
    .forEach(el => el.addEventListener('input', recalcularNom));
  document.querySelectorAll('input[name="curs_num"]')
    .forEach(el => el.addEventListener('change', recalcularNom));
})();
</script>
<?php endif; ?>

<style>
.accordion-button::after { filter: none !important; }
.accordion-button:focus  { box-shadow: none; }
.accordion-button:not(.collapsed) { box-shadow: none; }

.classe-card {
  background: #fff;
  border: 1px solid #e8eaf6;
  border-top: 4px solid var(--color);
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: .4rem;
  transition: box-shadow .15s, transform .15s;
}
.classe-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,.1);
  transform: translateY(-2px);
}
.classe-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .5rem;
}
.classe-card-nom {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1a237e;
  font-family: 'IBM Plex Mono', monospace;
}
.classe-card-badge {
  font-size: .72rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 20px;
  white-space: nowrap;
}
.classe-card-curs {
  font-size: .8rem;
  line-height: 1.2;
}
.classe-card-tutor {
  font-size: .82rem;
  color: #555;
  flex-grow: 1;
}
.classe-card-footer {
  display: flex;
  gap: .4rem;
  margin-top: .5rem;
  padding-top: .6rem;
  border-top: 1px solid #f0f0f0;
}
</style>

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
