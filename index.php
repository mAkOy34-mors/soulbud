<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Homepage
// ─────────────────────────────────────────
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('SOULBUD', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/Booking.php';

$pageTitle   = 'Home';
$currentPage = 'home';

$stats = [
  'total'    => Booking::count(),
  'pending'  => Booking::count('Pending'),
  'confirmed' => Booking::count('Confirmed'),
  'completed' => Booking::count('Completed'),
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Hero (BookedIn-style: full-width photo, dark overlay, left-aligned) ── -->
<div class="sb-hero" style="background-image: url('<?= APP_URL ?>/assets/images/bg.jpg');">
  <div class="sb-hero-content">
    <span class="sb-hero-eyebrow">Photography &amp; Videography · Est. 2024</span>
    <h1>Your creative buddy for every story.</h1>
    <p>Professional photography and videography — every frame tells your story with cinematic precision.</p>
    <div class="sb-hero-actions">
      <a href="<?= APP_URL ?>/book.php" class="btn-hero-primary">
        <i class="bi bi-calendar-plus"></i> Book an Appointment
      </a>
      <a href="<?= APP_URL ?>/pages/calendar.php" class="btn-hero-outline">
        <i class="bi bi-calendar3"></i> View Calendar
      </a>
    </div>
  </div>
</div>

<!-- ── Services ──────────────────────────────────── -->
<section class="sb-section" style="background:var(--bg-card);border-top:1px solid var(--border);border-bottom:1px solid var(--border);">
  <div class="sb-container">
    <p class="subtitle text-center mb-2">What We Offer</p>
    <h2 style="font-size:2.2rem;text-align:center;color:var(--text);margin-bottom:48px;font-weight:700;">Our Services</h2>
    <div class="row g-4">
      <?php
      $services = [
        ['bi bi-camera', 'Photography', 'From intimate portraits to grand events — every frame tells your story with timeless elegance.'],
        ['bi bi-camera-video', 'Videography', 'Cinematic films that breathe life into your memories with expert editing and storytelling.'],
        ['bi bi-collection', 'Full Package', 'Both photography and videography bundled for comprehensive coverage of your special day.'],
      ];
      foreach ($services as [$icon, $title, $desc]): ?>
        <div class="col-md-4" data-reveal>
          <div class="sb-card h-100 text-center" style="padding:40px 28px;">
            <i class="<?= $icon ?>" style="font-size:2.4rem;color:var(--accent);margin-bottom:20px;display:block;"></i>
            <h3 style="font-size:1.3rem;margin-bottom:12px;color:var(--text);font-weight:700;"><?= $title ?></h3>
            <div style="width:36px;height:3px;background:var(--accent);border-radius:2px;margin:0 auto 16px;"></div>
            <p style="color:var(--text-muted);line-height:1.8;font-size:14px;"><?= $desc ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Booking Process ────────────────────────────── -->
<section class="sb-section">
  <div class="sb-container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5" data-reveal>
        <p class="subtitle mb-2">Simple &amp; Fast</p>
        <h2 style="font-size:2.2rem;color:var(--text);margin-bottom:12px;font-weight:700;">How to Book</h2>
        <div style="width:36px;height:3px;background:var(--accent);border-radius:2px;margin-bottom:20px;"></div>
        <p style="color:var(--text-muted);line-height:1.8;">No account required — book your session in minutes with just your email address.</p>
      </div>
      <div class="col-lg-7">
        <?php
        $steps = [
          ['Fill out the booking form', 'Provide your event details, preferred date and service type.'],
          ['Verify your email', 'Click the verification link sent to your Gmail or email address.'],
          ['Admin review', 'Our team reviews your appointment request within 24 hours.'],
          ['Receive confirmation', 'Get your booking confirmation with payment instructions.'],
          ['Send payment', 'Upload your proof of payment via GCash, bank transfer, or cash.'],
          ['You\'re all set!', 'Your appointment is confirmed and saved in our calendar.'],
        ];
        foreach ($steps as $i => [$title, $desc]): ?>
          <div class="sb-step" data-reveal style="animation-delay:<?= $i * .08 ?>s;">
            <div class="sb-step-num"><?= $i + 1 ?></div>
            <div>
              <strong style="color:var(--text);display:block;margin-bottom:4px;"><?= $title ?></strong>
              <span style="color:var(--text-muted);font-size:14px;"><?= $desc ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ── Stats ─────────────────────────────────────── -->
<section style="background:var(--bg-card);border-top:1px solid var(--border);border-bottom:1px solid var(--border);padding:60px 0;">
  <div class="sb-container">
    <div class="row g-4 text-center">
      <?php
      $statItems = [
        ['Total Bookings',    $stats['total'],    'bi bi-calendar-check'],
        ['Pending Review',    $stats['pending'],  'bi bi-hourglass-split'],
        ['Confirmed',         $stats['confirmed'], 'bi bi-check-circle'],
        ['Completed Events',  $stats['completed'], 'bi bi-star'],
      ];
      foreach ($statItems as [$label, $value, $icon]): ?>
        <div class="col-6 col-md-3" data-reveal>
          <i class="<?= $icon ?>" style="font-size:1.8rem;color:var(--accent);margin-bottom:12px;display:block;"></i>
          <div style="font-family:'Inter',sans-serif;font-size:2.6rem;font-weight:800;color:var(--text);line-height:1;"><?= $value ?></div>
          <div class="subtitle" style="margin-top:8px;"><?= $label ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── CTA ───────────────────────────────────────── -->
<section class="sb-section" style="text-align:center;">
  <div class="sb-container">
    <p class="subtitle mb-2">Ready to begin?</p>
    <h2 style="font-size:2.4rem;color:var(--text);margin-bottom:16px;font-weight:800;">Let's Capture Your Story</h2>
    <div style="width:36px;height:3px;background:var(--accent);border-radius:2px;margin:0 auto 32px;"></div>
    <a href="<?= APP_URL ?>/book.php" class="btn-gold" style="font-size:15px;padding:16px 48px;">
      <i class="bi bi-calendar-plus"></i> Book Your Session Now
    </a>
  </div>
</section>

<style>
  [data-reveal] {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity .7s ease, transform .7s ease;
  }

  [data-reveal].revealed {
    opacity: 1;
    transform: translateY(0);
  }
</style>

<!-- ── Chat Widget ── -->
<div id="sb-chat-btn"
  onclick="sbChatToggle()"
  style="position:fixed;bottom:28px;right:28px;z-index:9999;
            width:56px;height:56px;border-radius:50%;
            background:var(--accent);color:#fff;
            display:flex;align-items:center;justify-content:center;
            cursor:pointer;box-shadow:0 4px 18px rgba(232,82,10,.45);
            transition:transform .2s,box-shadow .2s;">
  <i class="bi bi-chat-dots-fill" style="font-size:1.5rem;"></i>
  <span id="sb-chat-badge"
    style="display:none;position:absolute;top:-4px;right:-4px;
               background:#dc2626;color:#fff;border-radius:50%;
               width:18px;height:18px;font-size:10px;font-weight:700;
               align-items:center;justify-content:center;">0</span>
</div>

<div id="sb-chat-box"
  style="display:none;position:fixed;bottom:96px;right:28px;z-index:9998;
            width:340px;max-height:520px;
            background:var(--bg-card);border:1px solid var(--border);
            border-radius:var(--radius);box-shadow:0 8px 32px rgba(0,0,0,.12);
            flex-direction:column;overflow:hidden;">

  <!-- Header -->
  <div style="background:var(--accent);padding:14px 18px;display:flex;align-items:center;justify-content:space-between;">
    <div style="display:flex;align-items:center;gap:10px;">
      <div style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.2);
                  display:flex;align-items:center;justify-content:center;">
        <i class="bi bi-headset" style="color:#fff;font-size:1.1rem;"></i>
      </div>
      <div>
        <div style="color:#fff;font-weight:700;font-size:14px;">SOULBUD Support</div>
        <div style="color:rgba(255,255,255,.75);font-size:11px;">
          <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#4ade80;margin-right:4px;"></span>Online
        </div>
      </div>
    </div>
    <button onclick="sbChatToggle()"
      style="background:transparent;border:none;color:rgba(255,255,255,.8);font-size:1.2rem;cursor:pointer;line-height:1;">×</button>
  </div>

  <!-- Messages -->
  <div id="sb-chat-intro" style="padding:20px;display:flex;flex-direction:column;gap:10px;">
    <p style="font-size:14px;color:var(--text);margin:0;">Before we start, what's your name?</p>
    <input id="sb-chat-name" type="text" placeholder="Your full name..."
      style="padding:9px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);
                font-size:13px;font-family:'Inter',sans-serif;outline:none;background:var(--bg);">
    <button onclick="sbChatStart()"
      style="padding:9px 16px;background:var(--accent);color:#fff;border:none;
                 border-radius:var(--radius-sm);cursor:pointer;font-size:13px;font-weight:600;">
      Start Chat
    </button>
  </div>
  <div id="sb-chat-messages" style="display:none;flex:1;overflow-y:auto;padding:16px;
     flex-direction:column;gap:10px;min-height:260px;max-height:340px;"></div>

  <!-- Input -->
  <div style="padding:12px;border-top:1px solid var(--border);display:flex;gap-8px;gap:8px;">
    <input id="sb-chat-input" type="text" placeholder="Type a message..."
      style="flex:1;padding:9px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);
                  font-size:13px;font-family:'Inter',sans-serif;outline:none;background:var(--bg);"
      onkeydown="if(event.key==='Enter')sbChatSend()">
    <button onclick="sbChatSend()"
      style="padding:9px 16px;background:var(--accent);color:#fff;border:none;
                   border-radius:var(--radius-sm);cursor:pointer;font-size:13px;font-weight:600;
                   transition:background .2s;white-space:nowrap;"
      onmouseover="this.style.background='var(--accent-dark)'"
      onmouseout="this.style.background='var(--accent)'">
      <i class="bi bi-send"></i>
    </button>
  </div>
</div>

<script>
  (function() {
    // Generate or restore session ID
    let sid = localStorage.getItem('sb_chat_sid');
    if (!sid) {
      sid = 'c_' + Math.random().toString(36).slice(2) + Date.now().toString(36);
      localStorage.setItem('sb_chat_sid', sid);
    }

    let lastId = 0;
    let polling = null;
    let isOpen = false;
    // Add this function
    window.sbChatStart = function() {
      const name = document.getElementById('sb-chat-name').value.trim();
      if (!name) return;
      localStorage.setItem('sb_chat_name', name);
      document.getElementById('sb-chat-intro').style.display = 'none';
      document.getElementById('sb-chat-messages').style.display = 'flex';
      document.getElementById('sb-chat-input').disabled = false;

      // Send name as first message so admin can see it
      fetch('<?= APP_URL ?>/actions/chat.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=send&session_id=${encodeURIComponent(sid)}&sender=client&message=${encodeURIComponent('👤 ' + name)}&is_name=1`
      });

      startPolling();
      document.getElementById('sb-chat-input').focus();
    };

    // Update sbChatToggle to check if name already saved
    window.sbChatToggle = function() {
      isOpen = !isOpen;
      const box = document.getElementById('sb-chat-box');
      box.style.display = isOpen ? 'flex' : 'none';
      if (isOpen) {
        const savedName = localStorage.getItem('sb_chat_name');
        if (savedName) {
          // Already introduced, go straight to chat
          document.getElementById('sb-chat-intro').style.display = 'none';
          document.getElementById('sb-chat-messages').style.display = 'flex';
          startPolling();
        }
        clearBadge();
      } else {
        stopPolling();
      }
    };

    window.sbChatSend = function() {
      const input = document.getElementById('sb-chat-input');
      const msg = input.value.trim();
      if (!msg) return;
      input.value = '';

      fetch('<?= APP_URL ?>/actions/chat.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `action=send&session_id=${encodeURIComponent(sid)}&sender=client&message=${encodeURIComponent(msg)}`
        })
        .then(r => r.json())
        .then(data => {
          if (data.id) {
            lastId = data.id; // ✅ set BEFORE poll can see it
            appendMessage({
              sender: 'client',
              message: msg
            });
          }
        });
      // ❌ no manual appendMessage outside .then()
    };

    function startPolling() {
      poll();
      polling = setInterval(poll, 3000);
    }

    function stopPolling() {
      clearInterval(polling);
      polling = null;
    }

    function poll() {
      fetch(`<?= APP_URL ?>/actions/chat.php?action=poll&session_id=${encodeURIComponent(sid)}&since=${lastId}`)
        .then(r => r.json())
        .then(data => {
          if (data.messages && data.messages.length) {
            data.messages.forEach(m => {
              if (m.id > lastId) {
                lastId = m.id;
                appendMessage(m);
              }
            });
          }
        });
    }

    function appendMessage(m) {
      const wrap = document.getElementById('sb-chat-messages');
      const isClient = m.sender === 'client';
      const div = document.createElement('div');
      div.style.cssText = `display:flex;justify-content:${isClient ? 'flex-end' : 'flex-start'};`;
      div.innerHTML = `
      <div style="max-width:75%;padding:9px 13px;border-radius:${isClient ? '12px 12px 2px 12px' : '12px 12px 12px 2px'};
                  background:${isClient ? 'var(--accent)' : 'var(--bg)'};
                  color:${isClient ? '#fff' : 'var(--text)'};
                  font-size:13px;line-height:1.5;
                  border:${isClient ? 'none' : '1px solid var(--border)'};">
        ${escHtml(m.message)}
      </div>`;
      wrap.appendChild(div);
      wrap.scrollTop = wrap.scrollHeight;
    }

    function clearBadge() {
      const badge = document.getElementById('sb-chat-badge');
      badge.style.display = 'none';
    }

    function escHtml(s) {
      return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // Hover effect on button
    const btn = document.getElementById('sb-chat-btn');
    btn.addEventListener('mouseenter', () => btn.style.transform = 'scale(1.1)');
    btn.addEventListener('mouseleave', () => btn.style.transform = 'scale(1)');
  })();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>