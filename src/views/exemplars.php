<?php include __DIR__ . '/layout_top.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2><i class="bi bi-book"></i> Exemplars
      <?= $llibreFiltrat ? ' — <small class="text-muted">'.htmlspecialchars($llibreFiltrat['titol']).'</small>' : '' ?>
    </h2>

    <a href="<?= BASE_URL ?>/llibres/llibres.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Tornar a llibres
    </a>
  </div>

  <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalExemplar">
    <i class="bi bi-plus"></i> Nou exemplar
  </button>
</div>

<!-- FILTRES -->
<form class="row g-2 mb-3" method="get">
  <div class="col-md-4">
    <select name="llibre_id" class="form-select">
      <option value="">Tots els llibres</option>
      <?php foreach ($totsLlibres as $ll): ?>
        <option value="<?= $ll['id'] ?>" <?= $filtreLlibre==$ll['id']?'selected':'' ?>>
          <?= htmlspecialchars($ll['mat_nom']) ?> — <?= htmlspecialchars($ll['titol']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="estat" class="form-select">
      <option value="">Tots els estats</option>
      <?php foreach (['nou','bo','deteriorat','perdut'] as $e): ?>
        <option value="<?= $e ?>" <?= $filtreEstat===$e?'selected':'' ?>>
          <?= ucfirst($e) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="disponible" class="form-select">
      <option value="-1">Disponibilitat</option>
      <option value="1" <?= $filtreDisp===1?'selected':'' ?>>Disponibles</option>
      <option value="0" <?= $filtreDisp===0?'selected':'' ?>>No disponibles</option>
    </select>
  </div>

  <div class="col-md-2">
    <button class="btn btn-outline-primary w-100">
      <i class="bi bi-search"></i> Filtrar
    </button>
  </div>
</form>

<!-- TAULA -->
<div class="table-responsive">
  <table class="table table-bl table-hover">
    <thead>
      <tr>
        <th>Codi</th><th>Títol</th><th>Matèria</th><th>Curs</th>
        <th>Estat</th><th>Disponible</th><th>Desperfectes</th><th>Accions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($exemplars as $e): ?>
      <tr>
        <td><code><?= htmlspecialchars($e['codi']) ?></code></td>
        <td><?= htmlspecialchars($e['ll_titol']) ?></td>
        <td><?= htmlspecialchars($e['mat_nom']) ?></td>
        <td><?= htmlspecialchars($e['curs_codi']) ?></td>

        <td>
          <?php $estat = strtolower(trim($e['estat'])); ?>
          <span class="badge badge-estat-<?= $estat ?>">
              <?= ucfirst($estat) ?>
          </span>

        </td>

        <td>
          <?php if ($e['disponible']): ?>
            <span class="badge badge-estat-bo"><i class="bi bi-check-lg"></i> Sí</span>
          <?php else: ?>
            <span class="badge badge-estat-perdut"><i class="bi bi-x-lg"></i> No</span>
          <?php endif; ?>
        </td>

        <td><?= htmlspecialchars(mb_substr($e['desperfectes'] ?? '', 0, 60)) ?></td>

        <td>
          <button class="btn btn-sm btn-outline-warning"
                  onclick='editarExemplar(<?= json_encode($e) ?>)'>
            <i class="bi bi-pencil"></i>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>

      <?php if (!$exemplars): ?>
        <tr><td colspan="8" class="text-center text-muted">Cap exemplar trobat.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- MODAL CREAR -->
<div class="modal fade" id="modalExemplar" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header card-header-bl" style="border-radius:0">
        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nou exemplar</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="accio" value="crear">

        <div class="mb-3">
          <label class="form-label">Llibre *</label>
          <select name="llibre_id" class="form-select" required>
            <option value="">Selecciona...</option>
            <?php foreach ($totsLlibres as $ll): ?>
              <option value="<?= $ll['id'] ?>" <?= $filtreLlibre==$ll['id']?'selected':'' ?>>
                <?= htmlspecialchars($ll['mat_nom']) ?> — <?= htmlspecialchars($ll['titol']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Estat inicial</label>
          <select name="estat" class="form-select">
            <?php foreach (['nou','bo','deteriorat'] as $e): ?>
              <option value="<?= $e ?>"><?= ucfirst($e) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Quantitat a crear</label>
          <input type="number" name="quantitat" class="form-control" value="1" min="1" max="50">
        </div>

        <div class="mb-3">
          <label class="form-label">Desperfectes preexistents</label>
          <textarea name="desperfectes" class="form-control" rows="2"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel·lar</button>
        <button type="submit" class="btn btn-success">Crear exemplar(s)</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEditExemplar" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header card-header-bl" style="border-radius:0">
        <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar exemplar</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="accio" value="editar">
        <input type="hidden" name="id" id="editExId">
        <input type="hidden" name="llibre_id" id="editExLlibreId">

        <div class="mb-2">
          <strong>Codi:</strong> <code id="editExCodi"></code>
        </div>

        <div class="mb-3">
          <label class="form-label">Estat</label>
          <select name="estat" id="editExEstat" class="form-select">
            <?php foreach (['nou','bo','deteriorat','perdut'] as $e): ?>
              <option value="<?= $e ?>"><?= ucfirst($e) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Desperfectes</label>
          <textarea name="desperfectes" id="editExDesp" class="form-control" rows="2"></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Notes internes</label>
          <textarea name="notes" id="editExNotes" class="form-control" rows="2"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel·lar</button>
        <button type="submit" class="btn btn-warning">Guardar canvis</button>
      </div>
    </form>
  </div>
</div>

<script>
function editarExemplar(e) {
  document.getElementById('editExId').value       = e.id;
  document.getElementById('editExLlibreId').value = e.llibre_id;
  document.getElementById('editExCodi').textContent = e.codi;
  document.getElementById('editExEstat').value    = e.estat;
  document.getElementById('editExDesp').value     = e.desperfectes || '';
  document.getElementById('editExNotes').value    = e.notes || '';

  new bootstrap.Modal(document.getElementById('modalEditExemplar')).show();
}
</script>

<?php include __DIR__ . '/layout_bottom.php'; ?>
