</div> <!-- /.content -->

</div> <!-- /.main-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if (!empty($jsExtra)) echo $jsExtra; ?>

<script>
(function () {
  const icon  = document.getElementById('darkToggleIcon');
  const label = document.getElementById('darkToggleTxt');

  function sync() {
    const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    if (icon)  icon.className  = dark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    if (label) label.textContent = dark ? 'Mode clar' : 'Mode fosc';
  }

  window.toggleDark = function () {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-bs-theme') === 'dark';
    html.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
    sync();
    fetch('<?= BASE_URL ?>/toggle_theme.php').catch(() => {});
  };

  sync();
})();
</script>

</body>
</html>
