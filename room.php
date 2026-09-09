<?php
/**
 * NexusCall - Room Coordination & Participant Signaling API
 * Lightweight SQLite-backed multi-user room registry
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Database setup
$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0777, true);
}

$dbPath = $dataDir . DIRECTORY_SEPARATOR . 'rooms.sqlite';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create table if not exists
    $db->exec("
        CREATE TABLE IF NOT EXISTS room_participants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id TEXT NOT NULL,
            peer_id TEXT NOT NULL,
            user_name TEXT NOT NULL,
            joined_at INTEGER NOT NULL,
            last_seen INTEGER NOT NULL,
            UNIQUE(room_id, peer_id)
        );
        CREATE INDEX IF NOT EXISTS idx_room_id ON room_participants(room_id);
        CREATE INDEX IF NOT EXISTS idx_last_seen ON room_participants(last_seen);
    ");

    // Clean up stale participants (no heartbeat for > 15 seconds)
    $staleThreshold = time() - 15;
    $cleanupStmt = $db->prepare("DELETE FROM room_participants WHERE last_seen < :threshold");
    $cleanupStmt->execute([':threshold' => $staleThreshold]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database initialization failed: ' . $e->getMessage()
    ]);
    exit;
}

// Parse request parameters
$rawInput = file_get_contents('php://input');
$jsonInput = !empty($rawInput) ? json_decode($rawInput, true) : null;

$action = $_REQUEST['action'] ?? ($jsonInput['action'] ?? '');
$roomId = trim($_REQUEST['room_id'] ?? ($jsonInput['room_id'] ?? ''));
$peerId = trim($_REQUEST['peer_id'] ?? ($jsonInput['peer_id'] ?? ''));
$userName = trim($_REQUEST['user_name'] ?? ($jsonInput['user_name'] ?? 'Guest'));

// Clean Room ID (alphanumeric and dashes)
$roomId = preg_replace('/[^a-zA-Z0-9_-]/', '', $roomId);
if (empty($roomId) && in_array($action, ['join', 'heartbeat', 'leave', 'get_peers'])) {
    echo json_encode(['status' => 'error', 'message' => 'Valid room_id is required.']);
    exit;
}

$now = time();

switch ($action) {
    case 'join':
        if (empty($peerId)) {
            echo json_encode(['status' => 'error', 'message' => 'peer_id is required to join.']);
            exit;
        }

        if (empty($userName)) {
            $userName = 'Guest-' . substr($peerId, 0, 4);
        }

        // Upsert participant
        $stmt = $db->prepare("
            INSERT INTO room_participants (room_id, peer_id, user_name, joined_at, last_seen)
            VALUES (:room_id, :peer_id, :user_name, :joined_at, :last_seen)
            ON CONFLICT(room_id, peer_id) DO UPDATE SET
                user_name = excluded.user_name,
                last_seen = excluded.last_seen
        ");
        $stmt->execute([
            ':room_id' => $roomId,
            ':peer_id' => $peerId,
            ':user_name' => $userName,
            ':joined_at' => $now,
            ':last_seen' => $now
        ]);

        // Return current active peers in this room (excluding self)
        $peersStmt = $db->prepare("
            SELECT peer_id, user_name, joined_at 
            FROM room_participants 
            WHERE room_id = :room_id AND peer_id != :peer_id
            ORDER BY joined_at ASC
        ");
        $peersStmt->execute([
            ':room_id' => $roomId,
            ':peer_id' => $peerId
        ]);
        $peers = $peersStmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'action' => 'join',
            'room_id' => $roomId,
            'peer_id' => $peerId,
            'peers' => $peers,
            'participant_count' => count($peers) + 1
        ]);
        break;

    case 'heartbeat':
        if (empty($peerId)) {
            echo json_encode(['status' => 'error', 'message' => 'peer_id is required for heartbeat.']);
            exit;
        }

        $stmt = $db->prepare("
            UPDATE room_participants 
            SET last_seen = :last_seen 
            WHERE room_id = :room_id AND peer_id = :peer_id
        ");
        $stmt->execute([
            ':last_seen' => $now,
            ':room_id' => $roomId,
            ':peer_id' => $peerId
        ]);

        // Return current list of active peers in the room
        $peersStmt = $db->prepare("
            SELECT peer_id, user_name, joined_at 
            FROM room_participants 
            WHERE room_id = :room_id AND peer_id != :peer_id
            ORDER BY joined_at ASC
        ");
        $peersStmt->execute([
            ':room_id' => $roomId,
            ':peer_id' => $peerId
        ]);
        $peers = $peersStmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'action' => 'heartbeat',
            'room_id' => $roomId,
            'peers' => $peers,
            'participant_count' => count($peers) + 1
        ]);
        break;

    case 'leave':
        if (!empty($peerId)) {
            $stmt = $db->prepare("
                DELETE FROM room_participants 
                WHERE room_id = :room_id AND peer_id = :peer_id
            ");
            $stmt->execute([
                ':room_id' => $roomId,
                ':peer_id' => $peerId
            ]);
        }

        echo json_encode([
            'status' => 'success',
            'action' => 'leave',
            'room_id' => $roomId
        ]);
        break;

    case 'get_peers':
        $peersStmt = $db->prepare("
            SELECT peer_id, user_name, joined_at 
            FROM room_participants 
            WHERE room_id = :room_id AND peer_id != :peer_id
            ORDER BY joined_at ASC
        ");
        $peersStmt->execute([
            ':room_id' => $roomId,
            ':peer_id' => $peerId
        ]);
        $peers = $peersStmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'room_id' => $roomId,
            'peers' => $peers,
            'participant_count' => count($peers) + (!empty($peerId) ? 1 : 0)
        ]);
        break;

    case 'stats':
        $statsStmt = $db->query("
            SELECT room_id, COUNT(*) as count 
            FROM room_participants 
            GROUP BY room_id
        ");
        $stats = $statsStmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'active_rooms' => $stats,
            'server_time' => $now
        ]);
        break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action specified. Supported actions: join, heartbeat, leave, get_peers, stats'
        ]);
        break;
}
