<?php
require_once 'config.php';

$user = messagingAuthenticateUser();
$pdo = getMessagingDB();

$userId = $user['id'];
$userType = $user['type'];

try {
    if ($userType === 'user') {
        $query = "SELECT SUM(unread_count_user) as total_unread FROM conversations WHERE user_id = ?";
    } else {
        $query = "SELECT SUM(unread_count_professional) as total_unread FROM conversations WHERE professional_id = ?";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalUnread = $result['total_unread'] ?? 0;
    
    echo json_encode(['totalUnread' => (int)$totalUnread]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
