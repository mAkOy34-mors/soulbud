<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Payment Upload Page
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Booking.php';

$pageTitle   = 'Submit Payment';
$currentPage = 'payment';

$booking = null;
$success = '';
$error   = '';

$preRef = strtoupper(trim($_GET['ref'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref    = strtoupper(trim($_POST['booking_ref'] ?? ''));
    $email  = strtolower(trim($_POST['email']       ?? ''));
    $method = $_POST['payment_method'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);

    $booking = Booking::getByRef($ref);

    if (!$booking || strtolower($booking['email']) !== $email) {
        $error = 'Booking not found. Please check your reference and email.';
        $booking = null;
    } elseif (!in_array($booking['status'], ['Approved','Confirmed'])) {
        $error = 'Payment can only be submitted for approved bookings.';
    } elseif (!in_array($method, ['GCash','Bank Transfer','Cash'])) {
        $error = 'Please select a valid payment method.';
    } elseif ($amount <= 0) {
        $error = 'Please enter a valid amount.';
    } else {
        $proofFilename = null;
        if (!empty($_FILES['proof_of_payment']['tmp_name'])) {
            $file    = $_FILES['proof_of_payment'];
            $maxSize = MAX_UPLOAD_MB * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                $error = 'File exceeds ' . MAX_UPLOAD_MB . 'MB limit.';
            } elseif (!in_array($file['type'], ALLOWED_IMG_TYPES)) {
                $error = 'Only JPG, PNG, or WEBP images are allowed.';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $proofFilename = $ref . '_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/payments/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (!move_uploaded_file($file['tmp_name'], $uploadDir . $proofFilename)) {
                    $error = 'Upload failed. Please try again.';
                    $proofFilename = null;
                }
            }
        }

        if (!$error) {
            try {
                db()->prepare("
                    INSERT INTO payments (booking_id, payment_method, amount, proof_filename)
                    VALUES (?,?,?,?)
                ")->execute([$booking['id'], $method, $amount, $proofFilename]);

                db()->prepare("UPDATE bookings SET status='Confirmed' WHERE id=?")
                     ->execute([$booking['id']]);

                $booking = Booking::getByRef($ref);
                $success = 'Payment submitted! You will receive a confirmation email once verified.';
            } catch (Throwable $e) {
                $error = 'Submission failed. Please try again.';
            }
        }
    }
} elseif ($preRef) {
    $booking = Booking::getByRef($preRef);
    if ($booking && !in_array($booking['status'], ['Approved','Confirmed'])) {
        $booking = null;
        $error = 'Payment upload is only available for approved bookings.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── Page Banner ── -->
<div class="sb-page-banner" style="background:linear-gradient(135deg,#fff8f5 0%,#fff 100%);border-bottom:1px solid var(--border);">
  <div class="sb-container">
    <p class="subtitle mb-2">Secure Your Slot</p>
    <h1 style="font-size:2.4rem;color:var(--text);font-weight:800;">Submit Payment</h1>
    <div style="width:40px;height:3px;background:var(--accent);border-radius:2px;margin:14px auto 0;"></div>
  </div>
</div>

<section class="sb-section" style="padding-top:48px;">
  <div class="sb-container" style="max-width:700px;">

    <?php if ($success): ?>
    <div class="sb-alert sb-alert-success mb-4">
      <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="sb-alert sb-alert-danger mb-4">
      <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- ── Payment methods info ── -->
    <div class="sb-card mb-4" style="border-top:4px solid var(--accent);">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <i class="bi bi-wallet2" style="color:var(--accent);font-size:1.2rem;"></i>
        <h4 style="color:var(--text);font-size:1.1rem;font-weight:700;margin:0;">Payment Methods</h4>
      </div>
      <div class="row g-3">
        <div class="col-md-4">
          <div style="padding:18px 14px;background:var(--bg);border-radius:var(--radius-sm);
                      border:1px solid var(--border);text-align:center;transition:border-color var(--transition);"
               onmouseover="this.style.borderColor='var(--accent)'"
               onmouseout="this.style.borderColor='var(--border)'">
            <i class="bi bi-phone" style="font-size:1.8rem;color:var(--accent);margin-bottom:10px;display:block;"></i>
            <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:4px;">GCash</div>
            <div style="font-size:12px;color:var(--text-muted);"><?= GCASH_NUMBER ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div style="padding:18px 14px;background:var(--bg);border-radius:var(--radius-sm);
                      border:1px solid var(--border);text-align:center;transition:border-color var(--transition);"
               onmouseover="this.style.borderColor='var(--accent)'"
               onmouseout="this.style.borderColor='var(--border)'">
            <i class="bi bi-bank" style="font-size:1.8rem;color:var(--accent);margin-bottom:10px;display:block;"></i>
            <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:4px;"><?= BANK_NAME ?></div>
            <div style="font-size:12px;color:var(--text-muted);"><?= BANK_ACCOUNT ?></div>
            <div style="font-size:11px;color:var(--text-dim);margin-top:2px;"><?= BANK_ACCT_NAME ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div style="padding:18px 14px;background:var(--bg);border-radius:var(--radius-sm);
                      border:1px solid var(--border);text-align:center;transition:border-color var(--transition);"
               onmouseover="this.style.borderColor='var(--accent)'"
               onmouseout="this.style.borderColor='var(--border)'">
            <i class="bi bi-cash" style="font-size:1.8rem;color:var(--accent);margin-bottom:10px;display:block;"></i>
            <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:4px;">Cash</div>
            <div style="font-size:12px;color:var(--text-muted);">On-site or Meet-up</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Upload form ── -->
    <div class="sb-card" style="border-top:4px solid var(--accent);">
      <div class="sb-card-header">
        <i class="bi bi-cloud-upload" style="color:var(--accent);font-size:1.2rem;"></i>
        <h4 style="color:var(--text);font-size:1.1rem;font-weight:700;margin:0;">Upload Proof of Payment</h4>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-6 sb-form-group">
            <label>Booking Reference <span class="required">*</span></label>
            <input type="text" name="booking_ref" class="sb-input" placeholder="SB-XXXXXXXX"
                   value="<?= htmlspecialchars($_POST['booking_ref'] ?? $preRef) ?>" required>
          </div>
          <div class="col-md-6 sb-form-group">
            <label>Email Address <span class="required">*</span></label>
            <input type="email" name="email" class="sb-input" placeholder="your@email.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 sb-form-group">
            <label>Payment Method <span class="required">*</span></label>
            <select name="payment_method" class="sb-select" required>
              <option value="">Select method</option>
              <option value="GCash"         <?= ($_POST['payment_method'] ?? '') === 'GCash'         ? 'selected' : '' ?>>GCash</option>
              <option value="Bank Transfer" <?= ($_POST['payment_method'] ?? '') === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
              <option value="Cash"          <?= ($_POST['payment_method'] ?? '') === 'Cash'          ? 'selected' : '' ?>>Cash</option>
            </select>
          </div>
          <div class="col-md-6 sb-form-group">
            <label>Amount Paid (₱) <span class="required">*</span></label>
            <input type="number" name="amount" class="sb-input" placeholder="0.00" min="1" step="0.01"
                   value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" required>
          </div>
          <div class="col-12 sb-form-group">
            <label>Screenshot / Proof of Payment <span class="required">*</span></label>
            <div id="upload-zone"
                 style="border:2px dashed var(--border);border-radius:var(--radius-sm);padding:36px 24px;
                        text-align:center;cursor:pointer;transition:all var(--transition);background:var(--bg);"
                 onclick="document.getElementById('proof_of_payment').click()"
                 onmouseover="this.style.borderColor='var(--accent)';this.style.background='var(--accent-subtle)'"
                 onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--bg)'">
              <i class="bi bi-cloud-arrow-up" style="font-size:2.4rem;color:var(--accent);display:block;margin-bottom:10px;"></i>
              <p style="color:var(--text-muted);font-size:13px;margin:0;font-weight:500;">Click to upload your screenshot</p>
              <p style="color:var(--text-dim);font-size:11px;margin:4px 0 0;">JPG, PNG, or WEBP · max 5MB</p>
              <input type="file" id="proof_of_payment" name="proof_of_payment"
                     accept=".jpg,.jpeg,.png,.webp" style="display:none;" required>
            </div>
            <!-- Preview -->
            <div id="proof-preview-wrap" class="d-none mt-3"
                 style="position:relative;display:inline-block;">
              <img id="proof-preview" src="" alt="Preview"
                   style="max-height:200px;border-radius:var(--radius-sm);border:1px solid var(--border);display:block;">
              <button type="button" onclick="clearPreview()"
                      style="position:absolute;top:-8px;right:-8px;width:22px;height:22px;border-radius:50%;
                             background:var(--danger);border:none;color:#fff;font-size:12px;cursor:pointer;
                             display:flex;align-items:center;justify-content:center;line-height:1;">×</button>
            </div>
          </div>
        </div>

        <div class="mt-4 d-flex align-items-center gap-3 flex-wrap"
             style="border-top:1px solid var(--border);padding-top:20px;">
          <button type="submit" class="btn-gold btn-lg" style="padding:13px 36px;">
            <i class="bi bi-send me-2"></i> Submit Payment
          </button>
          <a href="<?= APP_URL ?>/pages/status.php" class="btn-outline">
            <i class="bi bi-arrow-left"></i> Back to Status
          </a>
        </div>
      </form>
    </div>

  </div>
</section>

<style>
.sb-input:focus, .sb-select:focus {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px rgba(232,82,10,.12) !important;
}
#upload-zone:active { transform: scale(.995); }
</style>

<script>
document.getElementById('proof_of_payment').addEventListener('change', function () {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('proof-preview').src = e.target.result;
    document.getElementById('proof-preview-wrap').classList.remove('d-none');
    document.getElementById('proof-preview-wrap').style.display = 'inline-block';
  };
  reader.readAsDataURL(file);
});

function clearPreview() {
  document.getElementById('proof_of_payment').value = '';
  document.getElementById('proof-preview').src = '';
  document.getElementById('proof-preview-wrap').classList.add('d-none');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>