<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Llibres';
$paginaActiva = 'llibres';

$missatge = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accio = $_POST['accio'] ?? '';

    /* EDITAR */
    if ($accio === 'editar') {
        $id         = (int)($_POST['id'] ?? 0);
        $titol      = trim($_POST['titol']      ?? '');
        $isbn       = trim($_POST['isbn']       ?? '');
        $editorial  = trim($_POST['editorial']  ?? '');
        $materia_id = (int)($_POST['materia_id'] ?? 0);
        $curs_id    = (int)($_POST['curs_id']    ?? 0);

        if ($id && $titol !== '' && $materia_id && $curs_id) {
            Database::execute(
                "UPDATE llibres SET titol=?, isbn=?, editorial=?, materia_id=?, curs_id=? WHERE id=?",
                [$titol, $isbn, $editorial, $materia_id, $curs_id, $id]
            );
            $missatge = 'Llibre actualitzat.';
        }
    }

    /* ELIMINAR */
    if ($accio === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $usos = Database::fetchOne(
                "SELECT COUNT(*) n FROM exemplars WHERE llibre_id = ?", [$id]
            )['n'] ?? 0;

            if ($usos > 0) {
                $errorMsg = "No es pot eliminar: hi ha {$usos} exemplar(s) d'aquest llibre.";
            } else {
                $titol = Database::fetchOne("SELECT titol FROM llibres WHERE id=?", [$id])['titol'] ?? '';
                Database::execute("DELETE FROM llibres WHERE id=?", [$id]);
                $missatge = "Llibre «{$titol}» eliminat.";
            }
        }
    }

    if (!$errorMsg) {
        header('Location: ' . BASE_URL . '/llibres/llibres.php' . ($missatge ? '?ok=1' : ''));
        exit;
    }
}

if (isset($_GET['ok'])) $missatge = 'Operació completada correctament.';

/* Filtre per matèria */
$filtreMateria = (int)($_GET['materia_id'] ?? 0);

$whereClause = 'l.actiu = 1';
$params = [];
if ($filtreMateria) {
    $whereClause .= ' AND l.materia_id = ?';
    $params[] = $filtreMateria;
}

$llibres = Database::fetchAll(
    "SELECT l.*, m.nom AS materia_nom, m.codi AS mat_codi,
            cu.codi AS curs_codi,
            COUNT(e.id) AS num_exemplars,
            SUM(e.disponible) AS num_disponibles
     FROM llibres l
     JOIN materies m  ON l.materia_id = m.id
     JOIN cursos cu   ON l.curs_id = cu.id
     LEFT JOIN exemplars e ON e.llibre_id = l.id
     WHERE {$whereClause}
     GROUP BY l.id
     ORDER BY m.nom, l.titol",
    $params
);

$materies = Database::fetchAll(
    "SELECT m.id, m.nom, m.codi, m.tipus,
            COUNT(l.id) AS num_llibres,
            COALESCE(SUM(
                (SELECT COUNT(*) FROM exemplars e WHERE e.llibre_id = l.id)
            ), 0) AS num_exemplars
     FROM materies m
     LEFT JOIN llibres l ON l.materia_id = m.id AND l.actiu = 1
     GROUP BY m.id
     ORDER BY m.tipus ASC, m.nom ASC"
);
$cursos = Database::fetchAll("SELECT id, codi FROM cursos ORDER BY codi");

$materiaActual = $filtreMateria
    ? Database::fetchOne("SELECT id, nom, codi, tipus FROM materies WHERE id=?", [$filtreMateria])
    : null;

include __DIR__ . '/../src/views/layout_top.php'; ?>

<?php if ($missatge): ?>
<div class="alert alert-success"><?= htmlspecialchars($missatge) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<?php if (!$filtreMateria): ?>
<!-- =====================================================================
     ESTAT INICIAL: tria de matèria (sense filtre actiu)
     ===================================================================== -->

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-0" style="color:#1a237e">
      <i class="bi bi-journal-text me-2"></i>Llibres
    </h3>
    <p class="text-muted mb-0 mt-1" style="font-size:.9rem">
      Selecciona una matèria per veure els llibres disponibles
    </p>
  </div>
  <?php if (Auth::rol() === 'admin'): ?>
  <a href="<?= BASE_URL ?>/llibres/nou.php" class="btn btn-primary">
    <i class="bi bi-plus-circle"></i> Nou llibre
  </a>
  <?php endif; ?>
</div>

<?php
$comuni   = array_filter($materies, fn($m) => $m['tipus'] === 'comuna');
$optativa = array_filter($materies, fn($m) => $m['tipus'] === 'optativa');
?>

<!-- Matèries comunes -->
<?php if (!empty($comuni)): ?>
<div class="mb-2 px-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#888">
  <i class="bi bi-circle-fill me-1" style="color:#1565c0;font-size:.5rem"></i> Matèries comunes
</div>
<div class="row g-3 mb-4">
  <?php foreach ($comuni as $m): ?>
  <div class="col-sm-6 col-md-4 col-xl-3">
    <a href="?materia_id=<?= $m['id'] ?>" class="text-decoration-none">
      <div class="materia-card">
        <div class="materia-card-top" style="background:#1a237e"></div>
        <div class="materia-card-body">
          <div class="materia-card-codi"><?= htmlspecialchars($m['codi']) ?></div>
          <div class="materia-card-nom"><?= htmlspecialchars($m['nom']) ?></div>
          <div class="materia-card-footer">
            <?php if ($m['num_llibres'] > 0): ?>
              <span class="materia-card-count">
                <i class="bi bi-book me-1"></i><?= $m['num_llibres'] ?> llibre<?= $m['num_llibres'] != 1 ? 's' : '' ?>
              </span>
            <?php else: ?>
              <span class="materia-card-empty">Sense llibres</span>
            <?php endif; ?>
            <i class="bi bi-arrow-right-circle materia-card-arrow"></i>
          </div>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Matèries optatives -->
<?php if (!empty($optativa)): ?>
<div class="mb-2 px-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#888">
  <i class="bi bi-circle-fill me-1" style="color:#0277bd;font-size:.5rem"></i> Matèries optatives
</div>
<div class="row g-3">
  <?php foreach ($optativa as $m): ?>
  <div class="col-sm-6 col-md-4 col-xl-3">
    <a href="?materia_id=<?= $m['id'] ?>" class="text-decoration-none">
      <div class="materia-card">
        <div class="materia-card-top" style="background:#0277bd"></div>
        <div class="materia-card-body">
          <div class="materia-card-codi"><?= htmlspecialchars($m['codi']) ?></div>
          <div class="materia-card-nom"><?= htmlspecialchars($m['nom']) ?></div>
          <div class="materia-card-footer">
            <?php if ($m['num_llibres'] > 0): ?>
              <span class="materia-card-count">
                <i class="bi bi-book me-1"></i><?= $m['num_llibres'] ?> llibre<?= $m['num_llibres'] != 1 ? 's' : '' ?>
              </span>
            <?php else: ?>
              <span class="materia-card-empty">Sense llibres</span>
            <?php endif; ?>
            <i class="bi bi-arrow-right-circle materia-card-arrow"></i>
          </div>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>


<?php else: ?>
<!-- =====================================================================
     ESTAT FILTRAT: sidebar matèries + grid de llibres
     ===================================================================== -->
<div class="row g-4">

  <!-- Panel esquerre: matèries -->
  <div class="col-md-3">
    <div class="card">
      <div class="card-header-bl"><i class="bi bi-tags-fill"></i> Matèries</div>

      <?php
      $comuni   = array_filter($materies, fn($m) => $m['tipus'] === 'comuna');
      $optativa = array_filter($materies, fn($m) => $m['tipus'] === 'optativa');
      ?>

      <div class="list-group list-group-flush">

        <!-- Totes -->
        <a href="<?= BASE_URL ?>/llibres/llibres.php"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
          <span class="fw-semibold" style="font-size:.9rem">
            <i class="bi bi-arrow-left me-1"></i> Totes les matèries
          </span>
        </a>

        <!-- Comunes -->
        <div class="px-3 pt-2 pb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#aaa">
          Comunes
        </div>
        <?php foreach ($comuni as $m): ?>
        <?php $activa = ($filtreMateria == $m['id']); ?>
        <a href="?materia_id=<?= $m['id'] ?>"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
           style="<?= $activa ? 'background:#e8eaf6;border-left:4px solid #1a237e;font-weight:700' : 'border-left:4px solid transparent' ?>">
          <span style="font-size:.88rem"><?= htmlspecialchars($m['nom']) ?></span>
          <span class="d-flex gap-1 align-items-center flex-shrink-0">
            <span class="codi-exemplar" style="font-size:.72rem"><?= htmlspecialchars($m['codi']) ?></span>
            <?php if ($m['num_llibres'] > 0): ?>
            <span class="badge" style="background:#1565c0;color:#fff;font-size:.68rem"><?= $m['num_llibres'] ?></span>
            <?php endif; ?>
          </span>
        </a>
        <?php endforeach; ?>

        <!-- Optatives -->
        <div class="px-3 pt-2 pb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#aaa">
          Optatives
        </div>
        <?php foreach ($optativa as $m): ?>
        <?php $activa = ($filtreMateria == $m['id']); ?>
        <a href="?materia_id=<?= $m['id'] ?>"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
           style="<?= $activa ? 'background:#e8eaf6;border-left:4px solid #1a237e;font-weight:700' : 'border-left:4px solid transparent' ?>">
          <span style="font-size:.88rem"><?= htmlspecialchars($m['nom']) ?></span>
          <span class="d-flex gap-1 align-items-center flex-shrink-0">
            <span class="codi-exemplar" style="font-size:.72rem"><?= htmlspecialchars($m['codi']) ?></span>
            <?php if ($m['num_llibres'] > 0): ?>
            <span class="badge" style="background:#0277bd;color:#fff;font-size:.68rem"><?= $m['num_llibres'] ?></span>
            <?php endif; ?>
          </span>
        </a>
        <?php endforeach; ?>

      </div>
    </div>
  </div>

  <!-- Panel dret: llibres -->
  <div class="col-md-9">

    <div class="d-flex align-items-center gap-2 mb-3">
      <h4 class="fw-bold mb-0">
        <span class="codi-exemplar me-1"><?= htmlspecialchars($materiaActual['codi']) ?></span>
        <?= htmlspecialchars($materiaActual['nom']) ?>
        <small class="text-muted fw-normal ms-1" style="font-size:.8rem">(<?= count($llibres) ?> llibres)</small>
      </h4>
      <div class="ms-auto">
        <?php if (Auth::rol() === 'admin'): ?>
        <a href="<?= BASE_URL ?>/llibres/nou.php?materia_id=<?= $filtreMateria ?>"
           class="btn btn-primary btn-sm">
          <i class="bi bi-plus-circle"></i> Nou llibre
        </a>
        <?php endif; ?>
      </div>
    </div>

    <?php if (empty($llibres)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="bi bi-journal-plus" style="font-size:2.5rem;color:#ccc"></i>
          <p class="mt-3 text-muted mb-3">Aquesta matèria no té cap llibre registrat.</p>
          <?php if (Auth::rol() === 'admin'): ?>
          <a href="<?= BASE_URL ?>/llibres/nou.php?materia_id=<?= $filtreMateria ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Afegir primer llibre
          </a>
          <?php endif; ?>
        </div>
      </div>

    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($llibres as $l):
          $total = (int)($l['num_exemplars'] ?? 0);
          $disp  = (int)($l['num_disponibles'] ?? 0);
          if ($total === 0)          { $color = '#9e9e9e'; $bg = '#f5f5f5'; }
          elseif ($disp === $total)  { $color = '#2e7d32'; $bg = '#e8f5e9'; }
          elseif ($disp > 0)         { $color = '#1565c0'; $bg = '#e3f2fd'; }
          else                       { $color = '#c62828'; $bg = '#ffebee'; }
        ?>
        <div class="col-sm-6 col-xl-4">
          <div class="card h-100" style="border-top:4px solid <?= $color ?>">
            <div class="card-body pb-2">
              <div class="fw-bold mb-2 lh-sm" style="font-size:1rem">
                <?= htmlspecialchars($l['titol']) ?>
              </div>
              <div class="d-flex flex-wrap gap-1 mb-2">
                <span class="codi-exemplar" style="font-size:.76rem"><?= htmlspecialchars($l['curs_codi']) ?></span>
                <?php if (!empty($l['isbn'])): ?>
                <span style="font-size:.76rem;color:#999">ISBN <?= htmlspecialchars($l['isbn']) ?></span>
                <?php endif; ?>
              </div>
              <?php if (!empty($l['editorial'])): ?>
              <div class="text-muted mb-2" style="font-size:.8rem">
                <i class="bi bi-building me-1"></i><?= htmlspecialchars($l['editorial']) ?>
              </div>
              <?php endif; ?>
              <div class="d-flex align-items-center gap-2 mt-auto pt-2" style="border-top:1px solid #eee">
                <div style="text-align:center;min-width:44px">
                  <div style="font-size:1.6rem;font-weight:800;color:<?= $color ?>;line-height:1"><?= $disp ?></div>
                  <div style="font-size:.68rem;color:#aaa">disp./<?= $total ?></div>
                </div>
                <div class="flex-fill" style="font-size:.8rem">
                  <?php if ($total === 0): ?>
                    <span style="color:#9e9e9e">Sense exemplars</span>
                  <?php elseif ($disp === $total): ?>
                    <span style="color:#2e7d32">Tots disponibles</span>
                  <?php elseif ($disp > 0): ?>
                    <span style="color:#1565c0"><?= $total - $disp ?> en préstec</span>
                  <?php else: ?>
                    <span style="color:#c62828">Tots en préstec</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="card-footer bg-transparent pt-2 pb-2 d-flex gap-1">
              <a href="<?= BASE_URL ?>/exemplars/exemplars.php?llibre_id=<?= $l['id'] ?>"
                 class="btn btn-sm btn-outline-primary flex-fill">
                <i class="bi bi-upc-scan"></i> Exemplars
              </a>
              <?php if (Auth::rol() === 'admin'): ?>
              <button class="btn btn-sm btn-outline-secondary"
                      onclick='obrirEditar(<?= json_encode([
                        'id'=>$l['id'],'titol'=>$l['titol'],'isbn'=>$l['isbn'] ?? '',
                        'editorial'=>$l['editorial'] ?? '','materia_id'=>$l['materia_id'],
                        'curs_id'=>$l['curs_id']
                      ]) ?>)'>
                <i class="bi bi-pencil"></i>
              </button>
              <?php if ($total == 0): ?>
              <form method="POST" class="d-inline"
                    onsubmit="return confirm('Eliminar «<?= htmlspecialchars($l['titol'], ENT_QUOTES) ?>»?')">
                <input type="hidden" name="accio" value="eliminar">
                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
              <?php else: ?>
              <button class="btn btn-sm btn-outline-danger" disabled title="Té exemplars">
                <i class="bi bi-trash"></i>
              </button>
              <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- Modal editar (admin) -->
<?php if (Auth::rol() === 'admin'): ?>
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header card-header-bl" style="border-radius:0">
        <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar llibre</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="accio" value="editar">
        <input type="hidden" name="id" id="editId">
        <div class="mb-3">
          <label class="form-label">Títol *</label>
          <input type="text" name="titol" id="editTitol" class="form-control" required>
        </div>
        <div class="row g-2">
          <div class="col-sm-6 mb-3">
            <label class="form-label">ISBN</label>
            <input type="text" name="isbn" id="editIsbn" class="form-control">
          </div>
          <div class="col-sm-6 mb-3">
            <label class="form-label">Editorial</label>
            <input type="text" name="editorial" id="editEditorial" class="form-control">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Matèria *</label>
          <select name="materia_id" id="editMateria" class="form-select" required>
            <?php foreach ($materies as $m): ?>
              <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Curs *</label>
          <select name="curs_id" id="editCurs" class="form-select" required>
            <?php foreach ($cursos as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['codi']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel·lar</button>
        <button type="submit" class="btn btn-primary">Guardar canvis</button>
      </div>
    </form>
  </div>
</div>
<script>
function obrirEditar(l) {
  document.getElementById('editId').value        = l.id;
  document.getElementById('editTitol').value     = l.titol;
  document.getElementById('editIsbn').value      = l.isbn;
  document.getElementById('editEditorial').value = l.editorial;
  document.getElementById('editMateria').value   = l.materia_id;
  document.getElementById('editCurs').value      = l.curs_id;
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>
<?php endif; ?>

<?php endif; ?>

<style>
/* Targetes de selecció de matèria */
.materia-card {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,.07);
  background: #fff;
  transition: transform .15s, box-shadow .15s;
  cursor: pointer;
  border: 1px solid #e8eaf6;
  height: 100%;
}
.materia-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(26,35,126,.15);
  border-color: #9fa8da;
}
.materia-card-top {
  height: 6px;
}
.materia-card-body {
  padding: 1rem 1.1rem .9rem;
}
.materia-card-codi {
  display: inline-block;
  font-family: 'IBM Plex Mono', monospace;
  font-size: .72rem;
  background: #e8eaf6;
  color: #283593;
  border-radius: 4px;
  padding: 2px 7px;
  font-weight: 600;
  margin-bottom: .5rem;
}
.materia-card-nom {
  font-size: 1rem;
  font-weight: 700;
  color: #1a237e;
  line-height: 1.3;
  margin-bottom: .75rem;
  min-height: 2.6rem;
}
.materia-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid #f0f0f0;
  padding-top: .6rem;
}
.materia-card-count {
  font-size: .8rem;
  color: #555;
}
.materia-card-empty {
  font-size: .78rem;
  color: #bbb;
}
.materia-card-arrow {
  font-size: 1.2rem;
  color: #c5cae9;
  transition: color .15s, transform .15s;
}
.materia-card:hover .materia-card-arrow {
  color: #1a237e;
  transform: translateX(2px);
}
/* Sidebar */
.list-group-item { border-left: 4px solid transparent; }
.list-group-item:hover { background: #f5f5f5; }
</style>

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
