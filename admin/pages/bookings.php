<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Admin: Bookings Management
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Booking.php';
require_once __DIR__ . '/../../includes/Mailer.php';
require_once __DIR__ . '/../includes/auth.php';

session_start();
requireAdmin();

$pageTitle = 'Bookings';
$adminPage = 'bookings';

$pdo = db();

// Handle status update
$actionMsg = '';
$actionType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action    = $_POST['action'];
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $adminNote = trim($_POST['admin_notes'] ?? '');
    $rDate     = $_POST['rescheduled_date'] ?? null;
    $rTime     = $_POST['rescheduled_time'] ?? null;

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
        Booking::updateStatus($bookingId, $newStatus, $adminNote, $rDate, $rTime);

        // Get booking for email
        $stmt = $pdo->prepare("SELECT * FROM v_bookings_full WHERE id=?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        if ($booking) {
            if ($action === 'approve') {
                Mailer::sendConfirmation($booking);
            } elseif (in_array($action, ['reject','reschedule'])) {
                Mailer::sendStatusUpdate($booking, $action === 'reject' ? 'rejected' : 'rescheduled');
            }
        }
        $actionMsg  = "Booking status updated to <strong>{$newStatus}</strong>.";
        $actionType = 'success';
    }
}

// Filter / search
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['q']      ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$where  = '1=1';
$params = [];
if ($statusFilter) { $where .= ' AND status=?'; $params[] = $statusFilter; }
if ($search)       { $where .= ' AND (full_name LIKE ? OR email LIKE ? OR booking_ref LIKE ?)';
                     $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM v_bookings_full WHERE $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$pages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT * FROM v_bookings_full WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([...$params, $perPage, $offset]);
$bookings = $stmt->fetchAll();

$allStatuses = ['Waiting for Email Verification','Pending','Approved','Confirmed','Completed','Cancelled','Rejected','Rescheduled'];
$statusBadges = ['Waiting for Email Verification'=>'waiting','Pending'=>'pending','Approved'=>'approved',
                 'Confirmed'=>'confirmed','Completed'=>'completed','Cancelled'=>'cancelled',
                 'Rejected'=>'rejected','Rescheduled'=>'rescheduled'];

require_once __DIR__ . '/../includes/header.php';
?>

<main class="sb-admin-main">

  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
      <h1 style="font-size:1.8rem;color:var(--gold);margin:0;">Bookings</h1>
      <p style="color:var(--text-dim);font-size:13px;margin:4px 0 0;"><?= $total ?> total records</p>
    </div>
  </div>

  <?php if ($actionMsg): ?>
  <div class="sb-alert sb-alert-<?= $actionType ?> mb-3" data-auto-dismiss>
    <i class="bi bi-check-circle"></i> <?= $actionMsg ?>
  </div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="sb-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="subtitle mb-1">Search</label>
        <input type="text" name="q" class="sb-input" placeholder="Name, email or reference..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-4">
        <label class="subtitle mb-1">Filter by Status</label>
        <select name="status" class="sb-select">
          <option value="">All Statuses</option>
          <?php foreach ($allStatuses as $s): ?>
          <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn-gold" style="flex:1;justify-content:center;">
          <i class="bi bi-search"></i> Search
        </button>
        <a href="<?= APP_URL ?>/admin/pages/bookings.php" class="btn-outline" style="flex-shrink:0;">
          <i class="bi bi-x"></i>
        </a>
      </div>
    </form>
  </div>

  <!-- Table -->
  <div class="sb-card">
    <div style="overflow-x:auto;">
      <table class="sb-table">
        <thead>
          <tr>
            <th>Reference</th>
            <th>Client</th>
            <th>Event</th>
            <th>Date & Time</th>
            <th>Service</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b):
            $bc = $statusBadges[$b['status']] ?? 'pending';
          ?>
          <tr>
            <td><code style="color:var(--gold);font-size:12px;"><?= htmlspecialchars($b['booking_ref']) ?></code></td>
            <td>
              <div><?= htmlspecialchars($b['full_name']) ?></div>
              <div style="font-size:11px;color:var(--text-dim);"><?= htmlspecialchars($b['email']) ?></div>
              <div style="font-size:11px;color:var(--text-dim);"><?= htmlspecialchars($b['contact_number']) ?></div>
            </td>
            <td><?= htmlspecialchars($b['event_type']) ?></td>
            <td style="white-space:nowrap;font-size:13px;">
              <?= date('M j, Y', strtotime($b['preferred_date'])) ?><br>
              <span style="color:var(--text-dim);"><?= date('g:i A', strtotime($b['preferred_time'])) ?></span>
            </td>
            <td style="font-size:13px;"><?= htmlspecialchars($b['service_type']) ?></td>
            <td><span class="badge-status badge-<?= $bc ?>"><?= htmlspecialchars($b['status']) ?></span></td>
            <td>
              <?php if ($b['payment_status']): ?>
              <span class="badge-status badge-<?= strtolower($b['payment_status']) ?>">
                <?= $b['payment_status'] ?>
              </span>
              <?php else: ?>
              <span style="color:var(--text-dim);font-size:12px;">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="action-btns">
                <a href="<?= APP_URL ?>/admin/pages/booking_detail.php?ref=<?= urlencode($b['booking_ref']) ?>"
                   class="btn-outline btn-sm" title="View"><i class="bi bi-eye"></i></a>

                <?php if ($b['status'] === 'Pending'): ?>
                <!-- Quick approve -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Approve this booking?');">
                  <input type="hidden" name="action"     value="approve">
                  <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                  <button type="submit" class="btn-gold btn-sm" title="Approve">
                    <i class="bi bi-check-lg"></i>
                  </button>
                </form>
                <!-- Quick reject -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Reject this booking?');">
                  <input type="hidden" name="action"     value="reject">
                  <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                  <button type="submit" class="btn-danger btn-gold btn-sm" title="Reject">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($bookings)): ?>
          <tr><td colspan="8" class="text-center" style="color:var(--text-dim);padding:40px;">No bookings found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="d-flex justify-content-center gap-2 mt-4 flex-wrap">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a href="?page=<?= $i ?>&status=<?= urlencode($statusFilter) ?>&q=<?= urlencode($search) ?>"
         class="<?= $i === $page ? 'btn-gold' : 'btn-outline' ?> btn-sm">
        <?= $i ?>
      </a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>

</main>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/soulbud.js"></script>
</body></html>
