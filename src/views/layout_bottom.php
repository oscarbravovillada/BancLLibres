</div> <!-- /.content -->

</div> <!-- /.main-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if (!empty($jsExtra)) echo $jsExtra; ?>

<script>
(function () {
  /* ── Dark mode ─────────────────────────────────────────── */
  const icon  = document.getElementById('darkToggleIcon');
  const label = document.getElementById('darkToggleTxt');

  function syncDark() {
    const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    if (icon)  icon.className  = dark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    if (label) label.textContent = dark ? 'Mode clar' : 'Mode fosc';
  }

  window.toggleDark = function () {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-bs-theme') === 'dark';
    html.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
    syncDark();
    fetch('<?= BASE_URL ?>/toggle_theme.php').catch(() => {});
  };

  syncDark();

  /* ── Sidebar collapse ──────────────────────────────────── */
  const sidebar       = document.getElementById('sidebar');
  const collapseIcon  = document.getElementById('sidebarCollapseIcon');
  const overlay       = document.getElementById('sidebarOverlay');
  const MOBILE_BP     = 650;
  const TABLET_BP     = 900;

  function isMobile()  { return window.innerWidth <= MOBILE_BP; }
  function isTablet()  { return window.innerWidth > MOBILE_BP && window.innerWidth <= TABLET_BP; }
  function isDesktop() { return window.innerWidth > TABLET_BP; }

  function applySidebarState() {
    if (isMobile()) {
      document.body.classList.remove('sidebar-is-collapsed');
      if (sidebar) sidebar.classList.remove('is-collapsed');
    } else if (isTablet()) {
      document.body.classList.add('sidebar-is-collapsed');
      if (sidebar) sidebar.classList.add('is-collapsed');
    } else {
      // Desktop: use saved preference
      const saved = localStorage.getItem('bl-sidebar-collapsed') === '1';
      document.body.classList.toggle('sidebar-is-collapsed', saved);
      if (sidebar) sidebar.classList.toggle('is-collapsed', saved);
    }
    updateCollapseIcon();
  }

  function updateCollapseIcon() {
    if (!collapseIcon) return;
    const collapsed = sidebar && sidebar.classList.contains('is-collapsed');
    collapseIcon.className = collapsed ? 'bi bi-chevron-double-right' : 'bi bi-chevron-double-left';
  }

  window.toggleSidebar = function () {
    if (isMobile()) {
      document.body.classList.toggle('sidebar-mobile-open');
    } else if (isDesktop()) {
      const nowCollapsed = sidebar && sidebar.classList.toggle('is-collapsed');
      document.body.classList.toggle('sidebar-is-collapsed', nowCollapsed);
      localStorage.setItem('bl-sidebar-collapsed', nowCollapsed ? '1' : '0');
      updateCollapseIcon();
    }
    // Tablet: button hidden, no toggle
  };

  window.closeMobileSidebar = function () {
    document.body.classList.remove('sidebar-mobile-open');
  };

  window.addEventListener('resize', applySidebarState);
  applySidebarState();
})();
</script>

</body>
</html>
