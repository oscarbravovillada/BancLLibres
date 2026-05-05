<?php include __DIR__ . '/layout_top.php'; ?>

<h2><i class="bi bi-people"></i> Classes</h2>

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
      <div class="card-footer bg-transparent">
        <a href="<?= BASE_URL ?>/alumnes/llista.php?classe_id=<?= $c['id'] ?>"
           class="btn btn-primary btn-sm w-100">
          <i class="bi bi-people-fill"></i> Veure alumnes
        </a>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
