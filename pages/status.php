<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Check Booking Status
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Booking.php';

$pageTitle   = 'Check Booking Status';
$currentPage = 'status';

$booking = null;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref   = strtoupper(trim($_POST['booking_ref'] ?? ''));
    $email = strtolower(trim($_POST['email']       ?? ''));
    if (!$ref || !$email) {
        $error = 'Please provide both your booking reference and email.';
    } else {
        $booking = Booking::getByRef($ref);
        if (!$booking || strtolower($booking['email']) !== $email) {
            $error = 'No booking found with that reference and email combination.';
            $booking = null;
        }
    }
} elseif (!empty($_GET['ref'])) {
    $booking = Booking::getByRef(strtoupper($_GET['ref']));
}

$statusColors = [
    'Waiting for Email Verification' => 'waiting',
    'Pending'    => 'pending',
    'Approved'   => 'approved',
    'Confirmed'  => 'confirmed',
    'Completed'  => 'completed',
    'Cancelled'  => 'cancelled',
    'Rejected'   => 'rejected',
    'Rescheduled'=> 'rescheduled',
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── Page Banner ── -->
<div class="sb-page-banner" style="background:linear-gradient(135deg,#fff8f5 0%,#fff 100%);border-bottom:1px solid var(--border);">
  <div class="sb-container">
    <p class="subtitle mb-2">Track Your Appointment</p>
    <h1 style="font-size:2.4rem;color:var(--text);font-weight:800;">Booking Status</h1>
    <div style="width:40px;height:3px;background:var(--accent);border-radius:2px;margin:14px auto 0;"></div>
  </div>
</div>

<section class="sb-section" style="padding-top:48px;">
  <div class="sb-container" style="max-width:820px;">

    <!-- ── Search card ── -->
    <div class="sb-card mb-5" style="border-top:4px solid var(--accent);">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <i class="bi bi-search" style="color:var(--accent);font-size:1.2rem;"></i>
        <h3 style="font-size:1.1rem;font-weight:700;color:var(--text);margin:0;">Find Your Booking</h3>
      </div>
      <form method="POST" action="">
        <div class="row g-3 align-items-end">
          <div class="col-md-5 sb-form-group mb-0">
            <label>Booking Reference</label>
            <input type="text" name="booking_ref" class="sb-input" placeholder="SB-XXXXXXXX"
                   value="<?= htmlspecialchars($_POST['booking_ref'] ?? $_GET['ref'] ?? '') ?>" required>
          </div>
          <div class="col-md-5 sb-form-group mb-0">
            <label>Email Address</label>
            <input type="email" name="email" class="sb-input" placeholder="your@email.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
          <div class="col-md-2 mb-0">
            <button type="submit" class="btn-gold" style="width:100%;justify-content:center;padding:10px;">
              <i class="bi bi-search"></i>
            </button>
          </div>
        </div>
      </form>
    </div>

    <?php if ($error): ?>
    <div class="sb-alert sb-alert-danger mb-4">
      <i class="bi bi-exclamation-circle"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($booking):
      $allStatuses = [
          'Waiting for Email Verification',
          'Pending',
          'Approved',
          'Confirmed',
          'Completed',
      ];
      $currentIdx = array_search($booking['status'], $allStatuses);
      $badgeClass = $statusColors[$booking['status']] ?? 'pending';
    ?>

    <!-- ── Booking result card ── -->
    <div class="sb-card" style="border-top:4px solid var(--accent);">

      <!-- Header row: name + ref + badge -->
      <div class="sb-card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
          <h3 style="color:var(--text);font-size:1.2rem;font-weight:700;margin:0;">
            <?= htmlspecialchars($booking['full_name']) ?>
          </h3>
          <p style="color:var(--accent);font-size:13px;font-weight:600;margin:4px 0 0;font-family:'Inter',sans-serif;letter-spacing:.04em;">
            <?= htmlspecialchars($booking['booking_ref']) ?>
          </p>
        </div>
        <span class="badge-status badge-<?= $badgeClass ?>"><?= htmlspecialchars($booking['status']) ?></span>
      </div>

      <!-- ── Progress stepper (normal flow only) ── -->
      <?php if ($currentIdx !== false && !in_array($booking['status'], ['Cancelled','Rejected','Rescheduled'])): ?>
      <div style="margin:8px 0 32px;overflow-x:auto;">
        <div style="display:flex;justify-content:space-between;position:relative;padding:0 8px;min-width:400px;">
          <!-- Track line -->
          <div style="position:absolute;top:14px;left:20px;right:20px;height:2px;background:var(--border);z-index:0;"></div>
          <!-- Progress fill — orange -->
          <div style="position:absolute;top:14px;left:20px;height:2px;
                      background:linear-gradient(to right,var(--accent),var(--accent-dark));z-index:1;
                      width:<?= min(100, ($currentIdx / (count($allStatuses)-1)) * 100) ?>%;
                      transition:width .6s ease;"></div>
          <?php foreach ($allStatuses as $i => $s):
            $done   = $i <= $currentIdx;
            $active = $i === $currentIdx;
          ?>
          <div style="display:flex;flex-direction:column;align-items:center;position:relative;z-index:2;flex:1;">
            <div style="width:28px;height:28px;border-radius:50%;
                        background:<?= $done ? 'var(--accent)' : 'var(--bg)' ?>;
                        border:2px solid <?= $done ? 'var(--accent)' : 'var(--border)' ?>;
                        display:flex;align-items:center;justify-content:center;
                        font-size:12px;color:<?= $done ? '#fff' : 'var(--text-dim)' ?>;font-weight:700;
                        box-shadow:<?= $active ? '0 0 0 4px rgba(232,82,10,.18)' : 'none' ?>;">
              <?= $done ? '✓' : ($i + 1) ?>
            </div>
            <span style="font-size:10px;letter-spacing:.04em;
                         color:<?= $done ? 'var(--accent)' : 'var(--text-dim)' ?>;
                         margin-top:8px;text-align:center;max-width:72px;line-height:1.3;">
              <?= $s ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── Booking detail grid ── -->
      <div class="row g-3">
        <?php
        $details = [
          ['bi bi-camera',    'Service',  $booking['service_type']],
          ['bi bi-tag',       'Event',    $booking['event_type']],
          ['bi bi-geo-alt',   'Location', $booking['event_location']],
          ['bi bi-calendar',  'Date',     date('F j, Y', strtotime($booking['preferred_date']))],
          ['bi bi-clock',     'Time',     date('g:i A',  strtotime($booking['preferred_time']))],
          ['bi bi-telephone', 'Contact',  $booking['contact_number']],
        ];
        foreach ($details as [$icon, $label, $value]): ?>
        <div class="col-md-6">
          <div style="display:flex;gap:12px;align-items:flex-start;padding:14px;
                      background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);">
            <i class="<?= $icon ?>" style="color:var(--accent);margin-top:2px;flex-shrink:0;"></i>
            <div>
              <div style="font-size:10px;letter-spacing:.10em;text-transform:uppercase;color:var(--text-dim);margin-bottom:3px;"><?= $label ?></div>
              <div style="color:var(--text);font-size:14px;font-weight:500;"><?= htmlspecialchars($value) ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if ($booking['admin_notes']): ?>
      <div class="sb-alert sb-alert-info mt-4">
        <i class="bi bi-info-circle" style="color:var(--accent);"></i>
        <div><strong>Admin Note:</strong> <?= htmlspecialchars($booking['admin_notes']) ?></div>
      </div>
      <?php endif; ?>

      <?php if ($booking['status'] === 'Rescheduled' && $booking['rescheduled_date']): ?>
      <div class="sb-alert sb-alert-warning mt-3">
        <i class="bi bi-calendar-check"></i>
        <div>
          <strong>New Schedule:</strong>
          <?= date('F j, Y', strtotime($booking['rescheduled_date'])) ?>
          at <?= date('g:i A', strtotime($booking['rescheduled_time'])) ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Payment status strip -->
      <?php if ($booking['payment_status']): ?>
      <div class="mt-3 d-flex align-items-center gap-2 flex-wrap"
           style="padding:12px 16px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);">
        <i class="bi bi-credit-card" style="color:var(--accent);"></i>
        <span style="color:var(--text-muted);font-size:13px;">Payment:</span>
        <span class="badge-status badge-<?= strtolower($booking['payment_status']) ?>">
          <?= $booking['payment_status'] ?>
        </span>
        <?php if ($booking['payment_method']): ?>
        <span style="color:var(--text-muted);font-size:13px;">via <?= htmlspecialchars($booking['payment_method']) ?></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Action buttons -->
      <div class="d-flex gap-3 flex-wrap mt-4" style="border-top:1px solid var(--border);padding-top:20px;">
        <?php if (in_array($booking['status'], ['Pending','Approved'])): ?>
        <a href="<?= APP_URL ?>/pages/payment.php?ref=<?= urlencode($booking['booking_ref']) ?>" class="btn-gold">
          <i class="bi bi-credit-card"></i> Upload Payment
        </a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/book.php" class="btn-outline">
          <i class="bi bi-calendar-plus"></i> New Booking
        </a>
      </div>

    </div><!-- end .sb-card -->

    <?php endif; ?>

    <?php if (!$booking && !$error): ?>
    <div class="text-center" style="padding:60px 0;">
      <div style="width:80px;height:80px;background:var(--accent-subtle);border-radius:50%;
                  display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
        <i class="bi bi-calendar-search" style="font-size:2rem;color:var(--accent);"></i>
      </div>
      <h3 style="color:var(--text);font-weight:700;margin-bottom:8px;">Find Your Booking</h3>
      <p style="color:var(--text-muted);max-width:400px;margin:0 auto;">
        Enter your booking reference and email address above to check your appointment status.
      </p>
    </div>
    <?php endif; ?>

  </div>
</section>

<style>
/* Focus ring — orange on status page inputs too */
.sb-input:focus, .sb-select:focus {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px rgba(232,82,10,.12) !important;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>