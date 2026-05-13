<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Admin Header Partial
// ─────────────────────────────────────────
defined('SOULBUD') or die('Direct access denied.');
$adminUser   = adminUser();
$adminPage   = $adminPage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — <?= APP_NAME ?> Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/soulbud.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css">
  <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body class="admin-body">

<!-- ── Sidebar ──────────────────────────────────── -->
<aside class="sb-sidebar" id="adminSidebar">
  <div class="sb-sidebar-brand">
    <span class="brand-logo">SOULBUD.CO</span>
    <span class="brand-sub">Admin Panel</span>
  </div>

  <nav class="sb-sidebar-nav">
    <a href="<?= APP_URL ?>/admin/index.php"             class="<?= $adminPage==='dashboard' ?'active':'' ?>">
      <i class="bi bi-grid-1x2"></i> Dashboard
    </a>
    <a href="<?= APP_URL ?>/admin/pages/bookings.php"    class="<?= $adminPage==='bookings'  ?'active':'' ?>">
      <i class="bi bi-calendar-check"></i> Bookings
      <?php
      $pendingCount = (new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET, DB_USER, DB_PASS))->query("SELECT COUNT(*) FROM bookings WHERE status='Pending'")->fetchColumn();
      if ($pendingCount > 0): ?>
      <span class="sb-badge"><?= $pendingCount ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= APP_URL ?>/admin/pages/clients.php"     class="<?= $adminPage==='clients'   ?'active':'' ?>">
      <i class="bi bi-people"></i> Clients
    </a>
    <a href="<?= APP_URL ?>/admin/pages/payments.php"    class="<?= $adminPage==='payments'  ?'active':'' ?>">
      <i class="bi bi-credit-card"></i> Payments
    </a>
    <a href="<?= APP_URL ?>/admin/pages/calendar.php"    class="<?= $adminPage==='calendar'  ?'active':'' ?>">
      <i class="bi bi-calendar3"></i> Calendar
    </a>
    <a href="<?= APP_URL ?>/admin/pages/reports.php"     class="<?= $adminPage==='reports'   ?'active':'' ?>">
      <i class="bi bi-bar-chart"></i> Reports
    </a>
  </nav>

  <div class="sb-sidebar-footer">
    <div style="color:var(--text-muted);font-size:13px;margin-bottom:8px;">
      <i class="bi bi-person-circle me-1" style="color:var(--gold);"></i>
      <?= htmlspecialchars($adminUser['full_name'] ?? 'Admin') ?>
    </div>
    <a href="<?= APP_URL ?>/admin/logout.php" class="btn-outline btn-sm" style="width:100%;justify-content:center;">
      <i class="bi bi-box-arrow-right"></i> Sign Out
    </a>
  </div>
</aside>

<!-- ── Main content wrapper ─────────────────────── -->
<div class="sb-admin-content" id="adminContent">

  <!-- Top bar -->
  <div class="sb-topbar">
    <button class="sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('collapsed')">
      <i class="bi bi-list" style="font-size:1.4rem;color:var(--text-muted);"></i>
    </button>
    <div style="display:flex;align-items:center;gap:16px;">
      <span style="color:var(--text-dim);font-size:13px;"><?= date('F j, Y') ?></span>
      <a href="<?= APP_URL ?>" target="_blank" class="btn-outline btn-sm">
        <i class="bi bi-box-arrow-up-right"></i> View Site
      </a>
    </div>
  </div>
