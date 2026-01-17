<?php
// backend/api/user-stats.php
session_start();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
// Return JSON with user stats
echo json_encode([
    'bookings' => getBookingCount($user_id),
    'messages' => getMessageCount($user_id),
    'favorites' => getFavoriteCount($user_id),
    'skills_learning' => getSkillsLearning($user_id)
]);
?>
