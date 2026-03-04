<?php
include("../admin/database.php");

header('Content-Type: application/json');

try {
    // Get latest active broadcast
    $stmt = $db->query("SELECT * FROM broadcasts WHERE active = 1 ORDER BY created_at DESC LIMIT 1");
    $alert = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($alert) {
        echo json_encode([
            'active' => true,
            'id' => $alert['id'],
            'message' => $alert['message'],
            'type' => $alert['type']
        ]);
    } else {
        echo json_encode(['active' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['active' => false]);
}
?>
