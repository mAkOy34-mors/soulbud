<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Email Verification Page
// GET /verify.php?token=...
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/Booking.php';
require_once __DIR__ . '/includes/Mailer.php';

$token   = trim($_GET['token'] ?? '');
$result  = null;
$booking = null;
$error   = '';

if (!$token) {
    $error = 'Invalid or missing verification token.';
} else {
    $result = Booking::verifyToken($token);
    if (!$result) {
        $error = 'This verification link is invalid or has expired. Please submit a new booking.';
    } elseif ($result['status'] !== 'Waiting for Email Verification') {
        $error = 'This booking has already been verified.';
    } else {
        // Activate booking
        Booking::activateBooking($token, (int)$result['booking_id']);
        $booking = Booking::getByRef($result['booking_ref']);
        // Admin notification could go here
    }
}

$pageTitle   = 'Email Verification';
$currentPage = '';
require_once __DIR__ . '/includes/header.php';
?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:100px 24px 60px;">
  <div style="max-width:560px;width:100%;text-align:center;">

    <?php if ($error): ?>
    <!-- Error State -->
    <div style="font-size:4rem;margin-bottom:24px;">❌</div>
    <h1 style="color:var(--danger);font-size:2rem;margin-bottom:12px;">Verification Failed</h1>
    <div style="height:2px;width:48px;background:var(--danger);margin:0 auto 24px;"></div>
    <p style="color:var(--text-muted);line-height:1.8;margin-bottom:32px;"><?= htmlspecialchars($error) ?></p>
    <a href="<?= APP_URL ?>/book.php" class="btn-gold">
      <i class="bi bi-calendar-plus"></i> Book Again
    </a>

    <?php else: ?>
    <!-- Success State -->
    <div style="font-size:4rem;margin-bottom:24px;">✅</div>
    <h1 style="color:var(--gold);font-size:2.2rem;margin-bottom:12px;">Email Verified!</h1>
    <div class="gold-line" style="margin:0 auto 24px;"></div>
    <p style="color:var(--text-muted);line-height:1.8;margin-bottom:32px;">
      Your booking request has been submitted successfully. Our team will review it and send a confirmation email within <strong style="color:var(--text);">24 hours</strong>.
    </p>

    <!-- Booking summary card -->
    <div class="sb-card text-start mb-4">
      <div class="sb-card-header d-flex align-items-center gap-2">
        <i class="bi bi-receipt" style="color:var(--gold);"></i>
        <h4 style="margin:0;font-size:1rem;color:var(--text);">Booking Summary</h4>
      </div>
      <table style="width:100%;border-collapse:collapse;">
        <?php
        $rows = [
          ['Reference',    $booking['booking_ref']],
          ['Name',         $booking['full_name']],
          ['Event',        $booking['event_type']],
          ['Date',         date('F j, Y', strtotime($booking['preferred_date']))],
          ['Time',         date('g:i A', strtotime($booking['preferred_time']))],
          ['Location',     $booking['event_location']],
          ['Service',      $booking['service_type']],
          ['Status',       '<span class="badge-status badge-pending">Pending Review</span>'],
        ];
        foreach ($rows as [$label, $value]): ?>
        <tr>
          <td style="color:var(--text-dim);font-size:13px;padding:10px 0;width:35%;"><?= $label ?></td>
          <td style="color:var(--text);font-size:14px;padding:10px 0;"><?= $value ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <p style="color:var(--text-dim);font-size:12px;margin-bottom:28px;">
      A confirmation email was sent to <strong style="color:var(--gold);"><?= htmlspecialchars($booking['email']) ?></strong>
    </p>

    <div class="d-flex gap-3 justify-content-center flex-wrap">
      <a href="<?= APP_URL ?>/pages/status.php?ref=<?= urlencode($booking['booking_ref']) ?>" class="btn-gold">
        <i class="bi bi-search"></i> Track Booking
      </a>
      <a href="<?= APP_URL ?>" class="btn-outline">
        <i class="bi bi-house"></i> Home
      </a>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
