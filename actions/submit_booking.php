<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Action: Submit Booking
// POST /actions/submit_booking.php
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Booking.php';
require_once __DIR__ . '/../includes/Mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed.']));
}

// Rate-limit: max 3 submissions per IP per hour (session-based simple check)
session_start();
$_SESSION['booking_attempts'] = ($_SESSION['booking_attempts'] ?? 0) + 1;
$_SESSION['booking_window']   = $_SESSION['booking_window'] ?? time();
if (time() - $_SESSION['booking_window'] < 3600 && $_SESSION['booking_attempts'] > 3) {
    http_response_code(429);
    exit(json_encode(['success' => false, 'message' => 'Too many submissions. Please try again later.']));
}

// Sanitize & validate
$data = [
    'full_name'        => sanitize($_POST['full_name']        ?? ''),
    'email'            => strtolower(trim($_POST['email']     ?? '')),
    'contact_number'   => sanitize($_POST['contact_number']   ?? ''),
    'event_type'       => sanitize($_POST['event_type']       ?? ''),
    'event_location'   => sanitize($_POST['event_location']   ?? ''),
    'preferred_date'   => sanitize($_POST['preferred_date']   ?? ''),
    'preferred_time'   => sanitize($_POST['preferred_time']   ?? ''),
    'service_type'     => sanitize($_POST['service_type']     ?? ''),
    'additional_notes' => sanitize($_POST['additional_notes'] ?? ''),
];

$errors = validateBookingInput($data);
if (!empty($errors)) {
    http_response_code(422);
    exit(json_encode(['success' => false, 'errors' => $errors]));
}

// Check if date is blocked
$blocked = db()->prepare("SELECT id FROM calendar_blocks WHERE block_date = ?");
$blocked->execute([$data['preferred_date']]);
if ($blocked->rowCount() > 0) {
    exit(json_encode(['success' => false, 'message' => 'Selected date is unavailable. Please choose another date.']));
}

try {
    db()->beginTransaction();

    // Upsert client
    $clientId = Booking::upsertClient($data);
    $data['client_id'] = $clientId;

    // Create booking
    $bookingId  = Booking::create($data);
    $bookingRef = db()->query("SELECT booking_ref FROM bookings WHERE id = $bookingId")->fetchColumn();

    // Create verification token
    $token = Booking::createVerificationToken($bookingId);

    db()->commit();

    // Send verification email (non-blocking)
    Mailer::sendVerification($data['email'], $data['full_name'], $token, $bookingRef);

    // Reset attempt counter on success
    $_SESSION['booking_attempts'] = 0;

    exit(json_encode([
        'success'     => true,
        'booking_ref' => $bookingRef,
        'message'     => 'Booking submitted! Please verify your email.',
    ]));

} catch (Throwable $e) {
    db()->rollBack();
    error_log('Booking submission error: ' . $e->getMessage());
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']));
}
