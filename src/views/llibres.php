<?php include __DIR__ . '/layout_top.php'; ?>

<?php if ($missatge): ?>
<div class="alert alert-success"><?= htmlspecialchars($missatge) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<!-- Capçalera + botons -->
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <?php if ($materiaActual): ?>
    <a href="<?= BASE_URL ?>/llibres/llibres.php" class="btn btn-secondary btn-sm">
      <i class="bi bi-arrow-left"></i> Totes les matèries
    </a>
    <span class="fw-bold"><?= htmlspecialchars($materiaActual['nom']) ?></span>
  <?php endif; ?>
  <div class="ms-auto">
    <?php if (Auth::rol() === 'admin'): ?>
    <a href="<?= BASE_URL ?>/llibres/nou.php" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-circle"></i> Nou llibre
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header-bl">
    <i class="bi bi-journal-text"></i>
    Llibres
    <small class="ms-2 fw-normal opacity-75">(<?= count($llibres) ?>)</small>
  </div>
  <div class="table-responsive">
    <table class="table table-bl mb-0">
      <thead>
        <tr>
          <th>Títol</th>
          <th>Matèria</th>
          <th>Curs</th>
          <th>ISBN</th>
          <th class="text-center">Exemplars</th>
          <th class="text-center">Disponibles</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($llibres as $l): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($l['titol']) ?></td>
          <td>
            <a href="?materia_id=<?= $l['materia_id'] ?>" class="text-decoration-none">
              <?= htmlspecialchars($l['materia_nom']) ?>
            </a>
          </td>
          <td><span class="codi-exemplar"><?= htmlspecialchars($l['curs_codi']) ?></span></td>
          <td><span style="font-size:.85rem;color:#555"><?= htmlspecialchars($l['isbn'] ?: '—') ?></span></td>
          <td class="text-center">
            <?php if ($l['num_exemplars'] > 0): ?>
              <a href="<?= BASE_URL ?>/exemplars/exemplars.php?llibre_id=<?= $l['id'] ?>"
                 class="badge badge-estat-actiu text-decoration-none">
                <?= $l['num_exemplars'] ?>
              </a>
            <?php else: ?>
              <span class="text-muted">0</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <?php
            $disp = (int)($l['num_disponibles'] ?? 0);
            $total = (int)($l['num_exemplars'] ?? 0);
            $color = $disp === 0 && $total > 0 ? 'badge-estat-perdut' : 'badge-estat-bo';
            echo $total > 0
              ? "<span class=\"badge {$color}\">{$disp}</span>"
              : '<span class="text-muted">—</span>';
            ?>
          </td>
          <td class="text-end">
            <a href="<?= BASE_URL ?>/exemplars/exemplars.php?llibre_id=<?= $l['id'] ?>"
               class="btn btn-sm btn-outline-primary" title="Veure exemplars">
              <i class="bi bi-upc-scan"></i>
            </a>
            <?php if (Auth::rol() === 'admin'): ?>
            <button class="btn btn-sm btn-outline-warning"
                    onclick='obrirEditar(<?= json_encode([
                      'id'=>$l['id'],'titol'=>$l['titol'],'isbn'=>$l['isbn'] ?? '',
                      'editorial'=>$l['editorial'] ?? '','materia_id'=>$l['materia_id'],
                      'curs_id'=>$l['curs_id']
                    ]) ?>)'
                    title="Editar">
              <i class="bi bi-pencil"></i>
            </button>
            <?php if ($l['num_exemplars'] == 0): ?>
            <form method="POST" class="d-inline"
                  onsubmit="return confirm('Eliminar el llibre «<?= htmlspecialchars($l['titol'], ENT_QUOTES) ?>»?')">
              <input type="hidden" name="accio" value="eliminar">
              <input type="hidden" name="id" value="<?= $l['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                <i class="bi bi-trash"></i>
              </button>
            </form>
            <?php else: ?>
            <button class="btn btn-sm btn-outline-danger" disabled title="Té exemplars associats">
              <i class="bi bi-trash"></i>
            </button>
            <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$llibres): ?>
        <tr><td colspan="7" class="text-center text-muted py-3">Cap llibre trobat.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
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
        <div class="mb-3">
          <label class="form-label">ISBN</label>
          <input type="text" name="isbn" id="editIsbn" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Editorial</label>
          <input type="text" name="editorial" id="editEditorial" class="form-control">
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
  document.getElementById('editId').value       = l.id;
  document.getElementById('editTitol').value    = l.titol;
  document.getElementById('editIsbn').value     = l.isbn;
  document.getElementById('editEditorial').value = l.editorial;
  document.getElementById('editMateria').value  = l.materia_id;
  document.getElementById('editCurs').value     = l.curs_id;
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>
<?php endif; ?>

<?php include __DIR__ . '/layout_bottom.php'; ?>
