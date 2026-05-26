<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/helpers/Database.php';
require_once __DIR__ . '/src/helpers/Auth.php';

Auth::requireLogin();
Auth::requireAdmin();

$titolPagina  = 'Usuaris';
$paginaActiva = 'usuaris';

$missatge = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::csrfCheck();
    $accio = $_POST['accio'] ?? '';

    /* CREAR */
    if ($accio === 'crear') {
        $nom      = trim($_POST['nom']      ?? '');
        $cognoms  = trim($_POST['cognoms']  ?? '');
        $email    = trim($_POST['email']    ?? '');
        $rol      = $_POST['rol'] ?? 'professor';
        $password = trim($_POST['password'] ?? '');

        if ($nom === '' || $cognoms === '' || $email === '' || $password === '') {
            $errorMsg = 'Tots els camps marcats amb * són obligatoris.';
        } elseif (Database::fetchOne("SELECT id FROM usuaris WHERE email = ?", [$email])) {
            $errorMsg = "Ja existeix un usuari amb el correu {$email}.";
        } else {
            /* Generar username únic: nom.cognom1 */
            $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $cognoms)[0])) . '.'
                  . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nom));
            $username = $base;
            $i = 2;
            while (Database::fetchOne("SELECT id FROM usuaris WHERE username = ?", [$username])) {
                $username = $base . $i++;
            }

            Database::execute(
                "INSERT INTO usuaris (username, password, nom, cognoms, email, rol, actiu)
                 VALUES (?,?,?,?,?,?,1)",
                [$username, password_hash($password, PASSWORD_DEFAULT), $nom, $cognoms, $email, $rol]
            );
            $missatge = "Usuari {$nom} {$cognoms} creat. Username: {$username}";
        }
    }

    /* CANVIAR ROL */
    if ($accio === 'rol') {
        $id  = (int)($_POST['id']  ?? 0);
        $rol = $_POST['rol'] ?? '';
        if ($id && in_array($rol, ['admin', 'professor'])) {
            if ($id === Auth::id()) {
                $errorMsg = "No podeu canviar el vostre propi rol.";
            } else {
                Database::execute("UPDATE usuaris SET rol = ? WHERE id = ?", [$rol, $id]);
                $missatge = "Rol actualitzat.";
            }
        }
    }

    /* ACTIVAR / DESACTIVAR */
    if ($accio === 'toggle_actiu') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $id !== Auth::id()) {
            Database::execute(
                "UPDATE usuaris SET actiu = NOT actiu WHERE id = ?", [$id]
            );
            $missatge = "Estat de l'usuari actualitzat.";
        } else {
            $errorMsg = "No podeu desactivar el vostre propi compte.";
        }
    }

    /* RESET CONTRASENYA */
    if ($accio === 'reset_password') {
        $id       = (int)($_POST['id']       ?? 0);
        $password = trim($_POST['password']  ?? '');
        if ($id && strlen($password) >= 4) {
            Database::execute(
                "UPDATE usuaris SET password = ? WHERE id = ?",
                [password_hash($password, PASSWORD_DEFAULT), $id]
            );
            $missatge = "Contrasenya restablerta.";
        } else {
            $errorMsg = "La contrasenya ha de tenir almenys 4 caràcters.";
        }
    }

    if (!$errorMsg) {
        header('Location: ' . BASE_URL . '/usuaris.php?ok=1');
        exit;
    }
}

if (isset($_GET['ok'])) $missatge = 'Operació completada correctament.';

$usuaris = Database::fetchAll(
    "SELECT id, username, nom, cognoms, email, rol, actiu, created_at
     FROM usuaris ORDER BY cognoms, nom"
);

include __DIR__ . '/src/views/layout_top.php'; ?>

<?php if ($missatge): ?>
<div class="alert alert-success"><?= htmlspecialchars($missatge) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<div class="row g-4">

  <!-- Llista d'usuaris -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header-bl"><i class="bi bi-person-gear"></i> Usuaris (<?= count($usuaris) ?>)</div>
      <div class="table-responsive">
        <table class="table table-bl mb-0">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Username</th>
              <th>Email</th>
              <th>Rol</th>
              <th class="text-center">Actiu</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuaris as $u): ?>
            <tr <?= !$u['actiu'] ? 'style="opacity:.5"' : '' ?>>
              <td class="fw-semibold"><?= htmlspecialchars($u['cognoms'] . ', ' . $u['nom']) ?></td>
              <td><span class="codi-exemplar"><?= htmlspecialchars($u['username']) ?></span></td>
              <td style="font-size:.9rem"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
              <td>
                <?php if ($u['rol'] === 'admin'): ?>
                  <span class="badge badge-estat-perdut">Admin</span>
                <?php else: ?>
                  <span class="badge badge-estat-actiu">Professor</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?= $u['actiu']
                  ? '<span class="badge badge-estat-bo">Sí</span>'
                  : '<span class="badge" style="background:#9e9e9e;color:#fff">No</span>' ?>
              </td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-warning"
                        onclick='obrirModal(<?= json_encode(['id'=>$u['id'],'nom'=>$u['nom'],'cognoms'=>$u['cognoms'],'rol'=>$u['rol'],'actiu'=>(int)$u['actiu']]) ?>)'
                        title="Gestionar">
                  <i class="bi bi-gear"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Formulari nou usuari -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header-bl"><i class="bi bi-person-plus"></i> Nou usuari</div>
      <div class="card-body">
        <form method="POST">
  <?= Auth::csrfField() ?>
          <input type="hidden" name="accio" value="crear">
          <div class="mb-3">
            <label class="form-label">Nom *</label>
            <input type="text" name="nom" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Cognoms *</label>
            <input type="text" name="cognoms" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Rol</label>
            <select name="rol" class="form-select">
              <option value="professor">Professor/a</option>
              <option value="admin">Administrador</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="form-label">Contrasenya inicial *</label>
            <input type="text" name="password" class="form-control" required
                   placeholder="Mínim 4 caràcters">
            <div class="form-text">L'usuari hauria de canviar-la en el primer accés.</div>
          </div>
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-person-plus"></i> Crear usuari
          </button>
        </form>
      </div>
    </div>
  </div>

</div>

<!-- Modal gestionar usuari -->
<div class="modal fade" id="modalGestionar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header card-header-bl" style="border-radius:0">
        <h5 class="modal-title" id="modalTitol"><i class="bi bi-gear"></i> Gestionar usuari</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- Canviar rol -->
        <form method="POST" class="mb-4">
  <?= Auth::csrfField() ?>
          <input type="hidden" name="accio" value="rol">
          <input type="hidden" name="id" id="gId">
          <label class="form-label fw-semibold">Canviar rol</label>
          <div class="d-flex gap-2">
            <select name="rol" id="gRol" class="form-select">
              <option value="professor">Professor/a</option>
              <option value="admin">Administrador</option>
            </select>
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </form>

        <!-- Reset contrasenya -->
        <form method="POST" class="mb-4">
  <?= Auth::csrfField() ?>
          <input type="hidden" name="accio" value="reset_password">
          <input type="hidden" name="id" id="gId2">
          <label class="form-label fw-semibold">Restablir contrasenya</label>
          <div class="d-flex gap-2">
            <input type="text" name="password" class="form-control" placeholder="Nova contrasenya" required>
            <button type="submit" class="btn btn-warning">Restablir</button>
          </div>
        </form>

        <!-- Activar/desactivar -->
        <form method="POST">
  <?= Auth::csrfField() ?>
          <input type="hidden" name="accio" value="toggle_actiu">
          <input type="hidden" name="id" id="gId3">
          <button type="submit" id="gToggleBtn" class="btn btn-outline-danger w-100">
            <i class="bi bi-person-slash"></i> Desactivar compte
          </button>
        </form>

      </div>
    </div>
  </div>
</div>

<script>
function obrirModal(u) {
  document.getElementById('modalTitol').innerHTML =
    '<i class="bi bi-gear"></i> ' + u.cognoms + ', ' + u.nom;
  ['gId','gId2','gId3'].forEach(id => document.getElementById(id).value = u.id);
  document.getElementById('gRol').value = u.rol;
  const btn = document.getElementById('gToggleBtn');
  btn.innerHTML = u.actiu
    ? '<i class="bi bi-person-slash"></i> Desactivar compte'
    : '<i class="bi bi-person-check"></i> Activar compte';
  btn.className = u.actiu ? 'btn btn-outline-danger w-100' : 'btn btn-outline-success w-100';
  new bootstrap.Modal(document.getElementById('modalGestionar')).show();
}
</script>

<?php include __DIR__ . '/src/views/layout_bottom.php'; ?>
