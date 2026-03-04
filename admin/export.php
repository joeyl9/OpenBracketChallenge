<?php
include("database.php");
include("functions.php");
validatecookie();

if(!is_admin()) {
    die("Access Denied");
}

// Headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=tournament_data_'.date('Y-m-d').'.csv');

// Create output handle
$output = fopen('php://output', 'w');

// Header Row
$headers = ['BracketID', 'BracketName', 'OwnerName', 'Email', 'Type', 'Paid', 'Score', 'Tiebreaker', 'ChampionPick'];
for($i=1; $i<=63; $i++) {
    $headers[] = "Game_$i";
}
fputcsv($output, $headers);

// Fetch Data
$sql = "SELECT b.*, s.score FROM brackets b LEFT JOIN scores s ON b.id = s.id ORDER BY b.id ASC";
$stmt = $db->query($sql);

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $line = [
        $row['id'],
        $row['name'],
        $row['person'],
        $row['email'],
        $row['type'],
        ($row['paid'] == 1 ? 'Yes' : ($row['paid'] == 2 ? 'Exempt' : 'No')),
        ($row['score'] ?? 0),
        $row['tiebreaker'],
        $row['63']
    ];
    for($i=1; $i<=63; $i++) {
        $line[] = $row[$i];
    }
    fputcsv($output, $line);
}

fclose($output);
exit();
?>

