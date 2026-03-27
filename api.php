<?php
// Version: 1.1.0
// Last Updated: 2026-03-27

// api.php
require_once 'db.php';

header('Content-Type: application/json');

// --- Configuration ---
// Generate a strong string here (e.g., openssl rand -hex 32)
// CloudBot will need to send this in the header or POST body to submit quotes.
$apiKey = 'changeme_to_a_secure_random_string'; 

$db = DB::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ============================================================================
// 1. PUBLIC GET REQUESTS (Read-Only)
// ============================================================================
if ($method === 'GET') {
    
    // Fetch a random approved quote (Usage: !hqdb)
    if ($action === 'random') {
        $stmt = $db->prepare("SELECT id, quote_text, score FROM quotes WHERE status = 'approved' ORDER BY RANDOM() LIMIT 1");
        $stmt->execute();
        $quote = $stmt->fetch();

        if ($quote) {
            echo json_encode(['success' => true, 'data' => $quote]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Database empty.']);
        }
        exit;
    }

    // Fetch a specific quote by ID (Usage: !hqdb 123)
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT id, quote_text, score FROM quotes WHERE id = :id AND status = 'approved'");
        $stmt->execute([':id' => $id]);
        $quote = $stmt->fetch();

        if ($quote) {
            echo json_encode(['success' => true, 'data' => $quote]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Quote not found.']);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid GET action.']);
    exit;
}

// ============================================================================
// 2. AUTHENTICATED POST REQUESTS (Write)
// ============================================================================
if ($method === 'POST') {
    
    // Check for the API Key in the headers or POST body
    $providedKey = $_SERVER['HTTP_X_API_KEY'] ?? $_POST['api_key'] ?? '';
    
    // Use hash_equals to prevent timing attacks
    if (!hash_equals($apiKey, $providedKey)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Invalid API key.']);
        exit;
    }

    // Submit a new quote to the moderation queue (Usage: !hqdb add <text>)
    if ($action === 'add') {
        $quoteText = trim($_POST['quote_text'] ?? '');
        $ipAddress = $_SERVER['REMOTE_ADDR']; // This will be the server IP where CloudBot runs
        
        if (empty($quoteText)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Payload cannot be null.']);
            exit;
        }

        try {
            $db->beginTransaction();
            
            // Bypass standard flood control for API submissions, insert directly to pending
            $stmt = $db->prepare("INSERT INTO quotes (quote_text, submitted_by_ip) VALUES (:text, :ip)");
            $stmt->execute([':text' => $quoteText, ':ip' => $ipAddress]);
            $newId = $db->lastInsertId();
            
            $db->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Quote transmitted to buffer.', 
                'id' => $newId
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database fault.']);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid POST action.']);
    exit;
}

// Default fallback
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method Not Allowed.']);
exit;
