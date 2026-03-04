<?php
include("database.php");
include("functions.php");
header('Content-Type: application/json');

// Check Admin Access
if (!isset($_SESSION['admin_user'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// 1. Signups per Day (Last 7 Days)
// Fallback: Group by ID ranges if dates are missing, or just accept empty.
try {
    $stats = [];
    
    // Signups
    $signupQuery = "SELECT DATE(created_at) as date, COUNT(*) as count FROM brackets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date ASC";
    $stmt = $db->query($signupQuery);
    $signups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats['signups'] = $signups;

    // Payment Status
    $paidQuery = "SELECT paid, COUNT(*) as count FROM brackets GROUP BY paid";
    $stmt = $db->query($paidQuery);
    $paidData = $stmt->fetchAll(PDO::FETCH_ASSOC); // 0=Unpaid, 1=Paid, 2=Exempt
    $stats['payment'] = $paidData;
    
    // Total Pot
    $meta = $db->query("SELECT cost, cut, cutType, refund_last, payout_1, payout_2, payout_3 FROM meta WHERE id=1")->fetch();
    $totalBrackets = $db->query("SELECT COUNT(*) FROM brackets")->fetchColumn();
    $stats['meta'] = $meta;
    $stats['total_brackets'] = $totalBrackets;

    echo json_encode($stats);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

