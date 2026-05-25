<?php include __DIR__ . '/layout_top.php'; ?>

<div class="row g-4">

  <!-- Formulari -->
  <div class="col-md-5">
    <div class="card">
      <div class="card-header-bl"><i class="bi bi-upload"></i> Importar alumnes des de XML</div>
      <div class="card-body">

        <?php if (!empty($missatge)): ?>
          <div class="alert alert-success"><?= htmlspecialchars($missatge) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label fw-semibold">Fitxer XML</label>
            <input type="file" name="xml" class="form-control" accept=".xml" required>
            <div class="form-text">Format XML descrit a la dreta. Màx. 10 MB.</div>
          </div>
          <button class="btn btn-primary w-100">
            <i class="bi bi-upload"></i> Importar alumnes
          </button>
        </form>

        <hr>
        <div class="text-muted" style="font-size:.85rem">
          <i class="bi bi-info-circle me-1"></i>
          <strong>Comportament:</strong>
          <ul class="mt-1 mb-0 ps-3">
            <li>Si l'alumne <strong>ja existeix</strong> (per NIA o DNI), s'actualitzen les dades.</li>
            <li>Si la classe <strong>no existeix</strong> a la BD, el camp <code>classe_id</code> queda buit.</li>
            <li>Es genera automàticament l'<strong>email institucional</strong>:<br>
              <code>nom[3] + cognom1[3] + cognom2[3] @alu.edu.gva.es</code><br>
              Exemple: <em>Oscar Bravo Villada</em> → <code>oscbravil@alu.edu.gva.es</code>
            </li>
            <li>Es genera un <strong>CSV de contrasenyes</strong> per classe a <code>private/exports/</code>.</li>
          </ul>
        </div>

      </div>
    </div>
  </div>

  <!-- Documentació del format -->
  <div class="col-md-7">
    <div class="card">
      <div class="card-header-bl"><i class="bi bi-file-earmark-code"></i> Format del fitxer XML</div>
      <div class="card-body">

        <p class="mb-2" style="font-size:.9rem">
          Element arrel: <code>&lt;alumnes&gt;</code>. Cada alumne és un element
          <code>&lt;alumne /&gt;</code> amb els atributs següents:
        </p>

        <table class="table table-sm table-bordered" style="font-size:.82rem">
          <thead class="table-dark">
            <tr>
              <th>Atribut</th>
              <th>Obligatori</th>
              <th>Descripció</th>
              <th>Exemple</th>
            </tr>
          </thead>
          <tbody>
            <tr><td><code>nia</code></td><td>*</td><td>Número d'identificació de l'alumne</td><td><code>10000001</code></td></tr>
            <tr><td><code>dni</code></td><td>*</td><td>DNI o NIE de l'alumne</td><td><code>10000001A</code></td></tr>
            <tr class="table-warning"><td colspan="4" class="text-center fw-semibold" style="font-size:.78rem">* Cal almenys <code>nia</code> o <code>dni</code> (o tots dos)</td></tr>
            <tr><td><code>nom</code></td><td>Sí</td><td>Nom de pila</td><td><code>Oscar</code></td></tr>
            <tr><td><code>cognom1</code></td><td>Sí</td><td>Primer cognom</td><td><code>Bravo</code></td></tr>
            <tr><td><code>cognom2</code></td><td>No</td><td>Segon cognom</td><td><code>Villada</code></td></tr>
            <tr><td><code>classe</code></td><td>Sí</td><td>Nom exacte de la classe a la BD</td><td><code>1ASIX-A</code></td></tr>
            <tr><td><code>email_familia</code></td><td>No</td><td>Correu de la família (per als albarans)</td><td><code>familia@gmail.com</code></td></tr>
            <tr><td><code>telefon_familia</code></td><td>No</td><td>Telèfon de contacte</td><td><code>600111001</code></td></tr>
          </tbody>
        </table>

        <p class="fw-semibold mb-1" style="font-size:.88rem">Classes disponibles a la BD:</p>
        <div class="d-flex flex-wrap gap-1 mb-3">
          <?php
          $classesDisp = Database::fetchAll("SELECT nom FROM classes ORDER BY nom");
          foreach ($classesDisp as $cl):
          ?>
            <span class="codi-exemplar"><?= htmlspecialchars($cl['nom']) ?></span>
          <?php endforeach; ?>
        </div>

        <p class="fw-semibold mb-1" style="font-size:.88rem">Exemple de fitxer XML:</p>
        <pre class="bg-light border rounded p-3" style="font-size:.78rem;overflow-x:auto"><?= htmlspecialchars('<?xml version="1.0" encoding="UTF-8"?>
<alumnes>

  <alumne
    nia="10000001"
    dni="10000001A"
    nom="Oscar"
    cognom1="Bravo"
    cognom2="Villada"
    classe="1ASIX-A"
    email_familia="familia@gmail.com"
    telefon_familia="600111001"
  />

  <alumne
    nia="10000002"
    dni="10000002B"
    nom="Maria"
    cognom1="García"
    cognom2="López"
    classe="1ASIX-A"
    email_familia="maria.fam@gmail.com"
    telefon_familia="600111002"
  />

</alumnes>') ?></pre>

      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
