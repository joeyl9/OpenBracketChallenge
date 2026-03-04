<?php
include("../admin/database.php");
include("../admin/functions.php");

header('Content-Type: application/json');

// Auth Check 
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Search brackets
// Limit 10 for performance
$stmt = $db->prepare("SELECT id, name, person, email FROM brackets WHERE name LIKE ? OR person LIKE ? OR email LIKE ? LIMIT 10");
$searchParam = "%{$query}%";
$stmt->execute([$searchParam, $searchParam, $searchParam]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
?>
