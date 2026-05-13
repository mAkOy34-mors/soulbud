<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Admin Authentication Helper
// ─────────────────────────────────────────
defined('SOULBUD') or die('Direct access denied.');

function requireAdmin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
    // Session timeout
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        header('Location: ' . APP_URL . '/admin/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function adminUser(): ?array {
    return $_SESSION['admin'] ?? null;
}
