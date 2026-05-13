<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Public Calendar Page
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Booking.php';

$pageTitle   = 'Schedule Availability';
$currentPage = 'calendar';
$useCalendar = true;

$calData = Booking::getBookedDates();

$fcEvents = [];
foreach ($calData['blocked'] as $date) {
    $fcEvents[] = ['title' => 'Unavailable', 'start' => $date, 'allDay' => true,
                   'backgroundColor' => 'rgba(85,85,85,.4)', 'borderColor' => '#555',
                   'textColor' => '#666'];
}
foreach ($calData['booked'] as $date => $statuses) {
    $booked = count(array_filter($statuses, fn($s) => in_array($s, ['Approved','Confirmed','Completed'])));
    $color  = $booked > 0 ? 'rgba(229,85,85,.35)' : 'rgba(232,160,48,.3)';
    $border = $booked > 0 ? '#e55555' : '#e8a030';
    $title  = $booked > 0 ? 'Fully Booked' : 'Partially Available';
    $fcEvents[] = ['title' => $title, 'start' => $date, 'allDay' => true,
                   'backgroundColor' => $color, 'borderColor' => $border,
                   'textColor' => $border];
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── Page Banner ── -->
<div class="sb-page-banner" style="background:linear-gradient(135deg,#fff8f5 0%,#fff 100%);border-bottom:1px solid var(--border);">
  <div class="sb-container">
    <p class="subtitle mb-2">Check Availability</p>
    <h1 style="font-size:2.4rem;color:var(--text);font-weight:800;">Schedule Calendar</h1>
    <div style="width:40px;height:3px;background:var(--accent);border-radius:2px;margin:14px auto 0;"></div>
  </div>
</div>

<section class="sb-section" style="padding-top:48px;">
  <div class="sb-container">

    <div class="row g-5 align-items-start">

      <!-- ── Calendar ── -->
      <div class="col-lg-8">
        <div class="sb-card" style="border-top:4px solid var(--accent);">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
            <i class="bi bi-calendar3" style="color:var(--accent);font-size:1.2rem;"></i>
            <h3 style="font-size:1.1rem;font-weight:700;color:var(--text);margin:0;">Availability Overview</h3>
          </div>
          <div id="sb-calendar"></div>
        </div>
      </div>

      <!-- ── Sidebar ── -->
      <div class="col-lg-4">

        <!-- Legend card -->
        <div class="sb-card mb-4" style="border-top:4px solid var(--accent);">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <i class="bi bi-info-circle" style="color:var(--accent);font-size:1.1rem;"></i>
            <h4 style="color:var(--text);font-size:1rem;font-weight:700;margin:0;">Legend</h4>
          </div>
          <?php
          $legend = [
            ['rgba(232,82,10,.15)', 'var(--accent)', 'Available'],
            ['rgba(232,160,48,.3)', '#e8a030',       'Partially Available'],
            ['rgba(229,85,85,.35)', '#e55555',       'Fully Booked'],
            ['rgba(85,85,85,.4)',   '#555555',       'Unavailable'],
          ];
          foreach ($legend as [$bg, $border, $label]): ?>
          <div class="d-flex align-items-center gap-3 mb-3">
            <span style="display:inline-block;width:36px;height:18px;border-radius:4px;
                         background:<?= $bg ?>;border:1.5px solid <?= $border ?>;flex-shrink:0;"></span>
            <span style="color:var(--text-muted);font-size:13px;font-weight:500;"><?= $label ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- CTA card -->
        <div class="sb-card" style="border-top:4px solid var(--accent);">
          <div style="text-align:center;padding:8px 0 4px;">
            <div style="width:56px;height:56px;background:var(--accent-subtle);border-radius:50%;
                        display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
              <i class="bi bi-calendar-plus" style="font-size:1.4rem;color:var(--accent);"></i>
            </div>
            <h4 style="color:var(--text);font-size:1.1rem;font-weight:700;margin-bottom:8px;">Ready to Book?</h4>
            <p style="color:var(--text-muted);font-size:13px;line-height:1.7;margin-bottom:20px;">
              Found a date that works? Submit your booking request in minutes — no account required.
            </p>
            <a href="<?= APP_URL ?>/book.php" class="btn-gold" style="width:100%;justify-content:center;">
              <i class="bi bi-calendar-plus"></i> Book Now
            </a>
          </div>
        </div>

      </div>
    </div>

  </div>
</section>

<?php
$bookedJson  = json_encode($calData['booked']);
$blockedJson = json_encode($calData['blocked']);
$extraScripts = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
  initCalendar({$bookedJson}, {$blockedJson});
});
</script>
JS;
?>

<style>
/* FullCalendar overrides — accent orange */
.fc { background: transparent !important; }
.fc-toolbar-title {
  font-family: 'Inter', sans-serif;
  color: var(--text) !important;
  font-size: 1.1rem !important;
  font-weight: 700 !important;
}
.fc-button {
  background: var(--bg) !important;
  border: 1px solid var(--border) !important;
  color: var(--text-muted) !important;
  border-radius: 6px !important;
}
.fc-button:hover {
  border-color: var(--accent) !important;
  color: var(--accent) !important;
}
.fc-daygrid-day-number { color: var(--text-muted); font-size: 12px; }
.fc-day-today { background: rgba(232,82,10,.06) !important; }
.fc-day-today .fc-daygrid-day-number {
  color: var(--accent) !important;
  font-weight: 700;
}
.fc-col-header-cell-cushion {
  color: var(--text-dim);
  font-size: 11px;
  letter-spacing: .08em;
  text-transform: uppercase;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>