<?php include __DIR__ . '/layout_top.php'; ?>

<?php if ($missatge): ?>
<div class="alert alert-success"><?= htmlspecialchars($missatge) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<div class="row g-4">

  <!-- Llista de matèries -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header-bl"><i class="bi bi-tags-fill"></i> Matèries (<?= count($materies) ?>)</div>
      <div class="table-responsive">
        <table class="table table-bl mb-0">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Codi</th>
              <th>Tipus</th>
              <th class="text-center">Llibres</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($materies as $m): ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($m['nom']) ?></td>
              <td><span class="codi-exemplar"><?= htmlspecialchars($m['codi']) ?></span></td>
              <td>
                <?php if ($m['tipus'] === 'optativa'): ?>
                  <span class="badge" style="background:#0277bd;color:#fff">Optativa</span>
                <?php else: ?>
                  <span class="badge" style="background:#2e7d32;color:#fff">Comuna</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ($m['num_llibres'] > 0): ?>
                  <a href="<?= BASE_URL ?>/llibres/llibres.php?materia_id=<?= $m['id'] ?>"
                     class="badge badge-estat-actiu text-decoration-none">
                    <?= $m['num_llibres'] ?>
                  </a>
                <?php else: ?>
                  <span class="text-muted">0</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?php if (Auth::rol() === 'admin'): ?>
                <button class="btn btn-sm btn-outline-warning"
                        onclick='obrirEditar(<?= json_encode(['id'=>$m['id'],'nom'=>$m['nom'],'codi'=>$m['codi'],'tipus'=>$m['tipus']]) ?>)'>
                  <i class="bi bi-pencil"></i>
                </button>
                <?php if ($m['num_llibres'] == 0): ?>
                <form method="POST" class="d-inline"
                      onsubmit="return confirm('Eliminar la matèria «<?= htmlspecialchars($m['nom'], ENT_QUOTES) ?>»?')">
                  <input type="hidden" name="accio" value="eliminar">
                  <input type="hidden" name="id" value="<?= $m['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
                <?php else: ?>
                <button class="btn btn-sm btn-outline-danger" disabled title="Té llibres associats">
                  <i class="bi bi-trash"></i>
                </button>
                <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$materies): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">Cap matèria creada.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Formulari nova matèria (admin) -->
  <?php if (Auth::rol() === 'admin'): ?>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header-bl"><i class="bi bi-plus-circle"></i> Nova matèria</div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="accio" value="crear">
          <div class="mb-3">
            <label class="form-label">Nom *</label>
            <input type="text" name="nom" class="form-control" required placeholder="p.ex. Sistemes Informàtics">
          </div>
          <div class="mb-3">
            <label class="form-label">Codi *</label>
            <input type="text" name="codi" class="form-control" required placeholder="p.ex. SI"
                   maxlength="10" style="text-transform:uppercase">
            <div class="form-text">Codi curt que s'usarà en la codificació dels exemplars.</div>
          </div>
          <div class="mb-4">
            <label class="form-label">Tipus</label>
            <select name="tipus" class="form-select">
              <option value="comuna">Comuna (tothom la cursa)</option>
              <option value="optativa">Optativa</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-plus-circle"></i> Crear matèria
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<!-- Modal editar (admin) -->
<?php if (Auth::rol() === 'admin'): ?>
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header card-header-bl" style="border-radius:0">
        <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar matèria</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="accio" value="editar">
        <input type="hidden" name="id" id="editId">
        <div class="mb-3">
          <label class="form-label">Nom *</label>
          <input type="text" name="nom" id="editNom" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Codi *</label>
          <input type="text" name="codi" id="editCodi" class="form-control" required maxlength="10">
        </div>
        <div class="mb-3">
          <label class="form-label">Tipus</label>
          <select name="tipus" id="editTipus" class="form-select">
            <option value="comuna">Comuna</option>
            <option value="optativa">Optativa</option>
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
  document.getElementById('editId').value    = m.id;
  document.getElementById('editNom').value   = m.nom;
  document.getElementById('editCodi').value  = m.codi;
  document.getElementById('editTipus').value = m.tipus;
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>
<?php endif; ?>

<?php include __DIR__ . '/layout_bottom.php'; ?>
