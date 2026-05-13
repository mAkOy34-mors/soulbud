<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Admin Login
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

session_start();
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . APP_URL . '/admin/index.php');
    exit;
}

$error = '';
$timeout = !empty($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = db()->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin']          = $admin;
            $_SESSION['last_activity']  = time();
            header('Location: ' . APP_URL . '/admin/index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/soulbud.css">
</head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
             background:radial-gradient(ellipse at 30% 70%, rgba(201,169,110,.05) 0%, transparent 60%);">

<div style="width:100%;max-width:420px;">
  <div class="text-center mb-5">
    <div style="font-family:'Cormorant Garamond',serif;font-size:2.2rem;color:var(--gold);letter-spacing:.1em;">SOULBUD.CO</div>
    <div class="subtitle mt-1">Admin Panel</div>
  </div>

  <div class="sb-card">
    <div class="sb-card-header">
      <h2 style="font-size:1.3rem;color:var(--text);margin:0;text-align:center;">Sign In</h2>
    </div>

    <?php if ($timeout): ?>
    <div class="sb-alert sb-alert-warning mb-3">
      <i class="bi bi-clock"></i> Your session has expired. Please sign in again.
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="sb-alert sb-alert-danger mb-3">
      <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="sb-form-group">
        <label>Username or Email</label>
        <div style="position:relative;">
          <i class="bi bi-person" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-dim);"></i>
          <input type="text" name="username" class="sb-input" style="padding-left:38px;"
                 placeholder="admin" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
        </div>
      </div>
      <div class="sb-form-group">
        <label>Password</label>
        <div style="position:relative;">
          <i class="bi bi-lock" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-dim);"></i>
          <input type="password" name="password" id="passwordInput" class="sb-input" style="padding-left:38px;padding-right:42px;"
                 placeholder="••••••••" required>
          <button type="button" onclick="togglePassword()" style="position:absolute;right:13px;top:50%;transform:translateY(-50%);
                  background:none;border:none;color:var(--text-dim);cursor:pointer;padding:0;">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn-gold" style="width:100%;justify-content:center;margin-top:8px;">
        <i class="bi bi-box-arrow-in-right"></i> Sign In
      </button>
    </form>
  </div>

  <p class="text-center mt-4" style="color:var(--text-dim);font-size:12px;">
    <a href="<?= APP_URL ?>" style="color:var(--text-muted);text-decoration:none;">
      <i class="bi bi-arrow-left me-1"></i> Back to SOULBUD.CO
    </a>
  </p>
</div>

<script>
function togglePassword() {
  const input = document.getElementById('passwordInput');
  const icon  = document.getElementById('eyeIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'bi bi-eye';
  }
}
</script>
</body>
</html>
