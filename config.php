<?php
// backend/messaging/config.php - FIXED VERSION
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication function - RENAMED
function messagingAuthenticateUser() {
    // First check session (most common for PHP apps)
    if (!isset($_SESSION['user_id'])) {
        // Also check Authorization header as fallback
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            $token = str_replace('Bearer ', '', $authHeader);
            
            // For testing/development only
            if ($token === 'test_token_123') {
                return [
                    'id' => 1,
                    'type' => 'user'
                ];
            }
        }
        
        http_response_code(401);
        echo json_encode([
            "error" => "Authentication required",
            "message" => "Please login to access messaging"
        ]);
        exit();
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'type' => $_SESSION['user_type'] ?? 'user'
    ];
}

// Use the existing getDBConnection from database.php - DON'T redeclare it!
function getMessagingDB() {
    return getDBConnection(); // This calls the function from database.php
}
?>
