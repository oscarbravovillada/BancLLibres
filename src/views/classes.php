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
      <i class="bi bi-building me-2"></i>Classes
    </h3>
    <p class="text-muted mb-0 mt-1" style="font-size:.9rem">
      <?= array_sum(array_map(fn($g) => count($g['classes']), $grups)) ?> classes
      · <?= array_sum(array_column($classes, 'num_alumnes')) ?> alumnes
      · curs <?= ANY_ESCOLAR ?>
    </p>
  </div>
</div>

<!-- Acordió per etapes -->
<div class="accordion" id="acordioClasses">

<?php $primerObert = true; ?>
<?php foreach ($grups as $clau => $grup): ?>

<?php
  $totalAlumnes = array_sum(array_column($grup['classes'], 'num_alumnes'));
  $id = 'grup-' . strtolower($clau);
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
  <div id="<?= $id ?>" class="accordion-collapse collapse <?= $obert ? 'show' : '' ?>"
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

<?php include __DIR__ . '/layout_bottom.php'; ?>
