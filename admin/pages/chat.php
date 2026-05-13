<?php
define('SOULBUD', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

session_start();
requireAdmin();

$pageTitle = 'Live Chat';
$adminPage = 'chat';

require_once __DIR__ . '/../includes/header.php';
?>

<main class="sb-admin-main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 style="font-size:1.8rem;color:var(--gold);margin:0;">Live Chat</h1>
            <p style="color:var(--text-dim);font-size:13px;margin:4px 0 0;">Real-time client messages</p>
        </div>
        <span id="admin-unread-badge" style="display:none;background:var(--danger);color:#fff;
          padding:4px 12px;border-radius:99px;font-size:12px;font-weight:700;">0 unread</span>
    </div>

    <div class="row g-4" style="height:600px;">

        <!-- Sessions list -->
        <div class="col-md-4">
            <div class="sb-card h-100" style="overflow-y:auto;padding:0;">
                <div style="padding:14px 16px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600;color:var(--text);">
                    Conversations
                </div>
                <div id="admin-sessions"></div>
            </div>
        </div>

        <!-- Chat window -->
        <div class="col-md-8">
            <div class="sb-card h-100" style="display:flex;flex-direction:column;padding:0;">
                <div id="admin-chat-header"
                    style="padding:14px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600;color:var(--text-dim);">
                    Select a conversation
                </div>
                <div id="admin-chat-messages"
                    style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;">
                </div>
                <div style="padding:12px;border-top:1px solid var(--border);display:flex;gap:8px;">
                    <input id="admin-chat-input" type="text" placeholder="Type a reply..."
                        style="flex:1;padding:9px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);
                        font-size:13px;font-family:'Inter',sans-serif;outline:none;background:var(--bg);"
                        onkeydown="if(event.key==='Enter')adminSend()">
                    <button onclick="adminSend()"
                        style="padding:9px 18px;background:var(--accent);color:#fff;border:none;
                         border-radius:var(--radius-sm);cursor:pointer;font-size:13px;font-weight:600;">
                        <i class="bi bi-send"></i> Send
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/soulbud.js"></script>
<script>
    (function() {
        const API = '<?= APP_URL ?>/actions/chat.php';
        let activeSid = null;
        let lastId = 0;
        let isPolling = false; // add this at the top with your other variables

        function escHtml(s) {
            return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        // Load sessions
        function loadSessions() {
            fetch(`${API}?action=sessions`)
                .then(r => r.json())
                .then(data => {
                    const el = document.getElementById('admin-sessions');
                    el.innerHTML = '';
                    (data.sessions || []).forEach(s => {
                        const div = document.createElement('div');
                        div.style.cssText = `padding:12px 16px;cursor:pointer;border-bottom:1px solid var(--border);
                               background:${s.session_id === activeSid ? 'var(--accent-subtle)' : ''};
                               transition:background .15s;`;
                        div.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span style="font-size:12px;font-weight:600;color:var(--text);font-family:monospace;">
                ${escHtml(s.session_id.slice(0,16))}…
              </span>
              ${s.unread > 0 ? `<span style="background:var(--danger);color:#fff;border-radius:99px;padding:2px 7px;font-size:10px;font-weight:700;">${s.unread}</span>` : ''}
            </div>
            <div style="font-size:12px;color:var(--text-dim);margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              ${escHtml((s.last_msg || '').slice(0, 50))}
            </div>`;
                        div.addEventListener('mouseenter', () => {
                            if (s.session_id !== activeSid) div.style.background = 'var(--bg)';
                        });
                        div.addEventListener('mouseleave', () => {
                            if (s.session_id !== activeSid) div.style.background = '';
                        });
                        div.addEventListener('click', () => openSession(s.session_id));
                        el.appendChild(div);
                    });
                });
        }

        function openSession(sid) {
            activeSid = sid;
            lastId = 0;
            document.getElementById('admin-chat-header').textContent = 'Session: ' + sid;
            document.getElementById('admin-chat-messages').innerHTML = '';
            pollMessages();
            loadSessions();
        }


        function pollMessages() {
            if (!activeSid || isPolling) return; // 🔒 prevent overlapping calls
            isPolling = true;

            fetch(`${API}?action=poll&session_id=${encodeURIComponent(activeSid)}&since=${lastId}&viewer=admin`)
                .then(r => r.json())
                .then(data => {
                    (data.messages || []).forEach(m => {
                        if (m.id > lastId) {
                            lastId = m.id;
                            appendMessage(m);
                        }
                    });
                })
                .finally(() => {
                    isPolling = false; // 🔓 release lock when done
                });
        }

        function appendMessage(m) {
            const wrap = document.getElementById('admin-chat-messages');
            const isAdmin = m.sender === 'admin';
            const div = document.createElement('div');
            div.style.cssText = `display:flex;justify-content:${isAdmin ? 'flex-end' : 'flex-start'};`;
            div.innerHTML = `
      <div style="max-width:75%;padding:9px 13px;
                  border-radius:${isAdmin ? '12px 12px 2px 12px' : '12px 12px 12px 2px'};
                  background:${isAdmin ? 'var(--accent)' : 'var(--bg)'};
                  color:${isAdmin ? '#fff' : 'var(--text)'};
                  font-size:13px;line-height:1.5;
                  border:${isAdmin ? 'none' : '1px solid var(--border)'};">
        ${escHtml(m.message)}
      </div>`;
            wrap.appendChild(div);
            wrap.scrollTop = wrap.scrollHeight;
        }

        window.adminSend = function() {
             alert('adminSend fired');
            if (!activeSid) return;
            const input = document.getElementById('admin-chat-input');
            const msg = input.value.trim();
            if (!msg) return;
            input.value = '';

            fetch(API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=send&session_id=${encodeURIComponent(activeSid)}&sender=admin&message=${encodeURIComponent(msg)}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.id) {
                        lastId = data.id; // ✅ update lastId FIRST
                        appendMessage({
                            sender: 'admin',
                            message: msg
                        }); // ✅ show it manually
                        // ❌ NO pollMessages() here — interval will handle future messages
                    }
                });
        };

        // Poll every 3 seconds
        setInterval(() => {
            pollMessages();
            loadSessions();
        }, 3000);

        loadSessions();
    })();
</script>
</body>

</html>