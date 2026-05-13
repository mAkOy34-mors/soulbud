<?php
define('SOULBUD', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$action    = $_GET['action'] ?? $_POST['action'] ?? '';
$sessionId = $_GET['session_id'] ?? $_POST['session_id'] ?? '';

$pdo = db();

switch ($action) {

    case 'send':
        if (!$sessionId || strlen($sessionId) > 64) {
            echo json_encode(['error' => 'Invalid session']);
            exit;
        }
        $msg    = trim($_POST['message'] ?? '');
        $sender = $_POST['sender'] ?? 'client';
        if (!$msg || !in_array($sender, ['client', 'admin'])) {
            echo json_encode(['error' => 'Invalid']);
            exit;
        }
        try {
            $pdo->prepare("INSERT INTO chat_messages (session_id, sender, message) VALUES (?,?,?)")
                ->execute([$sessionId, $sender, $msg]);
            $newId = (int)$pdo->lastInsertId();
            echo json_encode(['success' => true, 'session_id' => $sessionId, 'id' => $newId]);
        } catch (\Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'poll':
        // Needs session ID
        if (!$sessionId || strlen($sessionId) > 64) {
            echo json_encode(['error' => 'Invalid session']);
            exit;
        }
        $since = (int)($_GET['since'] ?? 0);
        $stmt  = $pdo->prepare("SELECT id, sender, message, created_at FROM chat_messages WHERE session_id=? AND id>? ORDER BY id ASC");
        $stmt->execute([$sessionId, $since]);
        $msgs  = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (($_GET['viewer'] ?? '') === 'admin') {
            $pdo->prepare("UPDATE chat_messages SET is_read=1 WHERE session_id=? AND sender='client'")
                ->execute([$sessionId]);
        }
        echo json_encode(['messages' => $msgs]);
        break;

    case 'unread':
        // No session ID needed
        $stmt = $pdo->query("SELECT COUNT(*) FROM chat_messages WHERE sender='client' AND is_read=0");
        echo json_encode(['count' => (int)$stmt->fetchColumn()]);
        break;

    case 'sessions':
        $stmt = $pdo->query("
        SELECT
            m.session_id,
            MAX(m.created_at) AS last_at,
            SUM(CASE WHEN m.sender='client' AND m.is_read=0 THEN 1 ELSE 0 END) AS unread,
            (
                SELECT message FROM chat_messages
                WHERE session_id = m.session_id
                ORDER BY id DESC LIMIT 1
            ) AS last_msg,
            (
                SELECT REPLACE(message, '👤 ', '') FROM chat_messages
                WHERE session_id = m.session_id AND message LIKE '👤 %'
                ORDER BY id ASC LIMIT 1
            ) AS client_name
        FROM chat_messages m
        GROUP BY m.session_id
        ORDER BY last_at DESC
        LIMIT 30
    ");
        echo json_encode(['sessions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;
        echo json_encode(['sessions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
        break;
}
