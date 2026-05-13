<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Admin: Calendar Management
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Booking.php';
require_once __DIR__ . '/../includes/auth.php';

session_start();
requireAdmin();

$pageTitle = 'Calendar';
$adminPage = 'calendar';
$pdo       = db();

$actionMsg  = '';
$actionType = '';

// Block / unblock a date
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $date   = $_POST['date']   ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if ($action === 'block' && $date) {
        try {
            $pdo->prepare("INSERT IGNORE INTO calendar_blocks (block_date, reason) VALUES (?,?)")
                ->execute([$date, $reason]);
            $actionMsg  = "Date <strong>{$date}</strong> has been blocked.";
            $actionType = 'success';
        } catch (Throwable $e) {
            $actionMsg  = 'Failed to block date.';
            $actionType = 'danger';
        }
    } elseif ($action === 'unblock' && $date) {
        $pdo->prepare("DELETE FROM calendar_blocks WHERE block_date=?")->execute([$date]);
        $actionMsg  = "Date <strong>{$date}</strong> has been unblocked.";
        $actionType = 'success';
    }
}

// Fetch calendar data
$calData    = Booking::getBookedDates();
$blockedRows = $pdo->query("SELECT * FROM calendar_blocks ORDER BY block_date")->fetchAll();

// Build FullCalendar events JSON
$fcEvents = [];

// Blocked dates
foreach ($calData['blocked'] as $date) {
    $fcEvents[] = [
        'title'           => 'Blocked',
        'start'           => $date,
        'allDay'          => true,
        'backgroundColor' => '#444444',
        'borderColor'     => '#555555',
        'textColor'       => '#888888',
        'classNames'      => ['fc-blocked'],
    ];
}

// Booked dates
foreach ($calData['booked'] as $date => $statuses) {
    $fullyBooked = count(array_filter($statuses, fn($s) => in_array($s, ['Approved','Confirmed','Completed']))) > 0;
    $fcEvents[] = [
        'title'           => $fullyBooked ? 'Booked' : 'Pending',
        'start'           => $date,
        'allDay'          => true,
        'backgroundColor' => $fullyBooked ? 'rgba(229,85,85,.35)' : 'rgba(232,160,48,.35)',
        'borderColor'     => $fullyBooked ? '#e55555' : '#e8a030',
        'textColor'       => $fullyBooked ? '#e55555' : '#e8a030',
    ];
}

// Upcoming bookings as individual events
$upcoming = $pdo->query("
    SELECT b.booking_ref, b.preferred_date, b.preferred_time,
           b.event_type, b.service_type, b.status,
           c.full_name
    FROM bookings b
    JOIN clients c ON c.id = b.client_id
    WHERE b.status NOT IN ('Cancelled','Rejected','Waiting for Email Verification')
      AND b.preferred_date >= CURDATE()
    ORDER BY b.preferred_date
    LIMIT 100
")->fetchAll();

foreach ($upcoming as $u) {
    $colors = [
        'Pending'    => ['#e8a030','rgba(232,160,48,.2)'],
        'Approved'   => ['#4caf7d','rgba(76,175,125,.2)'],
        'Confirmed'  => ['#5b9bd5','rgba(91,155,213,.2)'],
        'Completed'  => ['#c9a96e','rgba(201,169,110,.2)'],
    ];
    [$border, $bg] = $colors[$u['status']] ?? ['#888','rgba(136,136,136,.2)'];
    $fcEvents[] = [
        'title'           => $u['full_name'] . ' — ' . $u['event_type'],
        'start'           => $u['preferred_date'] . 'T' . $u['preferred_time'],
        'backgroundColor' => $bg,
        'borderColor'     => $border,
        'textColor'       => $border,
        'extendedProps'   => [
            'ref'     => $u['booking_ref'],
            'service' => $u['service_type'],
            'status'  => $u['status'],
        ],
    ];
}

require_once __DIR__ . '/../includes/header.php';
?>

<main class="sb-admin-main">

  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
      <h1 style="font-size:1.8rem;color:var(--gold);margin:0;">Calendar</h1>
      <p style="color:var(--text-dim);font-size:13px;margin:4px 0 0;">Manage availability and view scheduled appointments</p>
    </div>
    <button class="btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#blockModal">
      <i class="bi bi-calendar-x"></i> Block a Date
    </button>
  </div>

  <?php if ($actionMsg): ?>
  <div class="sb-alert sb-alert-<?= $actionType ?> mb-3" data-auto-dismiss>
    <i class="bi bi-check-circle"></i> <?= $actionMsg ?>
  </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Calendar -->
    <div class="col-lg-8">
      <div class="sb-card">
        <div id="adminCalendar" style="min-height:500px;"></div>
      </div>
    </div>

    <!-- Blocked dates list + legend -->
    <div class="col-lg-4">
      <!-- Legend -->
      <div class="sb-card mb-4">
        <h4 style="color:var(--text);font-size:1rem;margin-bottom:16px;">
          <i class="bi bi-info-circle me-2" style="color:var(--gold);"></i>Legend
        </h4>
        <?php
        $legend = [
          ['#e8a030', 'Pending bookings'],
          ['#4caf7d', 'Approved bookings'],
          ['#5b9bd5', 'Confirmed bookings'],
          ['#c9a96e', 'Completed events'],
          ['#555555', 'Blocked / Unavailable'],
        ];
        foreach ($legend as [$color, $label]): ?>
        <div class="d-flex align-items-center gap-2 mb-2">
          <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:<?= $color ?>;flex-shrink:0;"></span>
          <span style="color:var(--text-muted);font-size:13px;"><?= $label ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Blocked dates -->
      <div class="sb-card">
        <h4 style="color:var(--text);font-size:1rem;margin-bottom:16px;">
          <i class="bi bi-calendar-x me-2" style="color:var(--gold);"></i>Blocked Dates
        </h4>
        <?php if (empty($blockedRows)): ?>
        <p style="color:var(--text-dim);font-size:13px;">No dates blocked.</p>
        <?php else: ?>
        <div style="max-height:300px;overflow-y:auto;">
          <?php foreach ($blockedRows as $row): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;
                      padding:10px 0;border-bottom:1px solid var(--border);">
            <div>
              <div style="color:var(--text);font-size:14px;">
                <?= date('F j, Y', strtotime($row['block_date'])) ?>
              </div>
              <?php if ($row['reason']): ?>
              <div style="color:var(--text-dim);font-size:12px;"><?= htmlspecialchars($row['reason']) ?></div>
              <?php endif; ?>
            </div>
            <form method="POST">
              <input type="hidden" name="action" value="unblock">
              <input type="hidden" name="date"   value="<?= $row['block_date'] ?>">
              <button type="submit" class="btn-outline btn-sm"
                      style="border-color:var(--danger);color:var(--danger);padding:4px 10px;"
                      onclick="return confirm('Unblock this date?')">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</main>

<!-- Block date modal -->
<div class="modal fade" id="blockModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:1.3rem;">
          <i class="bi bi-calendar-x me-2"></i>Block a Date
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="block">
        <div class="modal-body">
          <div class="sb-form-group">
            <label>Date to Block <span class="required">*</span></label>
            <input type="date" name="date" id="blockDateInput" class="sb-input" required
                   min="<?= date('Y-m-d') ?>">
          </div>
          <div class="sb-form-group">
            <label>Reason (optional)</label>
            <input type="text" name="reason" class="sb-input" placeholder="e.g., Holiday, Personal event">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-gold">
            <i class="bi bi-lock"></i> Block Date
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/soulbud.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const events = <?= json_encode($fcEvents) ?>;

  const calendar = new FullCalendar.Calendar(document.getElementById('adminCalendar'), {
    initialView: 'dayGridMonth',
    headerToolbar: { left: 'prev', center: 'title', right: 'next today' },
    events,
    height: 'auto',
    eventClick: function (info) {
      const ref = info.event.extendedProps.ref;
      if (ref) {
        window.location.href = '<?= APP_URL ?>/admin/pages/booking_detail.php?ref=' + encodeURIComponent(ref);
      }
    },
    dateClick: function (info) {
      document.getElementById('blockDateInput').value = info.dateStr;
      new bootstrap.Modal(document.getElementById('blockModal')).show();
    },
  });
  calendar.render();
});
</script>

<style>
.fc-toolbar-title { font-family:'Cormorant Garamond',serif;color:var(--gold) !important;font-size:1.3rem !important; }
.fc-button { background:var(--bg-surface) !important;border:1px solid var(--border) !important;color:var(--text-muted) !important;border-radius:6px !important; }
.fc-button-active,.fc-button:hover { border-color:var(--gold) !important;color:var(--gold) !important; }
.fc-daygrid-day-number { color:var(--text-muted);font-size:12px; }
.fc-day-today { background:rgba(201,169,110,.06) !important; }
.fc-day-today .fc-daygrid-day-number { color:var(--gold) !important;font-weight:600; }
.fc-col-header-cell-cushion { color:var(--text-dim);font-size:11px;letter-spacing:.08em;text-transform:uppercase; }
.fc-event { border-radius:4px;padding:2px 4px;font-size:11px;cursor:pointer; }
</style>

</div>
</body></html>
