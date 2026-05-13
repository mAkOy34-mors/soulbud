<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Admin: Clients
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

session_start();
requireAdmin();

$pageTitle = 'Clients';
$adminPage = 'clients';
$pdo       = db();

$search  = trim($_GET['q']    ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = '1=1';
$params = [];
if ($search) {
    $where   .= ' AND (c.full_name LIKE ? OR c.email LIKE ? OR c.contact_number LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM clients c WHERE $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$pages = ceil($total / $perPage);

$stmt = $pdo->prepare("
    SELECT c.*,
           COUNT(b.id) AS total_bookings,
           MAX(b.created_at) AS last_booking
    FROM clients c
    LEFT JOIN bookings b ON b.client_id = c.id
    WHERE $where
    GROUP BY c.id
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([...$params, $perPage, $offset]);
$clients = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<main class="sb-admin-main">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 style="font-size:1.8rem;color:var(--gold);margin:0;">Clients</h1>
      <p style="color:var(--text-dim);font-size:13px;margin:4px 0 0;"><?= $total ?> total clients</p>
    </div>
  </div>

  <!-- Search -->
  <div class="sb-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-8">
        <label class="subtitle mb-1">Search</label>
        <input type="text" name="q" class="sb-input" placeholder="Name, email or phone..."
               value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-4 d-flex gap-2">
        <button type="submit" class="btn-gold" style="flex:1;justify-content:center;">
          <i class="bi bi-search"></i> Search
        </button>
        <?php if ($search): ?>
        <a href="<?= APP_URL ?>/admin/pages/clients.php" class="btn-outline"><i class="bi bi-x"></i></a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Table -->
  <div class="sb-card">
    <div style="overflow-x:auto;">
      <table class="sb-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Total Bookings</th>
            <th>Last Booking</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clients as $i => $c): ?>
          <tr>
            <td style="color:var(--text-dim);font-size:12px;"><?= $offset + $i + 1 ?></td>
            <td style="font-weight:500;"><?= htmlspecialchars($c['full_name']) ?></td>
            <td>
              <a href="mailto:<?= htmlspecialchars($c['email']) ?>"
                 style="color:var(--gold);text-decoration:none;font-size:13px;">
                <?= htmlspecialchars($c['email']) ?>
              </a>
            </td>
            <td style="font-size:13px;color:var(--text-muted);"><?= htmlspecialchars($c['contact_number']) ?></td>
            <td>
              <span style="background:rgba(201,169,110,.15);color:var(--gold);padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;">
                <?= $c['total_bookings'] ?>
              </span>
            </td>
            <td style="font-size:13px;color:var(--text-muted);">
              <?= $c['last_booking'] ? date('M j, Y', strtotime($c['last_booking'])) : '—' ?>
            </td>
            <td>
              <a href="<?= APP_URL ?>/admin/pages/bookings.php?q=<?= urlencode($c['email']) ?>"
                 class="btn-outline btn-sm">
                <i class="bi bi-calendar-check"></i> Bookings
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($clients)): ?>
          <tr><td colspan="7" class="text-center" style="color:var(--text-dim);padding:40px;">No clients found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
    <div class="d-flex justify-content-center gap-2 mt-4 flex-wrap">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"
         class="<?= $i === $page ? 'btn-gold' : 'btn-outline' ?> btn-sm"><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>

</main>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/soulbud.js"></script>
</body></html>
