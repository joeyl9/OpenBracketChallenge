<?php
// Usage: avatar.php?name=John+Doe&bg=random

$name = isset($_GET['name']) ? $_GET['name'] : 'User';
$name =  str_replace('+', ' ', $name);

// Get Initials
$words = explode(" ", $name);
$initials = "";
foreach ($words as $w) {
    if (!empty($w)) {
        $initials .= strtoupper($w[0]);
    }
}
$initials = substr($initials, 0, 2);

$colors = [
    '#ef4444', // Red 500
    '#f97316', // Orange 500
    '#f59e0b', // Amber 500
    '#84cc16', // Lime 500
    '#10b981', // Emerald 500
    '#06b6d4', // Cyan 500
    '#3b82f6', // Blue 500
    '#8b5cf6', // Violet 500
    '#d946ef', // Fuchsia 500
    '#f43f5e'  // Rose 500
];

// Pick color based on name hash so it stays consistent for the same user
$seed = crc32($name);
$bgColor = $colors[$seed % count($colors)];

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if($user_id > 0) {
    // Try serving from DB
    require_once 'admin/database.php';
    $stmt = $db->prepare("SELECT avatar_data, avatar_type FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user && $user['avatar_data']) {
        header("Content-Type: " . $user['avatar_type']);
        echo $user['avatar_data'];
        exit;
    }
}

// Fallback to Initials Generator
header('Content-Type: image/svg+xml');
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
  <rect width="100" height="100" rx="0" fill="<?php echo $bgColor; ?>"/>
  <text x="50" y="50" alignment-baseline="central" text-anchor="middle" font-family="Arial, sans-serif" font-size="40" font-weight="bold" fill="white">
    <?php echo htmlspecialchars($initials); ?>
  </text>
</svg>
