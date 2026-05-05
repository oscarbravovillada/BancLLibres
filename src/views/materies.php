<?php include __DIR__ . '/layout_top.php'; ?>

<h2><i class="bi bi-bookmark"></i> Matèries</h2>

<ul class="list-group mt-3">
<?php foreach ($materies as $m): ?>
  <li class="list-group-item d-flex justify-content-between align-items-center">
    <?= htmlspecialchars($m['nom']) ?>
    <span class="badge bg-secondary"><?= htmlspecialchars($m['codi']) ?></span>
  </li>
<?php endforeach; ?>
</ul>

<?php include __DIR__ . '/layout_bottom.php'; ?>
