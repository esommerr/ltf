<?php
// backend/messaging/conversations.php - SIMPLIFIED WORKING VERSION
require_once 'config.php';

$user = messagingAuthenticateUser();
$pdo = getMessagingDB();

$userId = $user['id'];
$userType = $user['type'];

try {
    // Simple base query
    if ($userType === 'user') {
        $query = "SELECT c.* FROM conversations c WHERE c.user_id = ? ORDER BY c.updated_at DESC";
    } else {
        $query = "SELECT c.* FROM conversations c WHERE c.professional_id = ? ORDER BY c.updated_at DESC";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get additional details separately to avoid JOIN issues
    foreach ($conversations as &$conv) {
        if ($userType === 'user') {
            // Get professional details
            $profQuery = "SELECT id, full_name, business_name, profession, photo_path, user_id 
                         FROM professionals WHERE id = ? LIMIT 1";
            $profStmt = $pdo->prepare($profQuery);
            $profStmt->execute([$conv['professional_id']]);
            $professional = $profStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($professional) {
                $conv['professional'] = [
                    'id' => $professional['id'],
                    'full_name' => $professional['full_name'],
                    'business_name' => $professional['business_name'],
                    'profession' => $professional['profession'],
                    'photo_path' => $professional['photo_path'],
                    'user_id' => $professional['user_id']
                ];
            }
        } else {
            // Get user details
            $userQuery = "SELECT id, full_name, email FROM users WHERE id = ? LIMIT 1";
            $userStmt = $pdo->prepare($userQuery);
            $userStmt->execute([$conv['user_id']]);
            $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userDetails) {
                $conv['user'] = [
                    'id' => $userDetails['id'],
                    'full_name' => $userDetails['full_name'],
                    'email' => $userDetails['email']
                ];
            }
        }
        
        // Get last message if exists
        if (!empty($conv['last_message_id'])) {
            $msgQuery = "SELECT id, content, created_at, sender_id FROM messages WHERE id = ? LIMIT 1";
            $msgStmt = $pdo->prepare($msgQuery);
            $msgStmt->execute([$conv['last_message_id']]);
            $lastMessage = $msgStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lastMessage) {
                $conv['last_message_content'] = $lastMessage['content'];
                $conv['last_message_time'] = $lastMessage['created_at'];
                $conv['last_message_sender_id'] = $lastMessage['sender_id'];
            }
        }
    }
    
    echo json_encode($conversations);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
