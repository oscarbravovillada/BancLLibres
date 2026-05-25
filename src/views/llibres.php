<?php include __DIR__ . '/layout_top.php'; ?>

<?php if ($missatge): ?>
<div class="alert alert-success"><?= htmlspecialchars($missatge) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<div class="row g-4">

  <!-- ===================== PANEL ESQUERRE: MATÈRIES ===================== -->
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
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2
                  <?= !$filtreMateria ? 'active-mat' : '' ?>">
          <span class="fw-semibold" style="font-size:.95rem">
            <i class="bi bi-grid me-1"></i> Totes les matèries
          </span>
          <span class="badge" style="background:#455a64;color:#fff"><?= count($materies) ?></span>
        </a>

        <!-- Comunes -->
        <div class="px-3 pt-2 pb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#888">
          Comunes
        </div>
        <?php foreach ($comuni as $m): ?>
        <?php $activa = ($filtreMateria == $m['id']); ?>
        <a href="?materia_id=<?= $m['id'] ?>"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
           style="<?= $activa ? 'background:#e8eaf6;border-left:4px solid #1a237e;font-weight:700' : 'border-left:4px solid transparent' ?>">
          <span style="font-size:.93rem">
            <?= htmlspecialchars($m['nom']) ?>
          </span>
          <span class="ms-1 d-flex gap-1 align-items-center flex-shrink-0">
            <span class="codi-exemplar" style="font-size:.75rem"><?= htmlspecialchars($m['codi']) ?></span>
            <?php if ($m['num_llibres'] > 0): ?>
            <span class="badge" style="background:#1565c0;color:#fff;font-size:.7rem"><?= $m['num_llibres'] ?></span>
            <?php endif; ?>
          </span>
        </a>
        <?php endforeach; ?>

        <!-- Optatives -->
        <div class="px-3 pt-2 pb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#888">
          Optatives
        </div>
        <?php foreach ($optativa as $m): ?>
        <?php $activa = ($filtreMateria == $m['id']); ?>
        <a href="?materia_id=<?= $m['id'] ?>"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
           style="<?= $activa ? 'background:#e8eaf6;border-left:4px solid #1a237e;font-weight:700' : 'border-left:4px solid transparent' ?>">
          <span style="font-size:.93rem">
            <?= htmlspecialchars($m['nom']) ?>
          </span>
          <span class="ms-1 d-flex gap-1 align-items-center flex-shrink-0">
            <span class="codi-exemplar" style="font-size:.75rem"><?= htmlspecialchars($m['codi']) ?></span>
            <?php if ($m['num_llibres'] > 0): ?>
            <span class="badge" style="background:#0277bd;color:#fff;font-size:.7rem"><?= $m['num_llibres'] ?></span>
            <?php endif; ?>
          </span>
        </a>
        <?php endforeach; ?>

      </div>
    </div>
  </div>

  <!-- ===================== PANEL DRET: LLIBRES ===================== -->
  <div class="col-md-9">

    <!-- Capçalera del panell dret -->
    <div class="d-flex align-items-center gap-2 mb-3">
      <?php if ($materiaActual): ?>
        <h4 class="fw-bold mb-0">
          <span class="codi-exemplar me-1"><?= htmlspecialchars($materiaActual['codi']) ?></span>
          <?= htmlspecialchars($materiaActual['nom']) ?>
          <small class="text-muted fw-normal ms-1" style="font-size:.8rem">(<?= count($llibres) ?> llibres)</small>
        </h4>
      <?php else: ?>
        <h4 class="fw-bold mb-0 text-muted">
          <i class="bi bi-journal-text me-1"></i> Tots els llibres
          <small class="fw-normal ms-1" style="font-size:.8rem">(<?= count($llibres) ?>)</small>
        </h4>
      <?php endif; ?>
      <div class="ms-auto">
        <?php if (Auth::rol() === 'admin'): ?>
        <a href="<?= BASE_URL ?>/llibres/nou.php<?= $filtreMateria ? '?materia_id='.$filtreMateria : '' ?>"
           class="btn btn-primary btn-sm">
          <i class="bi bi-plus-circle"></i> Nou llibre
        </a>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$filtreMateria && empty($llibres)): ?>
      <!-- Estat buit global -->
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="bi bi-journal-text" style="font-size:3rem;color:#ccc"></i>
          <p class="mt-3 text-muted">Seleccioneu una matèria per veure els llibres.</p>
        </div>
      </div>

    <?php elseif (empty($llibres)): ?>
      <!-- Matèria sense llibres -->
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="bi bi-journal-plus" style="font-size:2.5rem;color:#ccc"></i>
          <p class="mt-3 text-muted mb-3">Aquesta matèria no té cap llibre registrat.</p>
          <?php if (Auth::rol() === 'admin'): ?>
          <a href="<?= BASE_URL ?>/llibres/nou.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Afegir primer llibre
          </a>
          <?php endif; ?>
        </div>
      </div>

    <?php else: ?>
      <!-- Grid de targetes de llibres -->
      <div class="row g-3">
        <?php foreach ($llibres as $l):
          $total = (int)($l['num_exemplars'] ?? 0);
          $disp  = (int)($l['num_disponibles'] ?? 0);
          if ($total === 0)       { $color = '#9e9e9e'; $bg = '#f5f5f5'; }
          elseif ($disp === $total) { $color = '#2e7d32'; $bg = '#e8f5e9'; }
          elseif ($disp > 0)      { $color = '#1565c0'; $bg = '#e3f2fd'; }
          else                    { $color = '#c62828'; $bg = '#ffebee'; }
        ?>
        <div class="col-sm-6 col-xl-4">
          <div class="card h-100" style="border-top: 4px solid <?= $color ?>">
            <div class="card-body pb-2">

              <!-- Títol -->
              <div class="fw-bold mb-2 lh-sm" style="font-size:1rem">
                <?= htmlspecialchars($l['titol']) ?>
              </div>

              <!-- Badges de metadades -->
              <div class="d-flex flex-wrap gap-1 mb-3">
                <span class="codi-exemplar" style="font-size:.78rem"><?= htmlspecialchars($l['curs_codi']) ?></span>
                <?php if (!$filtreMateria): ?>
                <span class="badge" style="background:#e8eaf6;color:#283593;font-size:.75rem;font-weight:600">
                  <?= htmlspecialchars($l['mat_codi']) ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($l['isbn'])): ?>
                <span style="font-size:.78rem;color:#888">ISBN <?= htmlspecialchars($l['isbn']) ?></span>
                <?php endif; ?>
              </div>

              <?php if (!empty($l['editorial'])): ?>
              <div class="text-muted mb-2" style="font-size:.82rem">
                <i class="bi bi-building me-1"></i><?= htmlspecialchars($l['editorial']) ?>
              </div>
              <?php endif; ?>

              <!-- Comptador d'exemplars -->
              <div class="d-flex align-items-center gap-2 mt-auto pt-1"
                   style="border-top:1px solid #eee;margin-top:.5rem">
                <div style="text-align:center;min-width:48px">
                  <div style="font-size:1.6rem;font-weight:800;color:<?= $color ?>;line-height:1">
                    <?= $disp ?>
                  </div>
                  <div style="font-size:.7rem;color:#888;line-height:1.2">
                    disp. / <?= $total ?>
                  </div>
                </div>
                <div class="flex-fill">
                  <?php if ($total === 0): ?>
                    <span style="font-size:.8rem;color:#9e9e9e">Sense exemplars</span>
                  <?php elseif ($disp === $total): ?>
                    <span style="font-size:.8rem;color:#2e7d32">Tots disponibles</span>
                  <?php elseif ($disp > 0): ?>
                    <span style="font-size:.8rem;color:#1565c0"><?= $total - $disp ?> en préstec</span>
                  <?php else: ?>
                    <span style="font-size:.8rem;color:#c62828">Tots en préstec</span>
                  <?php endif; ?>
                </div>
              </div>

            </div>
            <div class="card-footer bg-transparent pt-2 pb-2 d-flex gap-1">
              <a href="<?= BASE_URL ?>/exemplars/exemplars.php?llibre_id=<?= $l['id'] ?>"
                 class="btn btn-sm btn-outline-primary flex-fill" title="Veure exemplars">
                <i class="bi bi-upc-scan"></i> Exemplars
              </a>
              <?php if (Auth::rol() === 'admin'): ?>
              <button class="btn btn-sm btn-outline-secondary"
                      onclick='obrirEditar(<?= json_encode([
                        'id'=>$l['id'],'titol'=>$l['titol'],'isbn'=>$l['isbn'] ?? '',
                        'editorial'=>$l['editorial'] ?? '','materia_id'=>$l['materia_id'],
                        'curs_id'=>$l['curs_id']
                      ]) ?>)'
                      title="Editar">
                <i class="bi bi-pencil"></i>
              </button>
              <?php if ($total == 0): ?>
              <form method="POST" class="d-inline"
                    onsubmit="return confirm('Eliminar «<?= htmlspecialchars($l['titol'], ENT_QUOTES) ?>»?')">
                <input type="hidden" name="accio" value="eliminar">
                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" title="Eliminar">
                  <i class="bi bi-trash"></i>
                </button>
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

<!-- ===================== MODAL EDITAR (admin) ===================== -->
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

<style>
.active-mat {
  background: #e8eaf6 !important;
  border-left: 4px solid #1a237e !important;
  font-weight: 700;
}
.list-group-item { border-left: 4px solid transparent; }
.list-group-item:hover { background: #f5f5f5; }
</style>

<?php include __DIR__ . '/layout_bottom.php'; ?>
