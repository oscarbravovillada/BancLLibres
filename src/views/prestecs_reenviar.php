<?php include __DIR__ . '/layout_top.php'; ?>

<h3>Reenviar documents</h3>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Tipus</th>
            <th>Data</th>
            <th>Acció</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($albarans as $a): ?>
            <tr>
                <td><?= ucfirst($a['tipus']) ?></td>
                <td><?= $a['data'] ?></td>
                <td>
                    <a href="prestecs_reenviar.php?alumne_id=<?= $alumne_id ?>&reenviar=<?= $a['id'] ?>"
                       class="btn btn-info text-white">
                        Reenviar
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include __DIR__ . '/layout_bottom.php'; ?>
