<?php
require_once 'config.php';

$user = messagingAuthenticateUser();
$conversationId = $_GET['conversation_id'] ?? null;

if (!$conversationId) {
    http_response_code(400);
    echo json_encode(["error" => "conversation_id is required"]);
    exit();
}

$userId = $user['id'];
$pdo = getMessagingDB();

try {
    // Verify access
    $checkQuery = "SELECT * FROM conversations WHERE id = ? AND (user_id = ? OR professional_id = ?)";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$conversationId, $userId, $userId]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(403);
        echo json_encode(["error" => "Not authorized"]);
        exit();
    }
    
    // Get messages
    $messagesQuery = "
        SELECT m.*, 
               u.full_name as sender_name,
               CASE 
                   WHEN m.sender_type = 'user' THEN u.profile_pic
                   ELSE p.photo_path 
               END as sender_photo
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id AND m.sender_type = 'user'
        LEFT JOIN professionals p ON m.sender_id = p.id AND m.sender_type = 'professional'
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
    ";
    
    $messagesStmt = $pdo->prepare($messagesQuery);
    $messagesStmt->execute([$conversationId]);
    $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read
    $markReadQuery = "
        UPDATE messages 
        SET is_read = TRUE 
        WHERE conversation_id = ? 
        AND sender_id != ? 
        AND is_read = FALSE
    ";
    $markReadStmt = $pdo->prepare($markReadQuery);
    $markReadStmt->execute([$conversationId, $userId]);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
