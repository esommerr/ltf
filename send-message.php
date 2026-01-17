<?php
require_once 'config.php';

$user = messagingAuthenticateUser();
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['conversation_id']) || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(["error" => "conversation_id and message are required"]);
    exit();
}

$conversationId = $data['conversation_id'];
$messageText = trim($data['message']);
$userId = $user['id'];
$userType = $user['type']; // 'user' or 'professional'

if (empty($messageText)) {
    http_response_code(400);
    echo json_encode(["error" => "Message cannot be empty"]);
    exit();
}

$pdo = getMessagingDB();

try {
    // Verify user is part of conversation
    $checkQuery = "SELECT * FROM conversations WHERE id = ? AND (user_id = ? OR professional_id = ?)";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$conversationId, $userId, $userId]);
    $conversation = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        http_response_code(403);
        echo json_encode(["error" => "Not authorized for this conversation"]);
        exit();
    }
    
    // Insert message
    $insertQuery = "INSERT INTO messages (conversation_id, sender_id, sender_type, message) VALUES (?, ?, ?, ?)";
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute([$conversationId, $userId, $userType, $messageText]);
    $messageId = $pdo->lastInsertId();
    
    // Update conversation timestamp
    $updateQuery = "UPDATE conversations SET last_message_at = NOW() WHERE id = ?";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([$conversationId]);
    
    // Get the inserted message
    $fetchQuery = "SELECT * FROM messages WHERE id = ?";
    $fetchStmt = $pdo->prepare($fetchQuery);
    $fetchStmt->execute([$messageId]);
    $message = $fetchStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
