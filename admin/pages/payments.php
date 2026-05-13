<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Admin: Payments
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Mailer.php';
require_once __DIR__ . '/../includes/auth.php';

session_start();
requireAdmin();

$pageTitle = 'Payments';
$adminPage = 'payments';
$pdo       = db();

$actionMsg  = '';
$actionType = '';

// Handle confirm / reject payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $payId  = (int)($_POST['payment_id'] ?? 0);
  $action = $_POST['action'] ?? '';

  if ($payId && in_array($action, ['confirm', 'reject'])) {
    $newStatus = $action === 'confirm' ? 'Confirmed' : 'Rejected';
    $pdo->prepare("UPDATE payments SET status=?, confirmed_at=? WHERE id=?")
      ->execute([$newStatus, $action === 'confirm' ? date('Y-m-d H:i:s') : null, $payId]);

    if ($action === 'confirm') {
      // Get booking for email notification
      $stmt = $pdo->prepare("
                SELECT v.* FROM v_bookings_full v
                JOIN payments p ON p.booking_id = v.id
                WHERE p.id = ?
            ");
      $stmt->execute([$payId]);
      $booking = $stmt->fetch();
      if ($booking) Mailer::sendStatusUpdate($booking, 'payment');
    }
    $actionMsg  = "Payment <strong>{$newStatus}</strong> successfully.";
    $actionType = 'success';
  }
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$methodFilter = $_GET['method'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = '1=1';
$params = [];
if ($statusFilter) {
  $where .= ' AND p.status=?';
  $params[] = $statusFilter;
}
if ($methodFilter) {
  $where .= ' AND p.payment_method=?';
  $params[] = $methodFilter;
}

$totalStmt = $pdo->prepare("
    SELECT COUNT(*) FROM payments p
    JOIN bookings b ON b.id = p.booking_id
    WHERE $where
");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$pages = ceil($total / $perPage);

$stmt = $pdo->prepare("
    SELECT p.*,
           b.booking_ref, b.event_type, b.preferred_date,
           c.full_name, c.email
    FROM payments p
    JOIN bookings b ON b.id = p.booking_id
    JOIN clients  c ON c.id = b.client_id
    WHERE $where
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([...$params, $perPage, $offset]);
$payments = $stmt->fetchAll();

// Summary stats
$totalRevenue = $pdo->query("SELECT IFNULL(SUM(amount),0) FROM payments WHERE status='Confirmed'")->fetchColumn();
$totalPending = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='Pending'")->fetchColumn();
$totalConfirmed = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='Confirmed'")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<main class="sb-admin-main">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 style="font-size:1.8rem;color:var(--gold);margin:0;">Payments</h1>
      <p style="color:var(--text-dim);font-size:13px;margin:4px 0 0;"><?= $total ?> total records</p>
    </div>
  </div>

  <?php if ($actionMsg): ?>
    <div class="sb-alert sb-alert-<?= $actionType ?> mb-3" data-auto-dismiss>
      <i class="bi bi-check-circle"></i> <?= $actionMsg ?>
    </div>
  <?php endif; ?>

  <!-- Summary stat cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
        <div>
          <div class="stat-value">₱<?= number_format($totalRevenue, 2) ?></div>
          <div class="stat-label">Total Revenue</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
        <div>
          <div class="stat-value"><?= $totalPending ?></div>
          <div class="stat-label">Pending Payments</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
        <div>
          <div class="stat-value"><?= $totalConfirmed ?></div>
          <div class="stat-label">Confirmed Payments</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="sb-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="subtitle mb-1">Status</label>
        <select name="status" class="sb-select">
          <option value="">All Statuses</option>
          <option value="Pending" <?= $statusFilter === 'Pending'   ? 'selected' : '' ?>>Pending</option>
          <option value="Confirmed" <?= $statusFilter === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
          <option value="Rejected" <?= $statusFilter === 'Rejected'  ? 'selected' : '' ?>>Rejected</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="subtitle mb-1">Method</label>
        <select name="method" class="sb-select">
          <option value="">All Methods</option>
          <option value="GCash" <?= $methodFilter === 'GCash'         ? 'selected' : '' ?>>GCash</option>
          <option value="Bank Transfer" <?= $methodFilter === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
          <option value="Cash" <?= $methodFilter === 'Cash'          ? 'selected' : '' ?>>Cash</option>
        </select>
      </div>
      <div class="col-md-4 d-flex gap-2">
        <button type="submit" class="btn-gold" style="flex:1;justify-content:center;">
          <i class="bi bi-funnel"></i> Filter
        </button>
        <a href="<?= APP_URL ?>/admin/pages/payments.php" class="btn-outline"><i class="bi bi-x"></i></a>
      </div>
    </form>
  </div>

  <!-- Table -->
  <div class="sb-card">
    <div style="overflow-x:auto;">
      <table class="sb-table">
        <thead>
          <tr>
            <th>Booking Ref</th>
            <th>Client</th>
            <th>Event</th>
            <th>Method</th>
            <th>Amount</th>
            <th>Proof</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td>
                <a href="<?= APP_URL ?>/admin/pages/booking_detail.php?ref=<?= urlencode($p['booking_ref']) ?>"
                  style="color:var(--gold);text-decoration:none;font-size:12px;font-family:monospace;">
                  <?= htmlspecialchars($p['booking_ref']) ?>
                </a>
              </td>
              <td>
                <div style="font-size:14px;"><?= htmlspecialchars($p['full_name']) ?></div>
                <div style="font-size:11px;color:var(--text-dim);"><?= htmlspecialchars($p['email']) ?></div>
              </td>
              <td style="font-size:13px;"><?= htmlspecialchars($p['event_type']) ?></td>
              <td>
                <span style="font-size:12px;padding:3px 10px;border-radius:20px;background:rgba(201,169,110,.1);color:var(--gold);">
                  <?= htmlspecialchars($p['payment_method']) ?>
                </span>
              </td>
              <td style="font-weight:600;color:var(--text);">₱<?= number_format($p['amount'], 2) ?></td>
              <td>
                <?php if ($p['proof_filename']): ?>
                  <a href="<?= APP_URL ?>/uploads/payments/<?= htmlspecialchars($p['proof_filename']) ?>"
                    target="_blank" class="btn-outline btn-sm">
                    <i class="bi bi-image"></i> View
                  </a>
                <?php else: ?>
                  <span style="color:var(--text-dim);font-size:12px;">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php
                $pc = ['Pending' => 'pending', 'Confirmed' => 'confirmed', 'Rejected' => 'rejected'];
                $badge = $pc[$p['status']] ?? 'pending';
                ?>
                <span class="badge-status badge-<?= $badge ?>"><?= $p['status'] ?></span>
              </td>
              <td style="font-size:12px;color:var(--text-dim);white-space:nowrap;">
                <?= date('M j, Y', strtotime($p['created_at'])) ?>
              </td>
              <td>
                <?php if ($p['status'] === 'Pending'): ?>
                  <div class="action-btns">
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="action" value="confirm">
                      <button type="submit" class="btn-gold btn-sm"
                        onclick="return confirm('Confirm this payment?')">
                        <i class="bi bi-check-lg"></i>
                      </button>
                    </form>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="action" value="reject">
                      <button type="submit" class="btn-gold btn-danger btn-sm"
                        onclick="return confirm('Reject this payment?')">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </form>
                  </div>
                <?php else: ?>
                  <span style="color:var(--text-dim);font-size:12px;">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($payments)): ?>
            <tr>
              <td colspan="9" class="text-center" style="color:var(--text-dim);padding:40px;">
                No payment records found.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <div class="d-flex justify-content-center gap-2 mt-4 flex-wrap">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <a href="?page=<?= $i ?>&status=<?= urlencode($statusFilter) ?>&method=<?= urlencode($methodFilter) ?>"
            class="<?= $i === $page ? 'btn-gold' : 'btn-outline' ?> btn-sm"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>

</main>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/soulbud.js"></script>
</body>

</html>