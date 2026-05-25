<?php include __DIR__ . '/layout_top.php'; ?>

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
      <i class="bi bi-tags-fill me-2"></i>Matèries
    </h3>
    <p class="text-muted mb-0 mt-1" style="font-size:.9rem">
      <?= count($materies) ?> matèries
      · <?= array_sum(array_column($materies, 'num_llibres')) ?> títols actius
    </p>
  </div>
  <?php if (Auth::rol() === 'admin'): ?>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaMateria">
    <i class="bi bi-plus-circle me-1"></i> Nova matèria
  </button>
  <?php endif; ?>
</div>

<!-- Acordió per etapes -->
<div class="accordion" id="acordioMateries">

<?php $primerObert = true; ?>
<?php foreach ($grups as $clau => $grup): ?>

<?php
  $totalLlibres = array_sum(array_column($grup['materies'], 'num_llibres'));
  $id = 'grup-mat-' . strtolower($clau);
  $obert = $primerObert;
  if ($primerObert) $primerObert = false;
?>

<div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-sm">

  <!-- Capçalera del grup -->
  <h2 class="accordion-header">
    <button class="accordion-button <?= $obert ? '' : 'collapsed' ?> fw-semibold py-3"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#<?= $id ?>"
            style="background:<?= $grup['color'] ?>18;color:<?= $grup['color'] ?>;font-size:1rem">
      <i class="bi <?= $grup['icon'] ?> me-2" style="font-size:1.1rem"></i>
      <?= htmlspecialchars($grup['label']) ?>
      <span class="ms-3 d-flex gap-2">
        <span class="badge rounded-pill" style="background:<?= $grup['color'] ?>;color:#fff;font-size:.72rem;font-weight:600">
          <?= count($grup['materies']) ?> matèria<?= count($grup['materies']) != 1 ? 'ies' : '' ?>
        </span>
        <?php if ($totalLlibres > 0): ?>
        <span class="badge rounded-pill" style="background:<?= $grup['color'] ?>33;color:<?= $grup['color'] ?>;font-size:.72rem;font-weight:600;border:1px solid <?= $grup['color'] ?>55">
          <?= $totalLlibres ?> títol<?= $totalLlibres != 1 ? 's' : '' ?>
        </span>
        <?php endif; ?>
      </span>
    </button>
  </h2>

  <!-- Contingut del grup -->
  <div id="<?= $id ?>" class="accordion-collapse collapse <?= $obert ? 'show' : '' ?>"
       data-bs-parent="#acordioMateries">
    <div class="accordion-body pt-3 pb-4" style="background:<?= $grup['color'] ?>08">

      <div class="row g-3">
        <?php foreach ($grup['materies'] as $m): ?>
        <div class="col-sm-6 col-lg-4 col-xl-3">
          <div class="materia-card h-100" style="--color:<?= $grup['color'] ?>">

            <div class="materia-card-header">
              <span class="codi-exemplar" style="font-size:.75rem"><?= htmlspecialchars($m['codi']) ?></span>
              <?php if ($m['num_llibres'] > 0): ?>
              <a href="<?= BASE_URL ?>/llibres/llibres.php?materia_id=<?= $m['id'] ?>"
                 class="materia-card-badge text-decoration-none"
                 style="background:<?= $grup['color'] ?>22;color:<?= $grup['color'] ?>">
                <i class="bi bi-book me-1"></i><?= $m['num_llibres'] ?>
              </a>
              <?php else: ?>
              <span class="materia-card-badge" style="background:#f5f5f5;color:#aaa">0 llibres</span>
              <?php endif; ?>
            </div>

            <div class="materia-card-nom"><?= htmlspecialchars($m['nom']) ?></div>

            <?php if (!empty($m['curs_codi'])): ?>
            <div class="materia-card-curs">
              <i class="bi bi-bookmark me-1" style="color:<?= $grup['color'] ?>"></i>
              <?= htmlspecialchars($m['curs_codi']) ?> — <?= htmlspecialchars($m['curs_nom']) ?>
            </div>
            <?php else: ?>
            <div class="materia-card-curs text-muted fst-italic" style="font-size:.78rem">Sense curs assignat</div>
            <?php endif; ?>

            <?php if (Auth::rol() === 'admin'): ?>
            <div class="materia-card-footer">
              <button class="btn btn-sm flex-fill fw-semibold"
                      style="background:<?= $grup['color'] ?>;color:#fff;border:none"
                      onclick='obrirEditar(<?= json_encode([
                          'id'      => $m['id'],
                          'nom'     => $m['nom'],
                          'codi'    => $m['codi'],
                          'tipus'   => $m['tipus'],
                          'curs_id' => $m['curs_id'],
                      ]) ?>)'>
                <i class="bi bi-pencil me-1"></i>Editar
              </button>
              <?php if ($m['num_llibres'] == 0): ?>
              <form method="POST" class="m-0"
                    onsubmit="return confirm('Eliminar la matèria «<?= htmlspecialchars($m['nom'], ENT_QUOTES) ?>»?')">
                <input type="hidden" name="accio" value="eliminar">
                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
              <?php else: ?>
              <button class="btn btn-sm btn-outline-danger" disabled title="Té llibres associats">
                <i class="bi bi-trash"></i>
              </button>
              <?php endif; ?>
            </div>
            <?php endif; ?>

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
    <i class="bi bi-tags" style="font-size:3rem;color:#ccc"></i>
    <p class="mt-3 text-muted">No hi ha matèries registrades.</p>
  </div>
</div>
<?php endif; ?>

<?php if (Auth::rol() === 'admin'): ?>

<!-- ===================== MODAL NOVA MATÈRIA ===================== -->
<div class="modal fade" id="modalNovaMateria" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="accio" value="crear">
      <div class="modal-header card-header-bl" style="border-radius:0">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i> Nova matèria</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Nom *</label>
          <input type="text" name="nom" class="form-control" required placeholder="p.ex. Sistemes Informàtics">
        </div>
        <div class="row g-3 mb-3">
          <div class="col-sm-4">
            <label class="form-label fw-semibold">Codi *</label>
            <input type="text" name="codi" class="form-control text-uppercase" required
                   maxlength="10" placeholder="SI">
            <div class="form-text">Curt, p.ex. SI, MAT, FH</div>
          </div>
          <div class="col-sm-8">
            <label class="form-label fw-semibold">Tipus</label>
            <select name="tipus" class="form-select">
              <option value="comuna">Comuna (tothom la cursa)</option>
              <option value="optativa">Optativa</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">
            Curs assignat
            <span class="text-muted fw-normal">(determina el grup al llistat)</span>
          </label>
          <select name="curs_id" class="form-select">
            <option value="">— Sense assignar —</option>
            <?php foreach ($cursos as $cu): ?>
            <option value="<?= $cu['id'] ?>"><?= htmlspecialchars($cu['codi']) ?> — <?= htmlspecialchars($cu['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel·lar</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Crear</button>
      </div>
    </form>
  </div>
</div>

<!-- ===================== MODAL EDITAR MATÈRIA ===================== -->
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="accio" value="editar">
      <input type="hidden" name="id" id="editId">
      <div class="modal-header card-header-bl" style="border-radius:0">
        <h5 class="modal-title"><i class="bi bi-pencil me-1"></i> Editar matèria</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Nom *</label>
          <input type="text" name="nom" id="editNom" class="form-control" required>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-sm-4">
            <label class="form-label fw-semibold">Codi *</label>
            <input type="text" name="codi" id="editCodi" class="form-control text-uppercase" required maxlength="10">
          </div>
          <div class="col-sm-8">
            <label class="form-label fw-semibold">Tipus</label>
            <select name="tipus" id="editTipus" class="form-select">
              <option value="comuna">Comuna</option>
              <option value="optativa">Optativa</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">
            Curs assignat
            <span class="text-muted fw-normal">(determina el grup al llistat)</span>
          </label>
          <select name="curs_id" id="editCursId" class="form-select">
            <option value="">— Sense assignar —</option>
            <?php foreach ($cursos as $cu): ?>
            <option value="<?= $cu['id'] ?>"><?= htmlspecialchars($cu['codi']) ?> — <?= htmlspecialchars($cu['nom']) ?></option>
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
function obrirEditar(m) {
  document.getElementById('editId').value     = m.id;
  document.getElementById('editNom').value    = m.nom;
  document.getElementById('editCodi').value   = m.codi;
  document.getElementById('editTipus').value  = m.tipus;
  const sel = document.getElementById('editCursId');
  sel.value = m.curs_id ?? '';
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>

<?php endif; ?>

<style>
.accordion-button::after { filter: none !important; }
.accordion-button:focus  { box-shadow: none; }
.accordion-button:not(.collapsed) { box-shadow: none; }

.materia-card {
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
.materia-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,.1);
  transform: translateY(-2px);
}
.materia-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .5rem;
}
.materia-card-badge {
  font-size: .72rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 20px;
  white-space: nowrap;
}
.materia-card-nom {
  font-size: 1rem;
  font-weight: 700;
  color: #1a237e;
  line-height: 1.25;
  flex-grow: 1;
}
.materia-card-curs {
  font-size: .8rem;
  color: #555;
}
.materia-card-footer {
  display: flex;
  gap: .4rem;
  margin-top: .5rem;
  padding-top: .6rem;
  border-top: 1px solid #f0f0f0;
}
</style>

<?php include __DIR__ . '/layout_bottom.php'; ?>
