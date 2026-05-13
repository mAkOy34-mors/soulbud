<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Admin: Booking Detail
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Booking.php';
require_once __DIR__ . '/../../includes/Mailer.php';
require_once __DIR__ . '/../includes/auth.php';

session_start();
requireAdmin();

$ref     = strtoupper(trim($_GET['ref'] ?? ''));
$booking = $ref ? Booking::getByRef($ref) : null;

if (!$booking) {
  header('Location: ' . APP_URL . '/admin/pages/bookings.php');
  exit;
}

$pageTitle  = 'Booking ' . $booking['booking_ref'];
$adminPage  = 'bookings';
$actionMsg  = '';
$actionType = '';

// ── Handle action ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
  $action    = $_POST['action'];
  $adminNote = trim($_POST['admin_notes']  ?? '');
  $rDate     = $_POST['rescheduled_date']  ?? null;
  $rTime     = $_POST['rescheduled_time']  ?? null;

  // Hard delete
  if ($action === 'delete') {
    $db = db();
    $db->prepare("DELETE FROM payments WHERE booking_id = ?")->execute([$booking['id']]);
    $db->prepare("DELETE FROM bookings  WHERE id = ?")       ->execute([$booking['id']]);

    $_SESSION['flash'] = [
      'type' => 'danger',
      'msg'  => "Booking <strong>{$booking['booking_ref']}</strong> has been permanently deleted.",
    ];
    header('Location: ' . APP_URL . '/admin/pages/bookings.php');
    exit;
  }

  $statusMap = [
    'approve'    => 'Approved',
    'reject'     => 'Rejected',
    'confirm'    => 'Confirmed',
    'complete'   => 'Completed',
    'cancel'     => 'Cancelled',
    'reschedule' => 'Rescheduled',
  ];

  if (isset($statusMap[$action])) {
    $newStatus = $statusMap[$action];
    Booking::updateStatus((int)$booking['id'], $newStatus, $adminNote, $rDate ?: null, $rTime ?: null);

    $booking = Booking::getByRef($ref);

    if ($action === 'approve') {
      Mailer::sendConfirmation($booking);
    } elseif (in_array($action, ['reject', 'reschedule'])) {
      Mailer::sendStatusUpdate($booking, $action === 'reject' ? 'rejected' : 'rescheduled');
    } elseif ($action === 'complete') {
      Mailer::sendStatusUpdate($booking, 'payment');
    }

    $actionMsg  = "Status updated to <strong>{$newStatus}</strong>. Client has been notified.";
    $actionType = 'success';
  }

  // Confirm payment
  if ($action === 'confirm_payment') {
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    db()->prepare("UPDATE payments SET status='Confirmed', confirmed_at=NOW() WHERE id=?")
      ->execute([$paymentId]);
    Mailer::sendStatusUpdate($booking, 'payment');
    $actionMsg  = 'Payment confirmed and client notified.';
    $actionType = 'success';
    $booking    = Booking::getByRef($ref);
  }
}

// Get payment records
$payments = db()->prepare("SELECT * FROM payments WHERE booking_id=? ORDER BY created_at DESC");
$payments->execute([$booking['id']]);
$payments = $payments->fetchAll();

$statusBadges = [
  'Waiting for Email Verification' => 'waiting',
  'Pending'     => 'pending',
  'Approved'    => 'approved',
  'Confirmed'   => 'confirmed',
  'Completed'   => 'completed',
  'Cancelled'   => 'cancelled',
  'Rejected'    => 'rejected',
  'Rescheduled' => 'rescheduled',
];

require_once __DIR__ . '/../includes/header.php';
?>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">

<main class="sb-admin-main">

  <!-- Breadcrumb -->
  <div class="d-flex align-items-center gap-2 mb-4" style="font-size:13px;color:var(--text-dim);">
    <a href="<?= APP_URL ?>/admin/pages/bookings.php" style="color:var(--text-muted);text-decoration:none;">Bookings</a>
    <i class="bi bi-chevron-right" style="font-size:11px;"></i>
    <span style="color:var(--gold);"><?= htmlspecialchars($booking['booking_ref']) ?></span>
  </div>

  <?php if ($actionMsg): ?>
    <div class="sb-alert sb-alert-<?= $actionType ?> mb-3" data-auto-dismiss>
      <i class="bi bi-check-circle"></i> <?= $actionMsg ?>
    </div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- Left column: booking info -->
    <div class="col-lg-8">

      <!-- Header card -->
      <div class="sb-card mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
          <div>
            <h2 style="font-size:1.6rem;color:var(--gold);margin:0;"><?= htmlspecialchars($booking['booking_ref']) ?></h2>
            <p style="color:var(--text-dim);font-size:13px;margin:6px 0 0;">
              Submitted <?= date('F j, Y \a\t g:i A', strtotime($booking['created_at'])) ?>
            </p>
          </div>
          <span class="badge-status badge-<?= $statusBadges[$booking['status']] ?? 'pending' ?>" style="font-size:13px;padding:6px 16px;">
            <?= htmlspecialchars($booking['status']) ?>
          </span>
        </div>
      </div>

      <!-- Client info -->
      <div class="sb-card mb-4">
        <div class="sb-card-header">
          <h4 style="color:var(--text);font-size:1rem;margin:0;">
            <i class="bi bi-person-circle me-2" style="color:var(--gold);"></i>Client Information
          </h4>
        </div>
        <div class="row g-3">
          <?php
          $clientFields = [
            ['Full Name',      $booking['full_name']],
            ['Email Address',  $booking['email']],
            ['Contact Number', $booking['contact_number']],
          ];
          foreach ($clientFields as [$label, $value]): ?>
            <div class="col-md-4">
              <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-dim);margin-bottom:4px;"><?= $label ?></div>
              <div style="color:var(--text);font-size:14px;"><?= htmlspecialchars($value) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Event info -->
      <div class="sb-card mb-4">
        <div class="sb-card-header">
          <h4 style="color:var(--text);font-size:1rem;margin:0;">
            <i class="bi bi-calendar-event me-2" style="color:var(--gold);"></i>Event Details
          </h4>
        </div>
        <div class="row g-3">
          <?php
          $eventFields = [
            ['Event Type',     $booking['event_type']],
            ['Service Type',   $booking['service_type']],
            ['Preferred Date', date('F j, Y', strtotime($booking['preferred_date']))],
            ['Preferred Time', date('g:i A',  strtotime($booking['preferred_time']))],
            ['Location',       $booking['event_location']],
          ];
          foreach ($eventFields as [$label, $value]): ?>
            <div class="col-md-6">
              <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-dim);margin-bottom:4px;"><?= $label ?></div>
              <div style="color:var(--text);font-size:14px;"><?= htmlspecialchars($value) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (!empty($booking['additional_notes'])): ?>
            <div class="col-12">
              <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-dim);margin-bottom:4px;">Additional Notes</div>
              <div style="color:var(--text-muted);font-size:14px;line-height:1.7;"><?= nl2br(htmlspecialchars($booking['additional_notes'] ?? '')) ?></div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Leaflet Map -->
        <div class="mt-4">
          <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-dim);margin-bottom:8px;">
            <i class="bi bi-map me-1" style="color:var(--gold);"></i> Map View
          </div>
          <div id="booking-map" style="height:300px;border-radius:var(--radius-sm);border:1px solid var(--border);z-index:0;"></div>
          <div id="map-status" style="font-size:12px;color:var(--text-dim);margin-top:6px;">
            <i class="bi bi-geo-alt me-1"></i> Locating: <em><?= htmlspecialchars($booking['event_location']) ?></em>
          </div>
        </div>

        <?php if ($booking['status'] === 'Rescheduled' && $booking['rescheduled_date']): ?>
          <div class="sb-alert sb-alert-info mt-3">
            <i class="bi bi-calendar-check"></i>
            <div>
              <strong>Rescheduled to:</strong>
              <?= date('F j, Y', strtotime($booking['rescheduled_date'])) ?>
              at <?= date('g:i A', strtotime($booking['rescheduled_time'])) ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($booking['admin_notes']): ?>
          <div class="sb-alert sb-alert-warning mt-3">
            <i class="bi bi-sticky"></i>
            <div><strong>Admin Note:</strong> <?= htmlspecialchars($booking['admin_notes']) ?></div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Payments -->
      <div class="sb-card">
        <div class="sb-card-header">
          <h4 style="color:var(--text);font-size:1rem;margin:0;">
            <i class="bi bi-credit-card me-2" style="color:var(--gold);"></i>Payment Records
          </h4>
        </div>

        <?php if (empty($payments)): ?>
          <p style="color:var(--text-dim);font-size:13px;">No payment submitted yet.</p>
        <?php else: ?>
          <?php foreach ($payments as $pay): ?>
            <div style="background:var(--bg-surface);border-radius:var(--radius-sm);padding:16px;margin-bottom:12px;border:1px solid var(--border);">
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                  <div style="color:var(--text);font-weight:500;margin-bottom:4px;">
                    ₱<?= number_format($pay['amount'], 2) ?>
                    <span style="font-size:12px;color:var(--text-muted);font-weight:400;margin-left:6px;">via <?= htmlspecialchars($pay['payment_method']) ?></span>
                  </div>
                  <div style="font-size:12px;color:var(--text-dim);">
                    Submitted: <?= date('M j, Y g:i A', strtotime($pay['created_at'])) ?>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge-status badge-<?= strtolower($pay['status']) ?>"><?= $pay['status'] ?></span>
                  <?php if ($pay['status'] === 'Pending'): ?>
                    <form method="POST">
                      <input type="hidden" name="action" value="confirm_payment">
                      <input type="hidden" name="payment_id" value="<?= $pay['id'] ?>">
                      <button type="submit" class="btn-gold btn-sm" onclick="return confirm('Confirm this payment?')">
                        <i class="bi bi-check-lg"></i> Confirm
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
              <?php if (!empty($pay['proof_filename'])): ?>
                <div class="mt-3">
                  <a href="<?= APP_URL ?>/uploads/payments/<?= urlencode($pay['proof_filename']) ?>" target="_blank">
                    <img src="<?= APP_URL ?>/uploads/payments/<?= urlencode($pay['proof_filename']) ?>"
                      alt="Proof of Payment"
                      style="max-height:180px;border-radius:var(--radius-sm);border:1px solid var(--border);cursor:zoom-in;">
                  </a>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div><!-- /left col -->

    <!-- Right column: actions -->
    <div class="col-lg-4">
      <div class="sb-card" style="position:sticky;top:80px;">
        <div class="sb-card-header">
          <h4 style="color:var(--text);font-size:1rem;margin:0;">
            <i class="bi bi-gear me-2" style="color:var(--gold);"></i>Actions
          </h4>
        </div>

        <?php
        $status        = $booking['status'];
        $canApprove    = $status === 'Pending';
        $canReject     = in_array($status, ['Pending', 'Approved']);
        $canConfirm    = $status === 'Approved';
        $canComplete   = $status === 'Confirmed';
        $canCancel     = in_array($status, ['Pending', 'Approved', 'Confirmed']);
        $canReschedule = in_array($status, ['Pending', 'Approved', 'Confirmed']);
        ?>

        <form method="POST" id="actionForm">
          <div class="sb-form-group">
            <label>Admin Notes</label>
            <textarea name="admin_notes" class="sb-textarea" rows="3"
              placeholder="Optional note to client..."><?= htmlspecialchars($booking['admin_notes'] ?? '') ?></textarea>
          </div>

          <!-- Reschedule fields (shown conditionally) -->
          <div id="rescheduleFields" style="display:none;">
            <div class="sb-form-group">
              <label>New Date</label>
              <input type="date" name="rescheduled_date" class="sb-input">
            </div>
            <div class="sb-form-group">
              <label>New Time</label>
              <input type="time" name="rescheduled_time" class="sb-input">
            </div>
          </div>

          <div class="d-grid gap-2">
            <?php if ($canApprove): ?>
              <button type="submit" name="action" value="approve" class="btn-gold"
                onclick="return confirm('Approve this booking and notify client?')">
                <i class="bi bi-check-circle"></i> Approve Booking
              </button>
            <?php endif; ?>

            <?php if ($canConfirm): ?>
              <button type="submit" name="action" value="confirm" class="btn-gold"
                onclick="return confirm('Mark as Confirmed?')">
                <i class="bi bi-check2-all"></i> Mark Confirmed
              </button>
            <?php endif; ?>

            <?php if ($canComplete): ?>
              <button type="submit" name="action" value="complete" class="btn-gold"
                onclick="return confirm('Mark event as Completed?')">
                <i class="bi bi-star"></i> Mark Completed
              </button>
            <?php endif; ?>

            <?php if ($canReschedule): ?>
              <button type="button" class="btn-outline"
                onclick="document.getElementById('rescheduleFields').style.display='block';
                         this.style.display='none';">
                <i class="bi bi-calendar-check"></i> Reschedule
              </button>
              <button type="submit" name="action" value="reschedule"
                style="display:none;" id="rescheduleSubmit" class="btn-outline"
                onclick="return confirm('Reschedule this booking?')">
                <i class="bi bi-send"></i> Send Reschedule
              </button>
              <script>
                document.getElementById('rescheduleFields').addEventListener('change', function() {
                  document.getElementById('rescheduleSubmit').style.display = 'flex';
                });
              </script>
            <?php endif; ?>

            <?php if ($canReject): ?>
              <button type="submit" name="action" value="reject" class="btn-gold btn-danger"
                onclick="return confirm('Reject this booking? This will notify the client.')">
                <i class="bi bi-x-circle"></i> Reject Booking
              </button>
            <?php endif; ?>

            <?php if ($canCancel): ?>
              <button type="submit" name="action" value="cancel" class="btn-outline"
                style="border-color:var(--danger);color:var(--danger);"
                onclick="return confirm('Cancel this booking?')">
                <i class="bi bi-trash"></i> Cancel Booking
              </button>
            <?php endif; ?>

            <!-- ── Delete permanently (always available to admin) ── -->
            <div class="sb-divider" style="margin:4px 0;"></div>
            <button type="submit" name="action" value="delete"
              class="btn-outline"
              style="border-color:var(--danger);color:var(--danger);opacity:.8;font-size:13px;"
              onclick="return confirm('⚠️ PERMANENTLY delete booking <?= htmlspecialchars(addslashes($booking['booking_ref'])) ?>?\n\nThis will also remove all payment records and CANNOT be undone.')">
              <i class="bi bi-trash3-fill"></i> Delete Permanently
            </button>
          </div>
        </form>

        <div class="sb-divider"></div>

        <!-- Quick links -->
        <div style="font-size:13px;color:var(--text-dim);">
          <div class="mb-2">
            <i class="bi bi-envelope me-1" style="color:var(--gold);"></i>
            <a href="mailto:<?= htmlspecialchars($booking['email']) ?>" style="color:var(--text-muted);text-decoration:none;">
              <?= htmlspecialchars($booking['email']) ?>
            </a>
          </div>
          <div>
            <i class="bi bi-telephone me-1" style="color:var(--gold);"></i>
            <?= htmlspecialchars($booking['contact_number']) ?>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /row -->

</main>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/soulbud.js"></script>

<!-- Leaflet JS -->
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  (function() {
    const location = <?= json_encode($booking['event_location']) ?>;
    const mapEl    = document.getElementById('booking-map');
    const statusEl = document.getElementById('map-status');

    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(location)}&limit=1`)
      .then(r => r.json())
      .then(results => {
        if (!results.length) {
          statusEl.innerHTML = '<i class="bi bi-exclamation-triangle me-1" style="color:var(--warning);"></i> Location not found on map.';
          mapEl.style.display = 'none';
          return;
        }

        const { lat, lon, display_name } = results[0];
        const map = L.map('booking-map').setView([lat, lon], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
          maxZoom: 19,
        }).addTo(map);

        const icon = L.divIcon({
          html: `<div style="background:var(--accent,#e8520a);width:14px;height:14px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35);"></div>`,
          iconSize:   [14, 14],
          iconAnchor: [7, 7],
          className:  '',
        });

        L.marker([lat, lon], { icon })
          .addTo(map)
          .bindPopup(`<strong style="font-size:13px;">${location}</strong><br><span style="font-size:11px;color:#666;">${display_name}</span>`)
          .openPopup();

        statusEl.innerHTML = `<i class="bi bi-geo-alt-fill me-1" style="color:var(--accent,#e8520a);"></i> ${display_name}`;
      })
      .catch(() => {
        statusEl.innerHTML = '<i class="bi bi-wifi-off me-1" style="color:var(--danger);"></i> Could not load map.';
        mapEl.style.display = 'none';
      });
  })();
</script>

</body>
</html>