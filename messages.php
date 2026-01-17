<?php
require_once 'config.php';

$user = messagingAuthenticateUser();
$pdo = getMessagingDB();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get messages for a conversation
        if (!isset($_GET['conversation_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "conversation_id is required"]);
            exit();
        }
        
        $conversationId = $_GET['conversation_id'];
        $userId = $user['id'];
        $userType = $user['type'];
        
        // Verify user has access to this conversation
        $checkQuery = "SELECT * FROM conversations WHERE id = ? AND (user_id = ? OR professional_id = ?)";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$conversationId, $userId, $userId]);
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["error" => "Conversation not found"]);
            exit();
        }
        
        // Get messages
        $query = "
            SELECT m.*, u.full_name as sender_name
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$conversationId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark messages as read
        $unreadMessageIds = [];
        foreach ($messages as $msg) {
            if ($msg['receiver_id'] == $userId && !$msg['is_read']) {
                $unreadMessageIds[] = $msg['id'];
            }
        }
        
        if (!empty($unreadMessageIds)) {
            $placeholders = implode(',', array_fill(0, count($unreadMessageIds), '?'));
            $updateQuery = "
                UPDATE messages 
                SET is_read = 1, read_at = NOW() 
                WHERE id IN ($placeholders)
            ";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute($unreadMessageIds);
            
            // Update conversation unread count
            $unreadField = $userType === 'user' ? 'unread_count_user' : 'unread_count_professional';
            $updateConvQuery = "
                UPDATE conversations 
                SET $unreadField = 0 
                WHERE id = ?
            ";
            $pdo->prepare($updateConvQuery)->execute([$conversationId]);
        }
        
        // Format response
        $formattedMessages = [];
        foreach ($messages as $msg) {
            $formattedMessages[] = [
                'id' => $msg['id'],
                'sender_id' => $msg['sender_id'],
                'receiver_id' => $msg['receiver_id'],
                'professional_id' => $msg['professional_id'],
                'conversation_id' => $msg['conversation_id'],
                'content' => $msg['content'],
                'is_read' => $msg['is_read'],
                'read_at' => $msg['read_at'],
                'created_at' => $msg['created_at'],
                'sender' => [
                    'id' => $msg['sender_id'],
                    'full_name' => $msg['sender_name']
                ]
            ];
        }
        
        echo json_encode($formattedMessages);
        break;
        
    case 'POST':
        // Send a new message
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['content']) || (!isset($data['conversation_id']) && !isset($data['professional_id']))) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required fields"]);
            exit();
        }
        
        $senderId = $user['id'];
        $userType = $user['type'];
        $content = $data['content'];
        $conversationId = $data['conversation_id'] ?? null;
        $professionalId = $data['professional_id'] ?? null;
        
        try {
            $pdo->beginTransaction();
            
            if ($conversationId) {
                // Existing conversation
                $convQuery = "SELECT * FROM conversations WHERE id = ?";
                $convStmt = $pdo->prepare($convQuery);
                $convStmt->execute([$conversationId]);
                $conversation = $convStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$conversation) {
                    throw new Exception("Conversation not found");
                }
                
                // Determine receiver
                if ($userType === 'user') {
                    $profQuery = "SELECT user_id FROM professionals WHERE id = ?";
                    $profStmt = $pdo->prepare($profQuery);
                    $profStmt->execute([$conversation['professional_id']]);
                    $professional = $profStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $receiverId = $conversation['user_id'] == $senderId 
                        ? $professional['user_id']
                        : $conversation['user_id'];
                    $profId = $conversation['professional_id'];
                } else {
                    $receiverId = $conversation['user_id'];
                    $profId = $conversation['professional_id'];
                }
            } else if ($professionalId && $userType === 'user') {
                // New conversation initiated by user
                $profQuery = "SELECT * FROM professionals WHERE id = ?";
                $profStmt = $pdo->prepare($profQuery);
                $profStmt->execute([$professionalId]);
                $professional = $profStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$professional) {
                    throw new Exception("Professional not found");
                }
                
                // Check if conversation exists
                $convCheckQuery = "SELECT * FROM conversations WHERE user_id = ? AND professional_id = ?";
                $convCheckStmt = $pdo->prepare($convCheckQuery);
                $convCheckStmt->execute([$senderId, $professionalId]);
                $conversation = $convCheckStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$conversation) {
                    // Create new conversation
                    $createConvQuery = "INSERT INTO conversations (user_id, professional_id) VALUES (?, ?)";
                    $createStmt = $pdo->prepare($createConvQuery);
                    $createStmt->execute([$senderId, $professionalId]);
                    $conversationId = $pdo->lastInsertId();
                    
                    $convQuery = "SELECT * FROM conversations WHERE id = ?";
                    $convStmt = $pdo->prepare($convQuery);
                    $convStmt->execute([$conversationId]);
                    $conversation = $convStmt->fetch(PDO::FETCH_ASSOC);
                }
                
                $receiverId = $professional['user_id'];
                $profId = $professionalId;
                $conversationId = $conversation['id'];
            } else {
                throw new Exception("Invalid request");
            }
            
            // Insert message
            $messageQuery = "
                INSERT INTO messages (sender_id, receiver_id, professional_id, conversation_id, content) 
                VALUES (?, ?, ?, ?, ?)
            ";
            $messageStmt = $pdo->prepare($messageQuery);
            $messageStmt->execute([$senderId, $receiverId, $profId, $conversationId, $content]);
            $messageId = $pdo->lastInsertId();
            
            // Update conversation
            $unreadField = $userType === 'user' ? 'unread_count_professional' : 'unread_count_user';
            $updateConvQuery = "
                UPDATE conversations 
                SET last_message_id = ?, 
                    last_message_at = NOW(), 
                    updated_at = NOW(),
                    $unreadField = $unreadField + 1
                WHERE id = ?
            ";
            $updateStmt = $pdo->prepare($updateConvQuery);
            $updateStmt->execute([$messageId, $conversationId]);
            
            // Get the created message with sender info
            $getMessageQuery = "
                SELECT m.*, u.full_name as sender_name
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.id = ?
            ";
            $getStmt = $pdo->prepare($getMessageQuery);
            $getStmt->execute([$messageId]);
            $message = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            $pdo->commit();
            
            $response = [
                'id' => $message['id'],
                'sender_id' => $message['sender_id'],
                'receiver_id' => $message['receiver_id'],
                'professional_id' => $message['professional_id'],
                'conversation_id' => $message['conversation_id'],
                'content' => $message['content'],
                'is_read' => $message['is_read'],
                'created_at' => $message['created_at'],
                'sender' => [
                    'id' => $message['sender_id'],
                    'full_name' => $message['sender_name']
                ]
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
}
?>
