<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Mailer (PHPMailer wrapper)
// ─────────────────────────────────────────
// Requires: composer require phpmailer/phpmailer
// Or use the downloaded PHPMailer in /vendor

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

class Mailer
{

    private static function instance(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        return $mail;
    }

    // ── Send verification email ──────────────────
    public static function sendVerification(string $toEmail, string $toName, string $token, string $bookingRef): bool
    {
        try {
            $mail = self::instance();
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = '📧 Verify Your Booking — ' . APP_NAME;
            $link = APP_URL . '/verify.php?token=' . urlencode($token);
            $mail->Body = self::verificationTemplate($toName, $link, $bookingRef);
            $mail->send();
            self::log(null, 'verification', $toEmail, $mail->Subject, 'Sent');
            return true;
        } catch (Exception $e) {
            self::log(null, 'verification', $toEmail, '', 'Failed');
            return false;
        }
    }

    // ── Send booking confirmation ─────────────────
    public static function sendConfirmation(array $booking): bool
    {
        try {
            $mail = self::instance();
            $mail->addAddress($booking['email'], $booking['full_name']);
            $mail->Subject = '✅ Booking Confirmed — ' . APP_NAME;
            $mail->Body = self::confirmationTemplate($booking);
            $mail->send();
            self::log($booking['id'], 'confirmation', $booking['email'], $mail->Subject, 'Sent');
            return true;
        } catch (Exception $e) {
            self::log($booking['id'], 'confirmation', $booking['email'], '', 'Failed');
            return false;
        }
    }

    // ── Send rejection / rescheduling notice ──────
    public static function sendStatusUpdate(array $booking, string $type): bool
    {
        try {
            $mail = self::instance();
            $mail->addAddress($booking['email'], $booking['full_name']);
            $subjects = [
                'rejected'    => '❌ Booking Update — ' . APP_NAME,
                'rescheduled' => '📅 Booking Rescheduled — ' . APP_NAME,
                'reminder'    => '⏰ Appointment Reminder — ' . APP_NAME,
                'payment'     => '💳 Payment Confirmed — ' . APP_NAME,
            ];
            $mail->Subject = $subjects[$type] ?? 'Booking Update — ' . APP_NAME;
            $mail->Body = self::statusTemplate($booking, $type);
            $mail->send();
            self::log($booking['id'], $type, $booking['email'], $mail->Subject, 'Sent');
            return true;
        } catch (Exception $e) {
            self::log($booking['id'] ?? null, $type, $booking['email'], '', 'Failed');
            return false;
        }
    }

    // ── Log email ─────────────────────────────────
    private static function log(?int $bookingId, string $type, string $recipient, string $subject, string $status): void
    {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("
                INSERT INTO notification_logs (booking_id, type, recipient, subject, status)
                VALUES (:bid, :type, :recipient, :subject, :status)
            ");
            $stmt->execute([
                ':bid'       => $bookingId,
                ':type'      => $type,
                ':recipient' => $recipient,
                ':subject'   => $subject,
                ':status'    => $status,
            ]);
        } catch (Exception $e) { /* silent */
        }
    }

    // ── Email Templates ───────────────────────────
    private static function verificationTemplate(string $name, string $link, string $ref): string
    {
        return <<<HTML
        <!DOCTYPE html><html><body style="font-family:'Segoe UI',sans-serif;background:#0a0a0a;margin:0;padding:40px 0;">
        <div style="max-width:560px;margin:0 auto;background:#111;border-radius:16px;overflow:hidden;border:1px solid #222;">
          <div style="background:linear-gradient(135deg,#c9a96e,#8b6914);padding:40px 32px;text-align:center;">
            <h1 style="color:#fff;margin:0;font-size:28px;letter-spacing:2px;">SOULBUD.CO</h1>
            <p style="color:rgba(255,255,255,.8);margin:8px 0 0;font-size:13px;letter-spacing:1px;">PHOTOGRAPHY & VIDEOGRAPHY</p>
          </div>
          <div style="padding:36px 32px;">
            <h2 style="color:#c9a96e;margin:0 0 16px;font-size:20px;">Verify Your Email</h2>
            <p style="color:#ccc;line-height:1.7;margin:0 0 24px;">Hi <strong style="color:#fff;">{$name}</strong>, thank you for booking with us! Please verify your email to proceed.</p>
            <p style="color:#888;font-size:13px;margin:0 0 8px;">Booking Reference: <strong style="color:#c9a96e;">{$ref}</strong></p>
            <div style="text-align:center;margin:32px 0;">
             <a href="{$link}" target="_self" style="background:linear-gradient(135deg,#c9a96e,#8b6914);color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-weight:600;font-size:15px;display:inline-block;letter-spacing:1px;">VERIFY EMAIL</a>
            </div>
            <p style="color:#555;font-size:12px;text-align:center;">Link expires in 24 hours. If you did not make this booking, ignore this email.</p>
          </div>
          <div style="padding:20px 32px;border-top:1px solid #222;text-align:center;">
            <p style="color:#444;font-size:12px;margin:0;">© 2025 SOULBUD.CO — All rights reserved.</p>
          </div>
        </div></body></html>
HTML;
    }

    private static function confirmationTemplate(array $b): string
    {
        $date   = date('F j, Y', strtotime($b['preferred_date']));
        $time   = date('g:i A', strtotime($b['preferred_time']));
        $appUrl = APP_URL;
        $ref    = $b['booking_ref'];

        return <<<HTML
    <!DOCTYPE html><html><body style="font-family:'Segoe UI',sans-serif;background:#0a0a0a;margin:0;padding:40px 0;">
    <div style="max-width:560px;margin:0 auto;background:#111;border-radius:16px;overflow:hidden;border:1px solid #222;">
      <div style="background:linear-gradient(135deg,#c9a96e,#8b6914);padding:40px 32px;text-align:center;">
        <h1 style="color:#fff;margin:0;font-size:28px;letter-spacing:2px;">SOULBUD.CO</h1>
      </div>
      <div style="padding:36px 32px;">
        <h2 style="color:#c9a96e;margin:0 0 16px;">✅ Booking Approved!</h2>
        <p style="color:#ccc;">Hi <strong style="color:#fff;">{$b['full_name']}</strong>, your appointment has been approved.</p>
        <table style="width:100%;border-collapse:collapse;margin:24px 0;">
          <tr><td style="color:#888;padding:10px 0;border-bottom:1px solid #222;font-size:13px;">Booking Ref</td><td style="color:#c9a96e;font-weight:600;padding:10px 0;border-bottom:1px solid #222;">{$ref}</td></tr>
          <tr><td style="color:#888;padding:10px 0;border-bottom:1px solid #222;font-size:13px;">Event</td><td style="color:#fff;padding:10px 0;border-bottom:1px solid #222;">{$b['event_type']}</td></tr>
          <tr><td style="color:#888;padding:10px 0;border-bottom:1px solid #222;font-size:13px;">Date</td><td style="color:#fff;padding:10px 0;border-bottom:1px solid #222;">{$date}</td></tr>
          <tr><td style="color:#888;padding:10px 0;border-bottom:1px solid #222;font-size:13px;">Time</td><td style="color:#fff;padding:10px 0;border-bottom:1px solid #222;">{$time}</td></tr>
          <tr><td style="color:#888;padding:10px 0;font-size:13px;">Service</td><td style="color:#fff;padding:10px 0;">{$b['service_type']}</td></tr>
        </table>
        <div style="background:#1a1a1a;border:1px solid #333;border-radius:10px;padding:20px;margin:24px 0;">
          <p style="color:#c9a96e;margin:0 0 12px;font-weight:600;">💳 Payment Instructions</p>
          <p style="color:#ccc;margin:0 0 8px;font-size:13px;"><strong style="color:#fff;">GCash:</strong> 09XX-XXX-XXXX</p>
          <p style="color:#ccc;margin:0 0 8px;font-size:13px;"><strong style="color:#fff;">Bank Transfer:</strong> BDO — 0000-0000-0000</p>
          <p style="color:#ccc;margin:0;font-size:13px;">Upload your proof of payment at: <a href="{$appUrl}/pages/payment.php?ref={$ref}" target="_self" style="color:#c9a96e;">Click here</a></p>
        </div>
      </div>
    </div></body></html>
HTML;
    }

    private static function statusTemplate(array $b, string $type): string
    {
        $messages = [
            'rejected'    => 'We regret to inform you that your booking has been rejected. Please contact us for more information.',
            'rescheduled' => 'Your appointment has been rescheduled. New details have been updated in our system.',
            'reminder'    => 'This is a friendly reminder about your upcoming appointment tomorrow.',
            'payment'     => 'Your payment has been confirmed! Your appointment is fully booked.',
        ];
        $msg = $messages[$type] ?? 'Your booking status has been updated.';
        return <<<HTML
        <!DOCTYPE html><html><body style="font-family:'Segoe UI',sans-serif;background:#0a0a0a;margin:0;padding:40px 0;">
        <div style="max-width:560px;margin:0 auto;background:#111;border-radius:16px;overflow:hidden;border:1px solid #222;">
          <div style="background:linear-gradient(135deg,#c9a96e,#8b6914);padding:32px;text-align:center;">
            <h1 style="color:#fff;margin:0;font-size:24px;letter-spacing:2px;">SOULBUD.CO</h1>
          </div>
          <div style="padding:32px;">
            <p style="color:#ccc;line-height:1.7;">Hi <strong style="color:#fff;">{$b['full_name']}</strong>,</p>
            <p style="color:#ccc;line-height:1.7;">{$msg}</p>
            <p style="color:#888;font-size:13px;">Ref: <strong style="color:#c9a96e;">{$b['booking_ref']}</strong></p>
          </div>
        </div></body></html>
HTML;
    }
}
