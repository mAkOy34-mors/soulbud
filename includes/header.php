<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Global Header Partial
// ─────────────────────────────────────────
defined('SOULBUD') or die('Direct access denied.');
$currentPage = $currentPage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="SOULBUD.CO — Professional Photography & Videography booking platform.">
  <title><?= htmlspecialchars($pageTitle ?? 'SOULBUD.CO') ?> | Photography &amp; Videography</title>

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg">

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <!-- FullCalendar (optional) -->
  <?php if (!empty($useCalendar)): ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
  <?php endif; ?>

  <!-- SOULBUD Custom CSS -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/soulbud.css">

  <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body style="padding-top:64px;">

<!-- ═══════════════════════════════════════════════
     NAVBAR — pure white, logo left, nav links right
═══════════════════════════════════════════════ -->
<nav class="sb-navbar" id="sbNavbar" style="top:0;">

  <!-- Logo -->
<a href="<?= APP_URL ?>" class="sb-logo" style="text-decoration:none;">
  <img src="<?= APP_URL ?>/assets/images/soulbud.jpg"
       alt="SOULBUD.CO logo" class="sb-logo-img"
       style="height:40px; width:40px; border-radius:8px; object-fit:cover; flex-shrink:0;">
  <div class="sb-logo-text" style="display:flex; flex-direction:column; justify-content:center; line-height:1;">
   <span class="logo-name">SOULBUD.CO</span>
    <span class="logo-tagline" style="font-size:9px; letter-spacing:.14em; color:#9ca3af; font-weight:500; text-transform:uppercase; margin-top:4px; white-space:nowrap;">
      Photography &amp; Videography
    </span>
  </div>
</a>

  <!-- Desktop nav links only (no CTA button) -->
  <div class="sb-navbar-right">
    <ul class="sb-nav-links" style="margin-right:0;">
      <li><a href="<?= APP_URL ?>/"
             class="<?= $currentPage === 'home'     ? 'active' : '' ?>">Home</a></li>
      <li><a href="<?= APP_URL ?>/book.php"
             class="<?= $currentPage === 'book'     ? 'active' : '' ?>">Book Now</a></li>
      <li><a href="<?= APP_URL ?>/pages/status.php"
             class="<?= $currentPage === 'status'   ? 'active' : '' ?>">Check Status</a></li>
      <li><a href="<?= APP_URL ?>/pages/calendar.php"
             class="<?= $currentPage === 'calendar' ? 'active' : '' ?>">Calendar</a></li>
      <li><a href="<?= APP_URL ?>/pages/payment.php"
             class="<?= $currentPage === 'payment'  ? 'active' : '' ?>">Payment</a></li>
    </ul>
  </div>

  <!-- Mobile hamburger -->
  <button class="sb-mobile-toggle d-md-none"
          aria-label="Toggle menu"
          onclick="document.getElementById('sbMobileMenu').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
</nav>

<!-- Mobile menu drawer -->
<div id="sbMobileMenu" class="sb-mobile-menu" style="top:64px;">
  <a href="<?= APP_URL ?>/"                   class="<?= $currentPage==='home'     ? 'active':'' ?>">Home</a>
  <a href="<?= APP_URL ?>/book.php"           class="<?= $currentPage==='book'     ? 'active':'' ?>">Book Now</a>
  <a href="<?= APP_URL ?>/pages/status.php"   class="<?= $currentPage==='status'   ? 'active':'' ?>">Check Status</a>
  <a href="<?= APP_URL ?>/pages/calendar.php" class="<?= $currentPage==='calendar' ? 'active':'' ?>">Calendar</a>
  <a href="<?= APP_URL ?>/pages/payment.php"  class="<?= $currentPage==='payment'  ? 'active':'' ?>">Payment</a>
</div>