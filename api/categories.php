<?php
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get database connection
$db = Database::getInstance()->getConnection();

// Get action from request (only GET allowed)
$action = $_GET['action'] ?? '';

// Response helper
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

try {
    // Only getAll action is supported (read-only)
    if ($action === 'getAll') {
        $stmt = $db->query("SELECT id, name, description FROM categories ORDER BY name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(true, 'Categories retrieved successfully', $categories);
    } else {
        sendResponse(false, 'Invalid action. Only getAll is supported.');
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    sendResponse(false, 'Database error occurred');
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    sendResponse(false, 'An error occurred');
}
