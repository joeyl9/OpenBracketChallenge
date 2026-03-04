<?php
include("../admin/database.php");
include("../admin/functions.php");

header('Content-Type: application/json');

// Auth Check (Session-only — cookies are forgeable)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$scenario = $input['scenario'] ?? [];

if (empty($scenario)) {
    // Return current stats if no scenario? Or error? Let's just run it empty (current standings).
}

try {
    // Run Simulation
    $leaderboard = simulateLeaderboard($db, $scenario);

    // Find Me (use session email, not cookie)
    $myEmail = $_SESSION['useremail'] ?? '';
    $myRank = -1;
    $myScore = 0;

    $rank_itr = 1;
    $prevScore = -1;
    $display_r = 1;

    foreach ($leaderboard as $entry) {
        if ($prevScore != $entry['score']) {
            $display_r = $rank_itr;
            $prevScore = $entry['score'];
        }

        if ($entry['email'] == $myEmail) {
            $myRank = $display_r;
            $myScore = $entry['score'];
        }
        $rank_itr++;
    }

    // Top 3
    $top3 = array_slice($leaderboard, 0, 3);

    echo json_encode([
        'success' => true,
        'my_rank' => $myRank,
        'my_score' => $myScore,
        'top_user' => $top3[0]['name'] ?? 'None',
        'top_score' => $top3[0]['score'] ?? 0,
        'leaderboard_top' => $top3
    ]);

} catch (Exception $e) {

    echo json_encode(['success' => false, 'error' => 'Simulation failed. Please try again.']);
}
?>
