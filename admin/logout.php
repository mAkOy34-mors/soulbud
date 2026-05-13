<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Admin Logout
// ─────────────────────────────────────────
define('SOULBUD', true);
require_once __DIR__ . '/../config/config.php';

session_start();
$_SESSION = [];
session_destroy();
header('Location: ' . APP_URL . '/admin/login.php');
exit;
