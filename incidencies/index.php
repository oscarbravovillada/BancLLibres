<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

Auth::requireLogin();

$titolPagina  = 'Incidències';
$paginaActiva = 'incidencies';

/* Marcar com a pagat */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_pagat'])) {
    Auth::csrfCheck();
    $inc_id = (int)($_POST['incidencia_id'] ?? 0);
    if ($inc_id) {
        Database::execute(
            "UPDATE incidencies SET pagat = 1, data_pagament = CURDATE() WHERE id = ?",
            [$inc_id]
        );
    }
    header('Location: ' . BASE_URL . '/incidencies/index.php?ok=pagat');
    exit;
}

$missatge = '';
if (isset($_GET['ok'])) $missatge = 'Incidència marcada com a pagada.';

if (Auth::rol() === 'admin') {
    $incidencies = Database::fetchAll(
        "SELECT i.*, a.nom, a.cognoms, c.nom AS classe_nom,
                e.codi AS exemplar_codi, l.titol
         FROM incidencies i
         JOIN alumnes a   ON i.alumne_id = a.id
         JOIN classes c   ON a.classe_id = c.id
         JOIN exemplars e ON i.exemplar_id = e.id
         JOIN llibres l   ON e.llibre_id = l.id
         ORDER BY i.data_incidencia DESC
         LIMIT 200"
    );
} else {
    $incidencies = Database::fetchAll(
        "SELECT i.*, a.nom, a.cognoms, c.nom AS classe_nom,
                e.codi AS exemplar_codi, l.titol
         FROM incidencies i
         JOIN alumnes a   ON i.alumne_id = a.id
         JOIN classes c   ON a.classe_id = c.id
         JOIN exemplars e ON i.exemplar_id = e.id
         JOIN llibres l   ON e.llibre_id = l.id
         JOIN professor_classe pc ON pc.classe_id = a.classe_id
         WHERE pc.professor_id = ?
         ORDER BY i.data_incidencia DESC
         LIMIT 200",
        [Auth::id()]
    );
}

include __DIR__ . '/../src/views/layout_top.php'; ?>

<?php if ($missatge): ?>
<div class="alert alert-success"><?= htmlspecialchars($missatge) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header-bl"><i class="bi bi-exclamation-triangle-fill"></i> Incidències</div>
  <div class="table-responsive">
    <table class="table table-bl mb-0">
      <thead>
        <tr>
          <th>Data</th>
          <th>Alumne/a</th>
          <th>Classe</th>
          <th>Exemplar</th>
          <th>Tipus</th>
          <th>Descripció</th>
          <th>Ha de pagar</th>
          <th>Import</th>
          <th>Estat pagament</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($incidencies as $i): ?>
        <tr>
          <td><?= date('d/m/Y', strtotime($i['data_incidencia'])) ?></td>
          <td>
            <a href="<?= BASE_URL ?>/prestecs/prestecs.php?id=<?= $i['alumne_id'] ?>">
              <?= htmlspecialchars($i['cognoms'] . ', ' . $i['nom']) ?>
            </a>
          </td>
          <td><?= htmlspecialchars($i['classe_nom']) ?></td>
          <td><span class="codi-exemplar"><?= htmlspecialchars($i['exemplar_codi']) ?></span></td>
          <td>
            <?php
            $etiquetes = [
                'perdua'            => 'Pèrdua',
                'deteriorament_greu'=> 'Deteriorament',
                'extraviu'          => 'Extraviu',
                'altre'             => 'Altre',
            ];
            echo htmlspecialchars($etiquetes[$i['tipus']] ?? $i['tipus']);
            ?>
          </td>
          <td><?= htmlspecialchars(mb_substr($i['descripcio'], 0, 60)) ?></td>
          <td class="text-center">
            <?= $i['ha_de_pagar'] ? '<span class="badge bg-danger">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?>
          </td>
          <td>
            <?= $i['ha_de_pagar'] && $i['import_pagament'] ? number_format($i['import_pagament'], 2) . ' €' : '—' ?>
          </td>
          <td class="text-center">
            <?php if (!$i['ha_de_pagar']): ?>
              <span class="text-muted">—</span>
            <?php elseif ($i['pagat']): ?>
              <span class="badge badge-estat-bo">Pagat <?= $i['data_pagament'] ? date('d/m/Y', strtotime($i['data_pagament'])) : '' ?></span>
            <?php else: ?>
              <span class="badge badge-estat-pendent">Pendent</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($i['ha_de_pagar'] && !$i['pagat']): ?>
            <form method="POST" style="display:inline">
  <?= Auth::csrfField() ?>
              <input type="hidden" name="incidencia_id" value="<?= $i['id'] ?>">
              <button type="submit" name="marcar_pagat" class="btn btn-sm btn-success"
                      onclick="return confirm('Marcar com a pagat?')">
                <i class="bi bi-cash-coin"></i> Pagat
              </button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$incidencies): ?>
        <tr><td colspan="10" class="text-center text-muted py-3">Cap incidència registrada.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../src/views/layout_bottom.php'; ?>
