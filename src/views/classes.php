<?php include __DIR__ . '/layout_top.php'; ?>

<h2><i class="bi bi-people"></i> Classes</h2>

<?php if ($missatge): ?>
  <div class="alert alert-success mt-3"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($missatge) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
  <div class="alert alert-danger mt-3"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<div class="row g-3 mt-3">
<?php foreach ($classes as $c): ?>
  <div class="col-sm-6 col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title"><?= htmlspecialchars($c['nom']) ?></h5>
        <p class="text-muted mb-1"><?= htmlspecialchars($c['curs_nom']) ?></p>
        <p class="small">
          <i class="bi bi-person-badge"></i>
          Tutor: <?= htmlspecialchars($c['tutor_nom'] ?: '—') ?>
        </p>
      </div>
      <div class="card-footer bg-transparent d-flex gap-2">
        <a href="<?= BASE_URL ?>/alumnes/llista.php?classe_id=<?= $c['id'] ?>"
           class="btn btn-primary btn-sm flex-grow-1">
          <i class="bi bi-people-fill"></i> Veure alumnes
        </a>
        <?php if (Auth::rol() === 'admin'): ?>
        <form method="POST" action="<?= BASE_URL ?>/classes/classes.php" class="m-0"
              onsubmit="return confirm('Segur que vols eliminar la classe «<?= htmlspecialchars($c['nom'], ENT_QUOTES) ?>»?\nAquesta acció no es pot desfer.')">
          <input type="hidden" name="accio" value="eliminar">
          <input type="hidden" name="id" value="<?= $c['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm" title="Eliminar classe">
            <i class="bi bi-trash"></i>
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
