# SOULBUD.CO — Photography & Videography Booking System

A complete booking management system built with **PHP 8+**, **Bootstrap 5**, **MySQL**, **CSS3**, and **JavaScript**.

---

## 📁 Project Structure

```
soulbud/
├── admin/                      # Admin panel
│   ├── includes/
│   │   ├── auth.php            # Session auth guard
│   │   ├── header.php          # Admin layout header
│   │   └── footer.php          # Admin layout footer
│   ├── pages/
│   │   ├── bookings.php        # Booking management (list + filter)
│   │   ├── booking_detail.php  # Individual booking + actions
│   │   ├── clients.php         # Client management
│   │   ├── payments.php        # Payment management
│   │   ├── calendar.php        # Calendar + date blocking
│   │   └── reports.php         # Analytics + CSV export
│   ├── index.php               # Dashboard
│   ├── login.php               # Admin login
│   └── logout.php              # Session destroy
│
├── actions/
│   └── submit_booking.php      # POST: booking form handler (AJAX)
│
├── assets/
│   ├── css/
│   │   ├── soulbud.css         # Global styles (dark luxury theme)
│   │   └── admin.css           # Admin panel styles
│   ├── js/
│   │   └── soulbud.js          # Main JS (forms, toast, calendar init)
│   └── images/                 # Logo, favicon (add your own)
│
├── config/
│   ├── config.php              # App constants (DB, SMTP, payments)
│   └── database.php            # PDO singleton
│
├── database/
│   └── soulbud.sql             # Full schema (tables + views)
│
├── includes/
│   ├── Booking.php             # Booking model + helpers
│   ├── Mailer.php              # PHPMailer email wrapper
│   ├── header.php              # Public layout header
│   └── footer.php              # Public layout footer
│
├── pages/
│   ├── status.php              # Check booking status (public)
│   ├── payment.php             # Upload proof of payment (public)
│   └── calendar.php            # Public availability calendar
│
├── uploads/
│   └── payments/               # Payment proof images (auto-created)
│
├── book.php                    # Booking form page
├── index.php                   # Homepage
├── verify.php                  # Email verification handler
├── setup.php                   # One-time admin account creation
├── composer.json               # PHPMailer dependency
└── .htaccess                   # Apache security + performance
```

---

## ⚡ Installation

### 1. Requirements
- PHP 8.0+
- MySQL 8.0+
- Apache with `mod_rewrite`
- Composer

### 2. Clone / Upload
Place the `soulbud/` folder in your web server root (e.g., `htdocs/` or `www/`).

### 3. Install PHPMailer
```bash
cd soulbud
composer install
```

### 4. Create the Database
```bash
mysql -u root -p < database/soulbud.sql
```

### 5. Configure the App
Edit `config/config.php`:
```php
define('DB_HOST',   'localhost');
define('DB_NAME',   'soulbud_db');
define('DB_USER',   'root');
define('DB_PASS',   'your_password');
define('APP_URL',   'http://localhost/soulbud');

// SMTP (Gmail App Password recommended)
define('SMTP_USER', 'youremail@gmail.com');
define('SMTP_PASS', 'your_app_password');

// Payment info
define('GCASH_NUMBER', '09XX-XXX-XXXX');
define('BANK_ACCOUNT', '0000-0000-0000');
```

### 6. Create Admin Account
Visit: `http://localhost/soulbud/setup.php`  
Fill in your admin details, then **delete `setup.php`** immediately.

### 7. Set Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/payments/
```

---

## 🔑 Default URLs

| Page              | URL                                      |
|-------------------|------------------------------------------|
| Homepage          | `/soulbud/`                              |
| Book Now          | `/soulbud/book.php`                      |
| Check Status      | `/soulbud/pages/status.php`              |
| Public Calendar   | `/soulbud/pages/calendar.php`            |
| Payment Upload    | `/soulbud/pages/payment.php`             |
| Admin Dashboard   | `/soulbud/admin/`                        |
| Admin Login       | `/soulbud/admin/login.php`               |

---

## 📧 Email Flow

1. Client submits booking → **Verification email sent**
2. Client clicks link → Booking status → **Pending**
3. Admin approves → **Confirmation + payment instructions sent**
4. Client uploads payment → Admin confirms → **Payment confirmed email sent**
5. 24h before event → **Reminder email** (set up a cron job)

### Cron Job (Reminders)
```bash
# Run daily at 9 AM
0 9 * * * php /var/www/html/soulbud/actions/send_reminders.php
```

---

## 🛡️ Security Features
- PDO prepared statements (SQL injection protection)
- `password_hash()` with bcrypt for admin passwords
- CSRF-safe forms (session-based rate limiting)
- File type validation for payment uploads
- Session timeout for admin panel
- `.htaccess` blocks direct access to config, includes, vendor

---

## 📦 Tech Stack

| Layer       | Technology                         |
|-------------|-------------------------------------|
| Backend     | PHP 8.0+                           |
| Database    | MySQL 8.0 with PDO                 |
| Frontend    | Bootstrap 5.3, CSS3, Vanilla JS    |
| Email       | PHPMailer 6.x (SMTP/Gmail)         |
| Calendar    | FullCalendar 6.x                   |
| Charts      | Chart.js 4.x                       |
| Fonts       | Cormorant Garamond + Outfit (Google Fonts) |
| Icons       | Bootstrap Icons 1.11               |
