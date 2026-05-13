<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Setup Script
// Run ONCE to create the default admin account
// Access: http://localhost/soulbud/setup.php
// DELETE THIS FILE after setup is complete!
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$done    = false;
$error   = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password  = $_POST['password']       ?? '';
    $confirm   = $_POST['confirm']        ?? '';

    if (!$username || !$email || !$full_name || !$password) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        try {
            db()->prepare("
                INSERT INTO admins (username, email, password, full_name)
                VALUES (?,?,?,?)
                ON DUPLICATE KEY UPDATE password=?, full_name=?
            ")->execute([$username, $email, $hash, $full_name, $hash, $full_name]);
            $done    = true;
            $message = "Admin account <strong>{$username}</strong> created successfully!";
        } catch (Throwable $e) {
            $error = 'Failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SOULBUD.CO Setup</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/soulbud.css">
</head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
             background:radial-gradient(ellipse at 50% 60%,rgba(201,169,110,.06),transparent 70%);">
<div style="width:100%;max-width:480px;">

  <div class="text-center mb-5">
    <div style="font-family:'Cormorant Garamond',serif;font-size:2.4rem;color:var(--gold);letter-spacing:.1em;">
      SOULBUD.CO
    </div>
    <div class="subtitle mt-1">Initial Setup</div>
  </div>

  <div class="sb-card">
    <div class="sb-card-header">
      <h2 style="font-size:1.3rem;color:var(--text);margin:0;text-align:center;">
        Create Admin Account
      </h2>
    </div>

    <?php if ($error): ?>
    <div class="sb-alert sb-alert-danger mb-3">
      <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($done): ?>
    <div class="sb-alert sb-alert-success mb-3">
      <i class="bi bi-check-circle"></i> <?= $message ?>
    </div>
    <p style="color:var(--danger);font-size:13px;text-align:center;margin:16px 0;">
      ⚠️ <strong>Delete this file immediately!</strong><br>
      <code style="font-size:11px;">rm <?= __FILE__ ?></code>
    </p>
    <div class="text-center mt-3">
      <a href="<?= APP_URL ?>/admin/login.php" class="btn-gold">
        <i class="bi bi-box-arrow-in-right"></i> Go to Admin Login
      </a>
    </div>

    <?php else: ?>
    <form method="POST">
      <div class="sb-form-group">
        <label>Full Name <span class="required">*</span></label>
        <input type="text" name="full_name" class="sb-input" placeholder="SOULBUD Admin"
               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
      </div>
      <div class="sb-form-group">
        <label>Username <span class="required">*</span></label>
        <input type="text" name="username" class="sb-input" placeholder="admin"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
      </div>
      <div class="sb-form-group">
        <label>Email <span class="required">*</span></label>
        <input type="email" name="email" class="sb-input" placeholder="admin@soulbud.co"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="sb-form-group">
        <label>Password <span class="required">*</span></label>
        <input type="password" name="password" class="sb-input" placeholder="Min. 8 characters" required>
      </div>
      <div class="sb-form-group">
        <label>Confirm Password <span class="required">*</span></label>
        <input type="password" name="confirm" class="sb-input" placeholder="Repeat password" required>
      </div>
      <div class="sb-alert sb-alert-warning mb-3">
        <i class="bi bi-exclamation-triangle"></i>
        <span style="font-size:13px;">Delete <code>setup.php</code> after creating the admin account.</span>
      </div>
      <button type="submit" class="btn-gold" style="width:100%;justify-content:center;">
        <i class="bi bi-person-plus"></i> Create Admin Account
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
