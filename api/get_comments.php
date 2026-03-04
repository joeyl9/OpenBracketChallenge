<?php
include("../admin/database.php");
include("../admin/functions.php");

// Set header for JSON
header('Content-Type: application/json');

// Auth Check — only logged-in users can read chat
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

// Fetch recent comments
// Limit 50 for the chat window history
$query = "SELECT c.id, c.from, c.content, c.time, c.bracket, b.avatar_url
          FROM comments c
          LEFT JOIN brackets b ON c.bracket = b.id
          ORDER BY c.time DESC LIMIT 50";

$stmt = $db->query($query);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Reverse to show oldest first in the chat window (standard chat flow)
$comments = array_reverse($comments);

echo json_encode($comments);
?>
