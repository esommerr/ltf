<?php
require_once 'config.php';

$user = messagingAuthenticateUser();

if ($user['type'] !== 'user') {
    http_response_code(403);
    echo json_encode(["error" => "Only users can start conversations"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['professional_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "professional_id is required"]);
    exit();
}

$userId = $user['id'];
$professionalId = $data['professional_id'];
$pdo = getMessagingDB();

try {
    // Check if professional exists
    $profQuery = "SELECT * FROM professionals WHERE id = ?";
    $profStmt = $pdo->prepare($profQuery);
    $profStmt->execute([$professionalId]);
    $professional = $profStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$professional) {
        http_response_code(404);
        echo json_encode(["error" => "Professional not found"]);
        exit();
    }
    
    // Check if conversation already exists
    $convQuery = "
        SELECT c.*, p.full_name, p.business_name, p.profession, p.photo_path
        FROM conversations c
        LEFT JOIN professionals p ON c.professional_id = p.id
        WHERE c.user_id = ? AND c.professional_id = ?
    ";
    $convStmt = $pdo->prepare($convQuery);
    $convStmt->execute([$userId, $professionalId]);
    $conversation = $convStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        // Create new conversation
        $createQuery = "INSERT INTO conversations (user_id, professional_id) VALUES (?, ?)";
        $createStmt = $pdo->prepare($createQuery);
        $createStmt->execute([$userId, $professionalId]);
        $conversationId = $pdo->lastInsertId();
        
        // Fetch the new conversation
        $fetchQuery = "
            SELECT c.*, p.full_name, p.business_name, p.profession, p.photo_path
            FROM conversations c
            LEFT JOIN professionals p ON c.professional_id = p.id
            WHERE c.id = ?
        ";
        $fetchStmt = $pdo->prepare($fetchQuery);
        $fetchStmt->execute([$conversationId]);
        $conversation = $fetchStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    $response = [
        'id' => $conversation['id'],
        'user_id' => $conversation['user_id'],
        'professional_id' => $conversation['professional_id'],
        'last_message_id' => $conversation['last_message_id'],
        'last_message_at' => $conversation['last_message_at'],
        'unread_count_user' => $conversation['unread_count_user'],
        'unread_count_professional' => $conversation['unread_count_professional'],
        'created_at' => $conversation['created_at'],
        'updated_at' => $conversation['updated_at'],
        'professional' => [
            'id' => $conversation['professional_id'],
            'full_name' => $conversation['full_name'],
            'business_name' => $conversation['business_name'],
            'profession' => $conversation['profession'],
            'photo_path' => $conversation['photo_path']
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
