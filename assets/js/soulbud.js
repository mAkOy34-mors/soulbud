/* ──────────────────────────────────────────────────
   SOULBUD.CO — Main JavaScript
────────────────────────────────────────────────── */

'use strict';

// ── Navbar scroll effect ───────────────────────────
const navbar = document.querySelector('.sb-navbar');
if (navbar) {
  window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 50);
  });
}

// ── Toast Notification ─────────────────────────────
function showToast(message, type = 'info', duration = 4000) {
  const toast = document.createElement('div');
  toast.className = `sb-toast ${type}`;
  const icons = { success: '✓', error: '✕', info: '◆' };
  toast.innerHTML = `<span style="font-size:16px;">${icons[type] || '◆'}</span> <span>${message}</span>`;
  document.body.appendChild(toast);
  requestAnimationFrame(() => {
    requestAnimationFrame(() => toast.classList.add('show'));
  });
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 400);
  }, duration);
}

// ── Booking Form Handler ───────────────────────────
const bookingForm = document.getElementById('bookingForm');
if (bookingForm) {
  const btn = bookingForm.querySelector('[type="submit"]');
  const spinner = bookingForm.querySelector('.sb-spinner');

  bookingForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    clearErrors(bookingForm);

    btn.disabled = true;
    if (spinner) spinner.style.display = 'block';

    const formData = new FormData(bookingForm);

    try {
      const res = await fetch('actions/submit_booking.php', {
        method: 'POST',
        body: formData,
      });
      const data = await res.json();

      if (data.success) {
        bookingForm.classList.add('d-none');
        document.getElementById('successMessage')?.classList.remove('d-none');
        document.getElementById('booking-ref-display').textContent = data.booking_ref || '';
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        if (data.errors && Array.isArray(data.errors)) {
          data.errors.forEach(err => showToast(err, 'error'));
        } else {
          showToast(data.message || 'Submission failed. Please try again.', 'error');
        }
      }
    } catch (err) {
      showToast('Network error. Please try again.', 'error');
    } finally {
      btn.disabled = false;
      if (spinner) spinner.style.display = 'none';
    }
  });
}

// ── Clear form validation errors ───────────────────
function clearErrors(form) {
  form.querySelectorAll('.sb-error-msg').forEach(el => el.remove());
  form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
}

// ── Calendar (FullCalendar integration) ────────────
function initCalendar(bookedDates, blockedDates) {
  const el = document.getElementById('sb-calendar');
  if (!el || typeof FullCalendar === 'undefined') return;

  const events = [];

  Object.entries(bookedDates).forEach(([date, statuses]) => {
    const allBooked = statuses.every(s => ['Approved','Confirmed','Completed'].includes(s));
    events.push({
      start: date,
      allDay: true,
      display: 'background',
      color: allBooked ? '#e5555530' : '#e8a03025',
      title: allBooked ? 'Fully Booked' : 'Pending',
      classNames: allBooked ? ['fc-booked'] : ['fc-pending'],
    });
  });

  blockedDates.forEach(date => {
    events.push({
      start: date, allDay: true, display: 'background',
      color: '#55555540', title: 'Unavailable',
      classNames: ['fc-blocked'],
    });
  });

  new FullCalendar.Calendar(el, {
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev',
      center: 'title',
      right: 'next',
    },
    events,
    eventClick: () => {},
    dateClick: function (info) {
      const dateInput = document.getElementById('preferred_date');
      if (dateInput) {
        dateInput.value = info.dateStr;
        showToast(`Date selected: ${info.dateStr}`, 'info', 2000);
      }
    },
    validRange: { start: new Date().toISOString().split('T')[0] },
    dayCellClassNames: function (arg) {
      const ds = arg.date.toISOString().split('T')[0];
      if (blockedDates.includes(ds)) return ['fc-day-blocked'];
      if (bookedDates[ds]) return ['fc-day-booked'];
      return ['fc-day-available'];
    },
    height: 'auto',
    themeSystem: 'standard',
  }).render();
}

// ── Payment upload preview ─────────────────────────
const proofInput = document.getElementById('proof_of_payment');
if (proofInput) {
  proofInput.addEventListener('change', function () {
    const preview = document.getElementById('proof-preview');
    const file = this.files[0];
    if (!file || !preview) return;
    if (file.size > 5 * 1024 * 1024) {
      showToast('File must be under 5MB.', 'error');
      this.value = '';
      return;
    }
    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      preview.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
  });
}

// ── Confirm dialogs for admin actions ──────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', function (e) {
    if (!confirm(this.dataset.confirm)) e.preventDefault();
  });
});

// ── Auto-dismiss alerts after 5s ──────────────────
document.querySelectorAll('.sb-alert[data-auto-dismiss]').forEach(alert => {
  setTimeout(() => {
    alert.style.opacity = '0';
    alert.style.transition = '.4s';
    setTimeout(() => alert.remove(), 400);
  }, 5000);
});

// ── Animate elements on scroll ────────────────────
function initScrollReveal() {
  const els = document.querySelectorAll('[data-reveal]');
  if (!els.length) return;
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('revealed');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });
  els.forEach(el => observer.observe(el));
}

document.addEventListener('DOMContentLoaded', () => {
  initScrollReveal();

  // Set min date for booking form
  const dateInput = document.getElementById('preferred_date');
  if (dateInput) {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    dateInput.min = tomorrow.toISOString().split('T')[0];
  }
});
