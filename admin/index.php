<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Admin Dashboard
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Booking.php';
require_once __DIR__ . '/includes/auth.php';

session_start();
requireAdmin();

$pageTitle = 'Dashboard';
$adminPage = 'dashboard';

$pdo = db();

// Stats
$stats = [
    'total'     => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'pending'   => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='Pending'")->fetchColumn(),
    'confirmed' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('Approved','Confirmed')")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='Completed'")->fetchColumn(),
    'revenue'   => $pdo->query("SELECT IFNULL(SUM(amount),0) FROM payments WHERE status='Confirmed'")->fetchColumn(),
    'payments'  => $pdo->query("SELECT COUNT(*) FROM payments WHERE status='Pending'")->fetchColumn(),
];

// Recent bookings
$recentBookings = $pdo->query("
    SELECT * FROM v_bookings_full ORDER BY created_at DESC LIMIT 10
")->fetchAll();

// Monthly chart data (last 6 months)
$monthlyData = $pdo->query("
    SELECT DATE_FORMAT(preferred_date,'%b %Y') AS month, COUNT(*) AS total
    FROM bookings
    WHERE preferred_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(preferred_date,'%Y-%m')
    ORDER BY preferred_date
")->fetchAll();

$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>';
require_once __DIR__ . '/includes/header.php';
?>

<main class="sb-admin-main">

  <!-- Page header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 style="font-size:1.8rem;color:var(--gold);margin:0;">Dashboard</h1>
      <p style="color:var(--text-dim);font-size:13px;margin:4px 0 0;">Welcome back, <?= htmlspecialchars(adminUser()['full_name']) ?></p>
    </div>
    <a href="<?= APP_URL ?>/admin/pages/bookings.php" class="btn-gold btn-sm">
      <i class="bi bi-calendar-plus"></i> View Bookings
    </a>
  </div>

  <!-- Stat cards -->
  <div class="stat-cards-row mb-4">
  <?php
  $statCards = [
    ['bi bi-calendar-check', 'Total Bookings',  $stats['total'],     ''],
    ['bi bi-hourglass-split','Pending Review',   $stats['pending'],   ''],
    ['bi bi-check-circle',   'Active',           $stats['confirmed'], ''],
    ['bi bi-star',           'Completed',        $stats['completed'], ''],
    ['bi bi-cash-stack',     'Revenue (₱)',      number_format($stats['revenue'],2), ''],
    ['bi bi-credit-card',    'Payments Pending', $stats['payments'],  ''],
  ];
  foreach ($statCards as [$icon, $label, $value, $extra]): ?>
  <div class="stat-card">
    <div class="stat-icon"><i class="<?= $icon ?>"></i></div>
    <div>
      <div class="stat-value"><?= $value ?></div>
      <div class="stat-label"><?= $label ?></div>
    </div>
  </div>
  <?php endforeach; ?>
  </div>

  <div class="row g-4">
    <!-- Chart -->
    <div class="col-lg-7">
      <div class="sb-card h-100">
        <div class="sb-card-header">
          <h4 style="color:var(--text);margin:0;font-size:1rem;">
            <i class="bi bi-bar-chart me-2" style="color:var(--gold);"></i>Monthly Bookings
          </h4>
        </div>
        <canvas id="bookingsChart" style="max-height:280px;"></canvas>
      </div>
    </div>

    <!-- Status breakdown -->
    <div class="col-lg-5">
      <div class="sb-card h-100">
        <div class="sb-card-header">
          <h4 style="color:var(--text);margin:0;font-size:1rem;">
            <i class="bi bi-pie-chart me-2" style="color:var(--gold);"></i>Status Breakdown
          </h4>
        </div>
        <?php
        $statusBreakdown = $pdo->query("
            SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status
        ")->fetchAll();
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
        foreach ($statusBreakdown as $row): ?>
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="badge-status badge-<?= $statusBadges[$row['status']] ?? 'pending' ?>"><?= $row['status'] ?></span>
          <strong style="color:var(--text);"><?= $row['cnt'] ?></strong>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Recent bookings table -->
  <div class="sb-card mt-4">
    <div class="sb-card-header d-flex justify-content-between align-items-center">
      <h4 style="color:var(--text);margin:0;font-size:1rem;">
        <i class="bi bi-clock-history me-2" style="color:var(--gold);"></i>Recent Bookings
      </h4>
      <a href="<?= APP_URL ?>/admin/pages/bookings.php" style="color:var(--gold);font-size:13px;text-decoration:none;">
        View all <i class="bi bi-arrow-right"></i>
      </a>
    </div>
    <div style="overflow-x:auto;">
      <table class="sb-table">
        <thead>
          <tr>
            <th>Reference</th>
            <th>Client</th>
            <th>Event</th>
            <th>Date</th>
            <th>Service</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentBookings as $b):
            $badgeClass = $statusBadges[$b['status']] ?? 'pending';
          ?>
          <tr>
            <td><code style="color:var(--gold);font-size:12px;"><?= htmlspecialchars($b['booking_ref']) ?></code></td>
            <td>
              <div style="font-size:14px;"><?= htmlspecialchars($b['full_name']) ?></div>
              <div style="font-size:11px;color:var(--text-dim);"><?= htmlspecialchars($b['email']) ?></div>
            </td>
            <td><?= htmlspecialchars($b['event_type']) ?></td>
            <td style="font-size:13px;white-space:nowrap;"><?= date('M j, Y', strtotime($b['preferred_date'])) ?></td>
            <td>
              <span style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($b['service_type']) ?></span>
            </td>
            <td><span class="badge-status badge-<?= $badgeClass ?>"><?= htmlspecialchars($b['status']) ?></span></td>
            <td>
              <div class="action-btns">
                <a href="<?= APP_URL ?>/admin/pages/booking_detail.php?ref=<?= urlencode($b['booking_ref']) ?>"
                   class="btn-outline btn-sm"><i class="bi bi-eye"></i></a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentBookings)): ?>
          <tr><td colspan="7" class="text-center" style="color:var(--text-dim);padding:32px;">No bookings yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<!-- Chat Button -->
<div id="sb-chat-btn"
     onclick="sbChatToggle()"
     style="position:fixed;bottom:28px;right:28px;z-index:9999;
            width:56px;height:56px;border-radius:50%;
            background:var(--gold);color:#fff;
            display:flex;align-items:center;justify-content:center;
            cursor:pointer;box-shadow:0 4px 18px rgba(201,169,110,.45);
            transition:transform .2s,box-shadow .2s;">
  <i class="bi bi-chat-dots-fill" style="font-size:1.5rem;"></i>
  <span id="sb-chat-badge"
        style="display:none;position:absolute;top:-4px;right:-4px;
               background:#dc2626;color:#fff;border-radius:50%;
               width:18px;height:18px;font-size:10px;font-weight:700;
               align-items:center;justify-content:center;">0</span>
</div>

<!-- Chat Box -->
<div id="sb-chat-box"
     style="display:none;position:fixed;bottom:96px;right:28px;z-index:9998;
            width:360px;max-height:540px;
            background:var(--bg-card);border:1px solid var(--border);
            border-radius:var(--radius);box-shadow:0 8px 32px rgba(0,0,0,.15);
            flex-direction:column;overflow:hidden;">

  <!-- Header -->
  <div style="background:var(--gold);padding:14px 18px;display:flex;align-items:center;justify-content:space-between;">
    <div style="display:flex;align-items:center;gap:10px;">
      <div style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.2);
                  display:flex;align-items:center;justify-content:center;">
        <i class="bi bi-headset" style="color:#fff;font-size:1.1rem;"></i>
      </div>
      <div>
        <div style="color:#fff;font-weight:700;font-size:14px;">Live Chat</div>
        <div style="color:rgba(255,255,255,.75);font-size:11px;" id="sb-active-session">No session selected</div>
      </div>
    </div>
    <button onclick="sbChatToggle()"
            style="background:transparent;border:none;color:rgba(255,255,255,.8);font-size:1.4rem;cursor:pointer;line-height:1;">×</button>
  </div>

  <!-- Sessions list -->
  <div id="sb-sessions-panel" style="flex:1;overflow-y:auto;max-height:440px;">
    <div style="padding:10px 14px;font-size:11px;letter-spacing:.08em;text-transform:uppercase;
                color:var(--text-dim);border-bottom:1px solid var(--border);">Conversations</div>
    <div id="sb-sessions-list"></div>
    <div id="sb-no-sessions" style="padding:32px;text-align:center;color:var(--text-dim);font-size:13px;">
      No conversations yet.
    </div>
  </div>

  <!-- Chat window -->
  <div id="sb-chat-window" style="display:none;flex-direction:column;flex:1;">
    <div style="padding:8px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px;">
      <button onclick="sbBackToSessions()"
              style="background:transparent;border:none;color:var(--text-muted);cursor:pointer;font-size:13px;padding:0;">
        <i class="bi bi-arrow-left"></i> Back
      </button>
    </div>
    <div id="sb-chat-messages"
         style="flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:8px;min-height:240px;max-height:320px;"></div>
    <div style="padding:10px 12px;border-top:1px solid var(--border);display:flex;gap:8px;">
      <input id="sb-chat-input" type="text" placeholder="Reply to client..."
             style="flex:1;padding:8px 12px;border:1.5px solid var(--border);border-radius:var(--radius-sm);
                    font-size:13px;font-family:'Inter',sans-serif;outline:none;background:var(--bg);"
             onkeydown="if(event.key==='Enter')sbAdminSend()">
      <button onclick="sbAdminSend()"
              style="padding:8px 14px;background:var(--gold);color:#fff;border:none;
                     border-radius:var(--radius-sm);cursor:pointer;font-size:13px;font-weight:600;">
        <i class="bi bi-send"></i>
      </button>
    </div>
  </div>
</div>

<script>
const ctx = document.getElementById('bookingsChart');
if (ctx) {
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($monthlyData, 'month')) ?>,
      datasets: [{
        label: 'Bookings',
        data: <?= json_encode(array_column($monthlyData, 'total')) ?>,
        backgroundColor: 'rgba(201,169,110,.25)',
        borderColor: '#c9a96e',
        borderWidth: 2,
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: '#888', font: { size: 11 } } },
        y: { grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: '#888', font: { size: 11 }, stepSize: 1 }, beginAtZero: true },
      },
    }
  });
}

document.getElementById('adminSidebar').addEventListener('click', () => {
  document.getElementById('adminContent').classList.toggle('collapsed',
    document.getElementById('adminSidebar').classList.contains('collapsed'));
});
</script>

<script>
(function () {
  const API     = '<?= APP_URL ?>/actions/chat.php';
  let isOpen    = false;
  let activeSid = null;
  let lastId    = 0;
  let pollTimer = null;
  let sessionPollTimer = null;

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  // ── Badge ─────────────────────────────────
  function updateUnreadBadge() {
    fetch(`${API}?action=unread`)
      .then(r => r.json())
      .then(data => {
        const badge = document.getElementById('sb-chat-badge');
        const count = data.count || 0;
        badge.textContent   = count;
        badge.style.display = count > 0 ? 'flex' : 'none';

        // Also pulse the button if there are unread messages
        const btn = document.getElementById('sb-chat-btn');
        if (count > 0) {
          btn.style.boxShadow = '0 0 0 0 rgba(220,38,38,.7)';
          btn.style.animation = 'sb-pulse 1.5s infinite';
        } else {
          btn.style.animation = '';
          btn.style.boxShadow = '0 4px 18px rgba(201,169,110,.45)';
        }
      });
  }

  window.sbChatToggle = function () {
    isOpen = !isOpen;
    const box = document.getElementById('sb-chat-box');
    box.style.display = isOpen ? 'flex' : 'none';
    if (isOpen) { loadSessions(); startSessionPoll(); }
    else stopPoll();
  };

  // ── Sessions ──────────────────────────────
  function loadSessions() {
    fetch(`${API}?action=sessions`)
      .then(r => r.json())
      .then(data => {
        const list     = document.getElementById('sb-sessions-list');
        const noSess   = document.getElementById('sb-no-sessions');
        const sessions = data.sessions || [];
        list.innerHTML = '';
        noSess.style.display = sessions.length ? 'none' : 'block';

        sessions.forEach(s => {
          const div = document.createElement('div');
          div.style.cssText = `padding:11px 14px;cursor:pointer;border-bottom:1px solid var(--border);
                               background:${s.session_id === activeSid ? 'var(--accent-subtle,#fff3ee)' : ''};
                               transition:background .15s;`;
          div.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span style="font-size:12px;font-weight:600;color:var(--text);font-family:monospace;">
                ${escHtml(s.client_name || s.session_id.slice(0,18))}
              </span>
              ${parseInt(s.unread) > 0
                ? `<span style="background:#dc2626;color:#fff;border-radius:99px;padding:2px 7px;font-size:10px;font-weight:700;">${s.unread}</span>`
                : ''}
            </div>
            <div style="font-size:11px;color:var(--text-dim);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px;">
              ${escHtml((s.last_msg || '').slice(0,55))}
            </div>`;
          div.addEventListener('mouseenter', () => { if (s.session_id !== activeSid) div.style.background = 'var(--bg)'; });
          div.addEventListener('mouseleave', () => { if (s.session_id !== activeSid) div.style.background = ''; });
          div.addEventListener('click',      () => openSession(s.session_id));
          list.appendChild(div);
        });
      });
  }

  function openSession(sid) {
    activeSid = sid; lastId = 0;
    document.getElementById('sb-sessions-panel').style.display = 'none';
    document.getElementById('sb-chat-window').style.display    = 'flex';
    document.getElementById('sb-active-session').textContent   = sid.slice(0,20) + '…';
    document.getElementById('sb-chat-messages').innerHTML      = '';
    stopPoll();
    pollMessages();
    pollTimer = setInterval(pollMessages, 3000);
    document.getElementById('sb-chat-input').focus();
  }

  window.sbBackToSessions = function () {
    activeSid = null; lastId = 0;
    stopPoll();
    document.getElementById('sb-chat-window').style.display    = 'none';
    document.getElementById('sb-sessions-panel').style.display = 'block';
    document.getElementById('sb-active-session').textContent   = 'No session selected';
    loadSessions(); startSessionPoll();
  };

  // ── Messages ──────────────────────────────
  function pollMessages() {
    if (!activeSid) return;
    fetch(`${API}?action=poll&session_id=${encodeURIComponent(activeSid)}&since=${lastId}&viewer=admin`)
      .then(r => r.json())
      .then(data => {
        (data.messages || []).forEach(m => {
          if (m.id > lastId) {
            lastId = m.id;
            appendMessage(m);
            // Play sound on new client message
            if (m.sender === 'client') playNotifSound();
          }
        });
        updateUnreadBadge();
      });
  }

  function appendMessage(m) {
    const wrap    = document.getElementById('sb-chat-messages');
    const isAdmin = m.sender === 'admin';
    const div     = document.createElement('div');
    div.style.cssText = `display:flex;justify-content:${isAdmin ? 'flex-end' : 'flex-start'};`;
    div.innerHTML = `
      <div style="max-width:78%;padding:8px 12px;
                  border-radius:${isAdmin ? '12px 12px 2px 12px' : '12px 12px 12px 2px'};
                  background:${isAdmin ? 'var(--gold)' : 'var(--bg)'};
                  color:${isAdmin ? '#fff' : 'var(--text)'};
                  font-size:13px;line-height:1.5;
                  border:${isAdmin ? 'none' : '1px solid var(--border)'};">
        ${escHtml(m.message)}
      </div>`;
    wrap.appendChild(div);
    wrap.scrollTop = wrap.scrollHeight;
  }

  window.sbAdminSend = function () {
    if (!activeSid) return;
    const input = document.getElementById('sb-chat-input');
    const msg   = input.value.trim();
    if (!msg) return;
    input.value = '';

    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=send&session_id=${encodeURIComponent(activeSid)}&sender=admin&message=${encodeURIComponent(msg)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.id) {
            lastId = data.id;  // ✅ set lastId first so poll skips it
            appendMessage({ sender: 'admin', message: msg }); // ✅ show once
        }
    });
    // ❌ no pollMessages() here, no appendMessage outside .then()
};

  // ── Sound notification ────────────────────
  function playNotifSound() {
    try {
      const ctx  = new (window.AudioContext || window.webkitAudioContext)();
      const osc  = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.4);
    } catch(e) {}
  }

  // ── Browser notification ──────────────────
  function requestNotifPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }
  }

  function showBrowserNotif(message) {
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification('New message — SOULBUD Chat', {
        body: message,
        icon: '<?= APP_URL ?>/assets/images/favicon.svg',
      });
    }
  }

  // ── Polling helpers ───────────────────────
  function startSessionPoll() {
    sessionPollTimer = setInterval(() => {
      loadSessions();
      updateUnreadBadge();
    }, 3000); // every 3s when open
  }

  function stopPoll() {
    clearInterval(pollTimer);
    clearInterval(sessionPollTimer);
    pollTimer = sessionPollTimer = null;
  }

  // Hover effect
  const btn = document.getElementById('sb-chat-btn');
  btn.addEventListener('mouseenter', () => btn.style.transform = 'scale(1.1)');
  btn.addEventListener('mouseleave', () => btn.style.transform = 'scale(1)');

  // ── Init ──────────────────────────────────
  requestNotifPermission();
  updateUnreadBadge();                          // check immediately on load
  setInterval(updateUnreadBadge, 4000);         // keep checking every 4s always
})();
</script>

<style>
@keyframes sb-pulse {
  0%   { box-shadow: 0 0 0 0 rgba(220,38,38,.6); }
  70%  { box-shadow: 0 0 0 10px rgba(220,38,38,0); }
  100% { box-shadow: 0 0 0 0 rgba(220,38,38,0); }
}
</style>

</div><!-- end .sb-admin-content -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/soulbud.js"></script>
</body>
</html>