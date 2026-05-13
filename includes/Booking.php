<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Booking Helper
// ─────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class Booking {

    // ── Generate unique booking reference ──────────
    public static function generateRef(): string {
        do {
            $ref = 'SB-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $exists = db()->prepare("SELECT id FROM bookings WHERE booking_ref = ?");
            $exists->execute([$ref]);
        } while ($exists->rowCount() > 0);
        return $ref;
    }

    // ── Get or create client ───────────────────────
    public static function upsertClient(array $data): int {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->execute([$data['email']]);
        $client = $stmt->fetch();
        if ($client) {
            $pdo->prepare("UPDATE clients SET full_name=?, contact_number=? WHERE id=?")
                ->execute([$data['full_name'], $data['contact_number'], $client['id']]);
            return (int)$client['id'];
        }
        $pdo->prepare("INSERT INTO clients (full_name, email, contact_number) VALUES (?,?,?)")
            ->execute([$data['full_name'], $data['email'], $data['contact_number']]);
        return (int)$pdo->lastInsertId();
    }

    // ── Create booking ─────────────────────────────
    public static function create(array $data): int {
        $ref = self::generateRef();
        $stmt = db()->prepare("
            INSERT INTO bookings
                (booking_ref, client_id, event_type, event_location,
                 preferred_date, preferred_time, service_type, additional_notes)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $ref,
            $data['client_id'],
            $data['event_type'],
            $data['event_location'],
            $data['preferred_date'],
            $data['preferred_time'],
            $data['service_type'],
            $data['additional_notes'] ?? null,
        ]);
        return (int)db()->lastInsertId();
    }

    // ── Create verification token ──────────────────
    public static function createVerificationToken(int $bookingId): string {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        db()->prepare("
            INSERT INTO email_verifications (booking_id, token, expires_at)
            VALUES (?,?,?)
        ")->execute([$bookingId, $token, $expires]);
        return $token;
    }

    // ── Verify token ───────────────────────────────
    public static function verifyToken(string $token): ?array {
        $stmt = db()->prepare("
            SELECT ev.*, b.booking_ref, b.id AS booking_id, b.status,
                   c.email, c.full_name
            FROM email_verifications ev
            JOIN bookings b ON b.id = ev.booking_id
            JOIN clients  c ON c.id = b.client_id
            WHERE ev.token = ? AND ev.is_used = 0 AND ev.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    // ── Mark token as used and update booking status ─
    public static function activateBooking(string $token, int $bookingId): void {
        db()->prepare("UPDATE email_verifications SET is_used=1 WHERE token=?")
             ->execute([$token]);
        db()->prepare("UPDATE bookings SET status='Pending' WHERE id=?")
             ->execute([$bookingId]);
    }

    // ── Get booking by ref ─────────────────────────
    public static function getByRef(string $ref): ?array {
        $stmt = db()->prepare("SELECT * FROM v_bookings_full WHERE booking_ref = ?");
        $stmt->execute([$ref]);
        return $stmt->fetch() ?: null;
    }

    // ── Get all bookings (admin) ───────────────────
    public static function getAll(?string $status = null, int $limit = 50, int $offset = 0): array {
        if ($status) {
            $stmt = db()->prepare("SELECT * FROM v_bookings_full WHERE status=? ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$status, $limit, $offset]);
        } else {
            $stmt = db()->prepare("SELECT * FROM v_bookings_full ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
        }
        return $stmt->fetchAll();
    }

    // ── Count bookings ─────────────────────────────
    public static function count(?string $status = null): int {
        if ($status) {
            $stmt = db()->prepare("SELECT COUNT(*) FROM bookings WHERE status=?");
            $stmt->execute([$status]);
        } else {
            $stmt = db()->query("SELECT COUNT(*) FROM bookings");
        }
        return (int)$stmt->fetchColumn();
    }

    // ── Update status (admin) ──────────────────────
    public static function updateStatus(int $id, string $status, ?string $adminNotes = null, ?string $rDate = null, ?string $rTime = null): bool {
        $stmt = db()->prepare("
            UPDATE bookings
            SET status=?, admin_notes=?, rescheduled_date=?, rescheduled_time=?
            WHERE id=?
        ");
        return $stmt->execute([$status, $adminNotes, $rDate, $rTime, $id]);
    }

    // ── Get booked dates (for calendar) ───────────
    public static function getBookedDates(): array {
        $rows = db()->query("
            SELECT preferred_date, status
            FROM bookings
            WHERE status NOT IN ('Cancelled','Rejected')
        ")->fetchAll();

        $blocked = db()->query("SELECT block_date FROM calendar_blocks")->fetchAll(PDO::FETCH_COLUMN);

        $dates = [];
        foreach ($rows as $r) {
            $dates[$r['preferred_date']] = ($dates[$r['preferred_date']] ?? []);
            $dates[$r['preferred_date']][] = $r['status'];
        }
        return ['booked' => $dates, 'blocked' => $blocked];
    }

    // ── Cancel booking via email link ──────────────
    public static function cancelByRef(string $ref, string $email): bool {
        $stmt = db()->prepare("
            UPDATE bookings b
            JOIN clients c ON c.id = b.client_id
            SET b.status='Cancelled'
            WHERE b.booking_ref=? AND c.email=?
              AND b.status IN ('Pending','Approved')
        ");
        $stmt->execute([$ref, $email]);
        return $stmt->rowCount() > 0;
    }
}

// ── Input sanitizer ────────────────────────────────
function sanitize(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

// ── Validation helper ──────────────────────────────
function validateBookingInput(array $post): array {
    $errors = [];
    $required = ['full_name','email','contact_number','event_type','event_location','preferred_date','preferred_time','service_type'];
    foreach ($required as $field) {
        if (empty($post[$field])) {
            $errors[] = ucwords(str_replace('_',' ',$field)) . ' is required.';
        }
    }
    if (!empty($post['email']) && !filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (!empty($post['preferred_date']) && strtotime($post['preferred_date']) < strtotime('tomorrow')) {
        $errors[] = 'Preferred date must be at least tomorrow.';
    }
    return $errors;
}
