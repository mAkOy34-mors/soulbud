<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Admin Footer Partial
// ─────────────────────────────────────────
defined('SOULBUD') or die('Direct access denied.');
?>
</div><!-- end .sb-admin-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/soulbud.js"></script>

<script>
// Sync sidebar collapse with content margin
const sidebar = document.getElementById('adminSidebar');
const content = document.getElementById('adminContent');
if (sidebar && content) {
  const toggle = document.querySelector('.sidebar-toggle');
  if (toggle) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      content.classList.toggle('collapsed');
    });
  }
}
</script>

<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>
