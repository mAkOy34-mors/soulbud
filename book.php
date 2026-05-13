<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Booking Form Page
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/Booking.php';

$pageTitle   = 'Book an Appointment';
$currentPage = 'book';
$useCalendar = true;

// Fetch booked/blocked dates for calendar
$calData = Booking::getBookedDates();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Page Banner (BookedIn-style inner page header) ── -->
<div class="sb-page-banner" style="background:linear-gradient(135deg,#fff8f5 0%,#fff 100%);border-bottom:1px solid var(--border);">
  <div class="sb-container">
    <p class="subtitle mb-2">Schedule a Session</p>
    <h1 style="font-size:2.4rem;color:var(--text);font-weight:800;">Book an Appointment</h1>
    <div style="width:40px;height:3px;background:var(--accent);border-radius:2px;margin:14px auto 0;"></div>
  </div>
</div>

<div class="sb-section" style="padding-top:48px;">
  <div class="sb-container">

    <!-- Success message (hidden initially) -->
    <div id="successMessage" class="d-none" style="max-width:620px;margin:0 auto 40px;">
      <div class="sb-card text-center" style="padding:52px 36px;border-top:4px solid var(--accent);">
        <div style="width:72px;height:72px;background:var(--accent-subtle);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
          <i class="bi bi-envelope-check" style="font-size:2rem;color:var(--accent);"></i>
        </div>
        <h2 style="color:var(--text);font-weight:800;margin-bottom:12px;">Check Your Email!</h2>
        <div style="width:36px;height:3px;background:var(--accent);border-radius:2px;margin:0 auto 20px;"></div>
        <p style="color:var(--text-muted);line-height:1.8;">A verification email has been sent. Please click the link to confirm your booking request.</p>
        <p style="color:var(--text-dim);font-size:13px;margin-top:12px;">
          Booking Reference: <strong id="booking-ref-display" style="color:var(--accent);"></strong>
        </p>
        <a href="<?= APP_URL ?>" class="btn-gold mt-4 d-inline-flex">
          <i class="bi bi-house"></i> Back to Home
        </a>
      </div>
    </div>

    <div class="row g-5" id="bookingForm-wrapper">

      <!-- ── Calendar sidebar ── -->
      <div class="col-lg-5 order-lg-2">
        <div class="sb-card" style="position:sticky;top:110px;border-top:4px solid var(--accent);">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
            <i class="bi bi-calendar3" style="color:var(--accent);font-size:1.2rem;"></i>
            <h4 style="color:var(--text);font-size:1.1rem;font-weight:700;margin:0;">Schedule Availability</h4>
          </div>
          <p style="color:var(--text-dim);font-size:12px;margin-bottom:20px;">Click a date to select it.</p>
          <div id="sb-calendar"></div>
          <div class="d-flex gap-3 mt-3 flex-wrap">
            <span style="font-size:11px;letter-spacing:.05em;color:var(--text-muted);">
              <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:rgba(232,82,10,.2);margin-right:5px;"></span> Available
            </span>
            <span style="font-size:11px;letter-spacing:.05em;color:var(--text-muted);">
              <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:rgba(217,119,6,.25);margin-right:5px;"></span> Pending
            </span>
            <span style="font-size:11px;letter-spacing:.05em;color:var(--text-muted);">
              <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:rgba(220,38,38,.25);margin-right:5px;"></span> Booked
            </span>
          </div>
        </div>
      </div>

      <!-- ── Booking Form ── -->
      <div class="col-lg-7 order-lg-1">
        <form id="bookingForm" novalidate>

          <!-- Personal Info card -->
          <div class="sb-card" style="border-top:4px solid var(--accent);">
            <div class="sb-card-header">
              <i class="bi bi-person-circle" style="color:var(--accent);font-size:1.2rem;"></i>
              <h3 style="color:var(--text);font-size:1.1rem;font-weight:700;margin:0;">Personal Information</h3>
            </div>
            <div class="row g-3">
              <div class="col-md-6 sb-form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" id="full_name" class="sb-input" placeholder="Juan dela Cruz" required>
              </div>
              <div class="col-md-6 sb-form-group">
                <label>Email Address <span class="required">*</span></label>
                <input type="email" name="email" id="email" class="sb-input" placeholder="juan@gmail.com" required>
              </div>
              <div class="col-12 sb-form-group">
                <label>Contact Number <span class="required">*</span></label>
                <input type="tel" name="contact_number" id="contact_number" class="sb-input" placeholder="09XX-XXX-XXXX" required>
              </div>
            </div>
          </div>

          <!-- Event Details card -->
          <div class="sb-card mt-4" style="border-top:4px solid var(--accent);">
            <div class="sb-card-header">
              <i class="bi bi-calendar-event" style="color:var(--accent);font-size:1.2rem;"></i>
              <h3 style="color:var(--text);font-size:1.1rem;font-weight:700;margin:0;">Event Details</h3>
            </div>
            <div class="row g-3">
              <div class="col-md-6 sb-form-group">
                <label>Event Type <span class="required">*</span></label>
                <select name="event_type" id="event_type" class="sb-select" required>
                  <option value="">Select event type</option>
                  <option>Wedding</option>
                  <option>Debut</option>
                  <option>Birthday</option>
                  <option>Corporate Event</option>
                  <option>Baptismal</option>
                  <option>Graduation</option>
                  <option>Pre-Nuptial</option>
                  <option>Maternity</option>
                  <option>Product Shoot</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="col-md-6 sb-form-group">
                <label>Service Type <span class="required">*</span></label>
                <select name="service_type" id="service_type" class="sb-select" required>
                  <option value="">Select service</option>
                  <option value="Photography">Photography</option>
                  <option value="Videography">Videography</option>
                  <option value="Both">Photography &amp; Videography</option>
                </select>
              </div>
              <div class="col-12 sb-form-group">
                <label>Event Location <span class="required">*</span></label>
                <div style="position:relative;">
                  <input type="text" name="event_location" id="event_location" class="sb-input"
                    placeholder="Venue name and address" required autocomplete="off">
                  <div id="location-suggestions"
                    style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;
                border:1px solid var(--border);border-top:none;border-radius:0 0 var(--radius-sm) var(--radius-sm);
                z-index:999;max-height:220px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.08);">
                  </div>
                </div>
                <div id="location-status" style="font-size:11px;color:var(--text-dim);margin-top:4px;display:none;">
                  <i class="bi bi-search"></i> Searching...
                </div>
              </div>
              <div class="col-md-6 sb-form-group">
                <label>Preferred Date <span class="required">*</span></label>
                <input type="date" name="preferred_date" id="preferred_date" class="sb-input" required>
              </div>
              <div class="col-md-6 sb-form-group">
                <label>Preferred Time <span class="required">*</span></label>
                <input type="time" name="preferred_time" id="preferred_time" class="sb-input" required>
              </div>
              <div class="col-12 sb-form-group">
                <label>Additional Notes</label>
                <textarea name="additional_notes" class="sb-textarea" placeholder="Any special requests, venue details, or things we should know..."></textarea>
              </div>
            </div>
          </div>

          <div class="mt-4 d-flex align-items-center gap-3 flex-wrap">
            <button type="submit" class="btn-gold btn-lg" style="padding:14px 40px;">
              <span class="sb-spinner me-2" style="display:none;"></span>
              <i class="bi bi-send me-2"></i> Submit Booking Request
            </button>
            <span style="color:var(--text-dim);font-size:12px;">A verification email will be sent to you.</span>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>

<?php
$bookedJson  = json_encode($calData['booked']);
$blockedJson = json_encode($calData['blocked']);
$extraScripts = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
  const booked  = {$bookedJson};
  const blocked = {$blockedJson};
  initCalendar(booked, blocked);
});
</script>
JS;
?>

<style>
  /* FullCalendar overrides — accent orange */
  .fc {
    background: transparent !important;
  }

  .fc-toolbar-title {
    font-family: 'Inter', sans-serif;
    color: var(--text) !important;
    font-size: 1.1rem !important;
    font-weight: 700 !important;
  }

  .fc-button {
    background: var(--bg) !important;
    border: 1px solid var(--border) !important;
    color: var(--text-muted) !important;
    border-radius: 6px !important;
  }

  .fc-button:hover {
    border-color: var(--accent) !important;
    color: var(--accent) !important;
  }

  .fc-daygrid-day-number {
    color: var(--text-muted);
    font-size: 12px;
  }

  .fc-day-today {
    background: rgba(232, 82, 10, .06) !important;
  }

  .fc-day-today .fc-daygrid-day-number {
    color: var(--accent) !important;
    font-weight: 700;
  }

  .fc-col-header-cell-cushion {
    color: var(--text-dim);
    font-size: 11px;
    letter-spacing: .08em;
    text-transform: uppercase;
  }

  /* Focus ring on form inputs — orange */
  .sb-input:focus,
  .sb-select:focus,
  .sb-textarea:focus {
    border-color: var(--accent) !important;
    box-shadow: 0 0 0 3px rgba(232, 82, 10, .12) !important;
  }
</style>

<script>
  (function() {
    const input = document.getElementById('event_location');
    const dropdown = document.getElementById('location-suggestions');
    const status = document.getElementById('location-status');
    let debounceTimer;

    input.addEventListener('input', function() {
      const query = this.value.trim();
      clearTimeout(debounceTimer);

      if (query.length < 3) {
        dropdown.style.display = 'none';
        status.style.display = 'none';
        return;
      }

      status.style.display = 'block';
      status.innerHTML = '<i class="bi bi-search"></i> Searching...';

      debounceTimer = setTimeout(() => {
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=6&addressdetails=1`)
          .then(r => r.json())
          .then(results => {
            status.style.display = 'none';
            dropdown.innerHTML = '';

            if (!results.length) {
              dropdown.style.display = 'none';
              return;
            }

            results.forEach(place => {
              const item = document.createElement('div');
              item.style.cssText = 'padding:10px 14px;cursor:pointer;font-size:13px;color:var(--text);border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:8px;transition:background .15s;';
              item.innerHTML = `
              <i class="bi bi-geo-alt" style="color:var(--accent);margin-top:2px;flex-shrink:0;"></i>
              <span>${place.display_name}</span>
            `;
              item.addEventListener('mouseenter', () => item.style.background = 'var(--accent-subtle)');
              item.addEventListener('mouseleave', () => item.style.background = '');
              item.addEventListener('mousedown', () => {
                input.value = place.display_name;
                dropdown.style.display = 'none';
              });
              dropdown.appendChild(item);
            });

            dropdown.style.display = 'block';
          })
          .catch(() => {
            status.style.display = 'none';
            dropdown.style.display = 'none';
          });
      }, 400); // 400ms debounce
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
      if (!input.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
      }
    });

    // Hide on Escape
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') dropdown.style.display = 'none';
    });
  })();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>