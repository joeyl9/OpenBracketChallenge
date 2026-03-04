<?php
include("../admin/database.php");

header('Content-Type: application/json');

// 1. Auth Check
session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

// 2. Get User Info (ID and Name)
// Find the user's main bracket to attach comment to
$stmt = $db->prepare("SELECT id, person, name FROM brackets WHERE user_id = ? AND type='main' ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(403);
    echo json_encode(['error' => 'You must create a bracket to post smack talk!']);
    exit();
}

// 3. Validate Input
$data = json_decode(file_get_contents('php://input'), true);

// CSRF Check (Manual, since it's JSON)
include("../admin/functions.php"); // Ensure functions loaded
if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $data['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF Validation Failed']);
    exit();
}

$content = isset($data['message']) ? trim($data['message']) : '';

if (empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty message']);
    exit();
}

// 4. Sanitize (Basic Strip Tags)
$specialChars = array('*',';','char(','=','javascript','JavaScript', '%', '&#','<','>','char(39)');
$clean_content = str_replace($specialChars, "", strip_tags($content));
$clean_from = $user['person']; // Use Person Name

// 5. Insert
try {
    $query = "INSERT INTO `comments` (`bracket`, `from`, `content`, `subject`, `time`) VALUES (:bracket, :from, :content, ' ', NOW())"; 
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':bracket' => $user['id'],
        ':from' => $clean_from,
        ':content' => $clean_content
    ]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
