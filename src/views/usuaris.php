<?php include __DIR__ . '/layout_top.php'; ?>

<h2><i class="bi bi-person-badge"></i> Usuaris</h2>

<div class="table-responsive mt-3">
  <table class="table table-hover">
    <thead>
      <tr>
        <th>Nom</th>
        <th>Email</th>
        <th>Rol</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($usuaris as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['cognoms'] . ', ' . $u['nom']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><span class="badge bg-info"><?= htmlspecialchars($u['rol']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
