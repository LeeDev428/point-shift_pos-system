<?php
// API Authentication Endpoint for Mobile App
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    default:
        sendResponse(false, 'Invalid action');
        break;
}

function handleLogin() {
    global $conn;
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        sendResponse(false, 'Username and password are required');
        return;
    }
    
    try {
        // Query user
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Invalid username or password');
            return;
        }
        
        $user = $result->fetch_assoc();
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            sendResponse(false, 'Invalid username or password');
            return;
        }
        
        // Remove sensitive data
        unset($user['password']);
        
        // Generate simple token (in production, use JWT)
        $token = bin2hex(random_bytes(32));
        
        // Return success response
        sendResponse(true, 'Login successful', [
            'user' => $user,
            'token' => $token
        ]);
        
    } catch (Exception $e) {
        sendResponse(false, 'Server error: ' . $e->getMessage());
    }
}

function sendResponse($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit();
}
?>
