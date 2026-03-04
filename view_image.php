<?php
require_once 'admin/database.php';

// type = 'main' or 'sweet16'
$type = isset($_GET['type']) ? $_GET['type'] : 'main';

try {
    $sql = "";
    if ($type === 'main') {
        $sql = "SELECT qr_code_data, qr_code_type FROM meta WHERE id = 1";
    } elseif ($type === 'sweet16') {
        $sql = "SELECT sweet16_qr_data, sweet16_qr_type FROM meta WHERE id = 1";
    } else {
        die("Invalid type");
    }

    $stmt = $db->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($type === 'main') {
        $data = $row['qr_code_data'] ?? null;
        $mime = $row['qr_code_type'] ?? null;
    } else {
        $data = $row['sweet16_qr_data'] ?? null;
        $mime = $row['sweet16_qr_type'] ?? null;
    }

    if ($data && $mime) {
        header("Content-Type: " . $mime);
        echo $data;
    } else {
        http_response_code(404);
        echo "No image found.";
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo "Database Error";
}
?>
