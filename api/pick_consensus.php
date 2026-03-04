<?php
include("../admin/database.php");
include("../admin/functions.php");

header('Content-Type: application/json');

// Auth Check — bracket picks are sensitive strategic data
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

// 1. Fetch all picks (columns 1 to 63)
// We construct the field list dynamically or just select * (lazier but fine)
// Let's be specific for safety/performance
$fields = [];
for($i=1; $i<=63; $i++) {
    $fields[] = "`$i`";
}
$fieldSql = implode(',', $fields);

$query = "SELECT $fieldSql FROM brackets WHERE 1"; // select all brackets
$stmt = $db->query($query);
$all_brackets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_brackets = count($all_brackets);

if ($total_brackets == 0) {
    echo json_encode([]);
    exit;
}

// 2. Aggregate
// Structure: $stats[game_id][team_id] = count
$stats = [];

foreach($all_brackets as $b) {
    for($game=1; $game<=63; $game++) {
        $team = $b[$game];
        if (!empty($team)) { // Ignore empty picks if any
            if (!isset($stats[$game][$team])) {
                $stats[$game][$team] = 0;
            }
            $stats[$game][$team]++;
        }
    }
}

// 3. Convert to Percentages
$output = [];
foreach($stats as $game => $teams) {
    foreach($teams as $team => $count) {
        $percent = round(($count / $total_brackets) * 100);

        $output[$game][$team] = $percent;
    }
}

echo json_encode(['total' => $total_brackets, 'stats' => $output]);
?>
