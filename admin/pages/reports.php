<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Admin: Reports
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

session_start();
requireAdmin();

$pageTitle = 'Reports';
$adminPage = 'reports';
$pdo       = db();

// Date range filter
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-6 months'));
$to = $_GET['to'] ?? date('Y-m-d', strtotime('+3 months'));

// ── Summary stats for range ────────────────────────
$summaryStmt = $pdo->prepare("
    SELECT
        COUNT(*)                       AS total_bookings,
        SUM(status='Pending')          AS pending,
        SUM(status='Approved')         AS approved,
        SUM(status='Confirmed')        AS confirmed,
        SUM(status='Completed')        AS completed,
        SUM(status='Cancelled')        AS cancelled,
        SUM(status='Rejected')         AS rejected
    FROM bookings
    WHERE preferred_date BETWEEN ? AND ?
");
$summaryStmt->execute([$from, $to]);
$summary = $summaryStmt->fetch();

$revenueStmt = $pdo->prepare("
    SELECT IFNULL(SUM(p.amount),0) AS revenue
    FROM payments p
    JOIN bookings b ON b.id = p.booking_id
    WHERE p.status='Confirmed' AND b.preferred_date BETWEEN ? AND ?
");
$revenueStmt->execute([$from, $to]);
$revenue = $revenueStmt->fetchColumn();

// ── Monthly breakdown ──────────────────────────────
$monthly = $pdo->query("SELECT * FROM v_monthly_report LIMIT 12")->fetchAll();

// ── Top event types ────────────────────────────────
$eventTypes = $pdo->prepare("
    SELECT event_type, COUNT(*) AS cnt
    FROM bookings
    WHERE preferred_date BETWEEN ? AND ?
    GROUP BY event_type
    ORDER BY cnt DESC
    LIMIT 8
");
$eventTypes->execute([$from, $to]);
$eventTypes = $eventTypes->fetchAll();

// ── Service type breakdown ─────────────────────────
$services = $pdo->prepare("
    SELECT service_type, COUNT(*) AS cnt
    FROM bookings
    WHERE preferred_date BETWEEN ? AND ?
    GROUP BY service_type
");
$services->execute([$from, $to]);
$services = $services->fetchAll();

// ── Daily bookings for range ───────────────────────
$daily = $pdo->prepare("
    SELECT preferred_date AS day, COUNT(*) AS cnt
    FROM bookings
    WHERE preferred_date BETWEEN ? AND ?
    GROUP BY preferred_date
    ORDER BY preferred_date
");
$daily->execute([$from, $to]);
$daily = $daily->fetchAll();

// ── Bookings in range for export ───────────────────
$exportData = $pdo->prepare("
    SELECT b.booking_ref, c.full_name, c.email, c.contact_number,
           b.event_type, b.service_type, b.preferred_date, b.preferred_time,
           b.event_location, b.status, b.created_at,
           p.payment_method, p.amount, p.status AS payment_status
    FROM bookings b
    JOIN clients c ON c.id = b.client_id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.preferred_date BETWEEN ? AND ?
    ORDER BY b.preferred_date
");
$exportData->execute([$from, $to]);
$exportRows = $exportData->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<main class="sb-admin-main">

  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
      <h1 style="font-size:1.8rem;color:var(--gold);margin:0;">Reports</h1>
      <p style="color:var(--text-dim);font-size:13px;margin:4px 0 0;">Analytics and booking history</p>
    </div>
    <button class="btn-gold btn-sm" onclick="exportCSV()">
      <i class="bi bi-download"></i> Export CSV
    </button>
  </div>

  <!-- Date range filter -->
  <div class="sb-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="subtitle mb-1">From Date</label>
        <input type="date" name="from" class="sb-input" value="<?= htmlspecialchars($from) ?>">
      </div>
      <div class="col-md-4">
        <label class="subtitle mb-1">To Date</label>
        <input type="date" name="to" class="sb-input" value="<?= htmlspecialchars($to) ?>">
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn-gold" style="width:100%;justify-content:center;">
          <i class="bi bi-funnel"></i> Apply Filter
        </button>
      </div>
    </form>
  </div>

  <!-- Summary cards -->
<div class="stat-cards-row mb-4">
  <?php
    $cards = [
      ['bi bi-calendar-check', 'Total',     $summary['total_bookings'], ''],
      ['bi bi-hourglass-split','Pending',   $summary['pending'],        ''],
      ['bi bi-check-circle',   'Confirmed', (int)$summary['approved'] + (int)$summary['confirmed'], ''],
      ['bi bi-star',           'Completed', $summary['completed'],      ''],
      ['bi bi-x-circle',       'Cancelled', (int)$summary['cancelled'] + (int)$summary['rejected'], ''],
      ['bi bi-cash-stack',     'Revenue',   '₱' . number_format($revenue, 2), ''],
    ];
  foreach ($cards as [$icon, $label, $value, $_]): ?>
  <div class="stat-card">
    <div class="stat-icon"><i class="<?= $icon ?>"></i></div>
    <div>
      <div class="stat-value"><?= $value ?></div>
      <div class="stat-label"><?= $label ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

  <div class="row g-4 mb-4">
    <!-- Daily chart -->
    <div class="col-lg-8">
      <div class="sb-card">
        <div class="sb-card-header">
          <h4 style="color:var(--text);font-size:1rem;margin:0;">
            <i class="bi bi-graph-up me-2" style="color:var(--gold);"></i>Bookings Over Time
          </h4>
        </div>
        <canvas id="dailyChart" style="max-height:260px;"></canvas>
      </div>
    </div>

    <!-- Service breakdown -->
    <div class="col-lg-4">
      <div class="sb-card h-100">
        <div class="sb-card-header">
          <h4 style="color:var(--text);font-size:1rem;margin:0;">
            <i class="bi bi-camera me-2" style="color:var(--gold);"></i>By Service
          </h4>
        </div>
        <canvas id="serviceChart" style="max-height:220px;margin:0 auto;display:block;"></canvas>
        <div class="mt-3">
          <?php foreach ($services as $s): ?>
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span style="color:var(--text-muted);font-size:13px;"><?= htmlspecialchars($s['service_type']) ?></span>
            <strong style="color:var(--text);"><?= $s['cnt'] ?></strong>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Top event types -->
    <div class="col-lg-5">
      <div class="sb-card">
        <div class="sb-card-header">
          <h4 style="color:var(--text);font-size:1rem;margin:0;">
            <i class="bi bi-tag me-2" style="color:var(--gold);"></i>Top Event Types
          </h4>
        </div>
        <?php $maxCount = max(array_column($eventTypes, 'cnt') ?: [1]); ?>
        <?php foreach ($eventTypes as $et): ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span style="color:var(--text-muted);font-size:13px;"><?= htmlspecialchars($et['event_type']) ?></span>
            <span style="color:var(--gold);font-size:13px;font-weight:600;"><?= $et['cnt'] ?></span>
          </div>
          <div style="height:4px;background:var(--border);border-radius:2px;overflow:hidden;">
            <div style="height:100%;width:<?= round($et['cnt'] / $maxCount * 100) ?>%;
                        background:linear-gradient(to right,var(--gold),var(--gold-dark));border-radius:2px;transition:.6s;"></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($eventTypes)): ?>
        <p style="color:var(--text-dim);font-size:13px;">No data for selected range.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Monthly table -->
    <div class="col-lg-7">
      <div class="sb-card">
        <div class="sb-card-header">
          <h4 style="color:var(--text);font-size:1rem;margin:0;">
            <i class="bi bi-table me-2" style="color:var(--gold);"></i>Monthly Report
          </h4>
        </div>
        <div style="overflow-x:auto;">
          <table class="sb-table">
            <thead>
              <tr>
                <th>Month</th>
                <th>Total</th>
                <th>Confirmed</th>
                <th>Completed</th>
                <th>Cancelled</th>
                <th>Revenue</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($monthly as $m): ?>
              <tr>
                <td style="color:var(--gold);font-size:13px;"><?= htmlspecialchars($m['month']) ?></td>
                <td><?= $m['total_bookings'] ?></td>
                <td style="color:var(--success);"><?= $m['confirmed'] ?></td>
                <td><?= $m['completed'] ?></td>
                <td style="color:var(--danger);"><?= $m['cancelled'] ?></td>
                <td style="color:var(--gold);">₱<?= number_format($m['total_revenue'], 2) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($monthly)): ?>
              <tr><td colspan="6" class="text-center" style="color:var(--text-dim);padding:32px;">No data.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Export table (hidden, for CSV) -->
  <table id="exportTable" style="display:none;">
    <thead>
      <tr>
        <th>Booking Ref</th><th>Full Name</th><th>Email</th><th>Contact</th>
        <th>Event Type</th><th>Service</th><th>Date</th><th>Time</th>
        <th>Location</th><th>Status</th><th>Submitted</th>
        <th>Payment Method</th><th>Amount</th><th>Payment Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($exportRows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['booking_ref']) ?></td>
        <td><?= htmlspecialchars($r['full_name']) ?></td>
        <td><?= htmlspecialchars($r['email']) ?></td>
        <td><?= htmlspecialchars($r['contact_number']) ?></td>
        <td><?= htmlspecialchars($r['event_type']) ?></td>
        <td><?= htmlspecialchars($r['service_type']) ?></td>
        <td><?= $r['preferred_date'] ?></td>
        <td><?= $r['preferred_time'] ?></td>
        <td><?= htmlspecialchars($r['event_location']) ?></td>
        <td><?= htmlspecialchars($r['status']) ?></td>
        <td><?= $r['created_at'] ?></td>
        <td><?= htmlspecialchars($r['payment_method'] ?? '') ?></td>
        <td><?= $r['amount'] ?? '' ?></td>
        <td><?= htmlspecialchars($r['payment_status'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/soulbud.js"></script>

<script>
// Daily chart
const dailyCtx = document.getElementById('dailyChart');
if (dailyCtx) {
  new Chart(dailyCtx, {
    type: 'line',
    data: {
      labels: <?= json_encode(array_column($daily, 'day')) ?>,
      datasets: [{
        label: 'Bookings',
        data:  <?= json_encode(array_column($daily, 'cnt')) ?>,
        borderColor: '#c9a96e',
        backgroundColor: 'rgba(201,169,110,.08)',
        tension: .4,
        fill: true,
        pointBackgroundColor: '#c9a96e',
        pointRadius: 4,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: '#888', font: { size: 10 } } },
        y: { grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: '#888', stepSize: 1 }, beginAtZero: true },
      },
    }
  });
}

// Service doughnut
const serviceCtx = document.getElementById('serviceChart');
if (serviceCtx) {
  const labels = <?= json_encode(array_column($services, 'service_type')) ?>;
  const data   = <?= json_encode(array_column($services, 'cnt')) ?>;
  new Chart(serviceCtx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: ['#c9a96e','#5b9bd5','#4caf7d'],
        borderColor: '#111',
        borderWidth: 3,
      }]
    },
    options: {
      responsive: true,
      cutout: '65%',
      plugins: {
        legend: { position: 'bottom', labels: { color: '#888', font: { size: 11 } } }
      }
    }
  });
}

// Export CSV
function exportCSV() {
  const table = document.getElementById('exportTable');
  const rows  = [...table.querySelectorAll('tr')];
  const csv   = rows.map(r =>
    [...r.querySelectorAll('th,td')]
      .map(c => '"' + c.innerText.replace(/"/g, '""') + '"')
      .join(',')
  ).join('\n');
  const a = document.createElement('a');
  a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
  a.download = 'soulbud_report_<?= $from ?>_<?= $to ?>.csv';
  a.click();
}
</script>

</div>
</body></html>
