<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Global Footer Partial
// ─────────────────────────────────────────
defined('SOULBUD') or die('Direct access denied.');
?>

<!-- ── Footer ───────────────────────────────────── -->
<footer class="sb-footer">
  <div class="sb-container">

    <div style="display:flex;flex-direction:column;align-items:center;gap:0;">

      <!-- Logo -->
      <div style="font-family:'Inter',sans-serif;font-size:22px;font-weight:800;
                  color:var(--text);letter-spacing:-.02em;margin-bottom:4px;">
        SOUL<span style="color:var(--accent);">BUD</span>.CO
      </div>

      <!-- Tagline -->
      <p class="subtitle" style="margin-bottom:0;color:var(--text-dim);">Photography &amp; Videography</p>

      <!-- Orange divider line -->
      <div style="width:40px;height:3px;background:var(--accent);border-radius:2px;margin:18px auto;"></div>

      <!-- Nav links row -->
      <nav style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin-bottom:20px;">
        <?php
        $footerLinks = [
          ['/',                  'Home'],
          ['/book.php',          'Book Now'],
          ['/pages/status.php',  'Check Status'],
          ['/pages/calendar.php','Calendar'],
          ['/pages/payment.php', 'Payment'],
        ];
        foreach ($footerLinks as [$path, $label]): ?>
        <a href="<?= APP_URL . $path ?>"
           style="color:var(--text-muted);text-decoration:none;font-size:12px;font-weight:500;
                  padding:4px 10px;border-radius:4px;transition:color var(--transition),background var(--transition);"
           onmouseover="this.style.color='var(--accent)';this.style.background='var(--accent-subtle)'"
           onmouseout="this.style.color='var(--text-muted)';this.style.background='transparent'">
          <?= $label ?>
        </a>
        <?php if ($path !== '/pages/payment.php'): ?>
        <span style="color:var(--border-light);font-size:12px;line-height:2;pointer-events:none;">|</span>
        <?php endif; ?>
        <?php endforeach; ?>
      </nav>

      <!-- Copyright + email -->
      <p style="color:var(--text-dim);font-size:12px;letter-spacing:.04em;text-align:center;line-height:1.8;">
        &copy; <?= date('Y') ?> SOULBUD.CO &mdash; All Rights Reserved.
        &nbsp;&nbsp;
        <a href="mailto:<?= APP_EMAIL ?>"
           style="color:var(--accent);text-decoration:none;font-weight:500;
                  transition:color var(--transition);"
           onmouseover="this.style.color='var(--accent-dark)'"
           onmouseout="this.style.color='var(--accent)'">
          <?= APP_EMAIL ?>
        </a>
      </p>

    </div>
  </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- FullCalendar (optional) -->
<?php if (!empty($useCalendar)): ?>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<?php endif; ?>
<!-- SOULBUD JS -->
<script src="<?= APP_URL ?>/assets/js/soulbud.js"></script>

<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>