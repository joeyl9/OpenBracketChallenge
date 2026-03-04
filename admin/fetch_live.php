<?php
include("database.php");
include("functions.php");
validatecookie();

if(!is_admin()) die("Access Denied");

// Check Toggle
$stmt = $db->query("SELECT use_live_scoring FROM meta WHERE id=1");
$metaRow = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$metaRow['use_live_scoring']) {
    die("Live Scoring is DISABLED in Settings.");
}

// ---------------------------------------------------------
// 1. Fetch Data (ESPN Public API)
// ---------------------------------------------------------
$apiUrl = 'https://site.api.espn.com/apis/site/v2/sports/basketball/mens-college-basketball/scoreboard?limit=100&groups=100';

$json = @file_get_contents($apiUrl);
if ($json === FALSE && function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'TourneyBracket/1.0');
    $json = curl_exec($ch);
    curl_close($ch);
}

// Parse completed games into matchup pairs: [winner_names[], loser_names[]]
$completedGames = [];
if ($json) {
    $data = json_decode($json, true);
    if (isset($data['events'])) {
        foreach($data['events'] as $event) {
            $status = $event['status']['type']['state'] ?? '';
            if ($status !== 'post') continue;

            $winnerNames = [];
            $loserNames = [];
            foreach($event['competitions'][0]['competitors'] as $competitor) {
                // Collect all name variants for fuzzy matching against bracket
                $names = [];
                if (!empty($competitor['team']['displayName']))      $names[] = $competitor['team']['displayName'];
                if (!empty($competitor['team']['shortDisplayName']))  $names[] = $competitor['team']['shortDisplayName'];
                if (!empty($competitor['team']['name']))              $names[] = $competitor['team']['name'];
                if (!empty($competitor['team']['nickname']))          $names[] = $competitor['team']['nickname'];
                if (!empty($competitor['team']['location']))          $names[] = $competitor['team']['location'];

                if (isset($competitor['winner']) && $competitor['winner'] === true) {
                    $winnerNames = $names;
                } else {
                    $loserNames = $names;
                }
            }
            if (!empty($winnerNames) && !empty($loserNames)) {
                $completedGames[] = [
                    'winner' => $winnerNames,
                    'loser'  => $loserNames,
                ];
            }
        }
    }
}

if (empty($completedGames)) {
    include("header.php");
    echo '<div id="main"><div class="full">';
    echo '<h2>Live Score Fetch</h2>';
    echo '<p>No completed tournament games found in live feed.</p>';
    echo '<a href="index.php" class="btn-outline">&larr; Back to Admin</a>';
    echo '</div></div></div>';
    include('footer.php');
    echo '</body>
</html>';
    exit;
}

// ---------------------------------------------------------
// 2. Load Master Bracket
// ---------------------------------------------------------
$masterQ = $db->query("SELECT * FROM master WHERE id=1"); // Teams (slots 1-64)
$teams = $masterQ->fetch(PDO::FETCH_ASSOC);

$masterWinQ = $db->query("SELECT * FROM master WHERE id=2"); // Winners (games 1-63)
$winners = $masterWinQ->fetch(PDO::FETCH_ASSOC);
if(!$winners) {
    $db->exec("INSERT INTO master (id) VALUES (2)");
    $winners = array_fill(1, 63, "");
}

// ---------------------------------------------------------
// Helper: Get the two teams playing in a given game
// ---------------------------------------------------------
// For Round 1 (games 1-32): teams come from initial seeding (row id=1)
//   Game N -> team slot (2*N-1) vs team slot (2*N)
// For Round 2+ (games 33-63): teams come from winners of feeder games
//   The two feeder games for game G are determined by bracket structure.
function getFeederGames($gameId) {
    if ($gameId <= 32) return null; // Round 1 has no feeders
    if ($gameId <= 48) { $base = 32; $offset = 1;  } // R2: games 33-48 fed by R1 games 1-32
    elseif ($gameId <= 56) { $base = 48; $offset = 33; } // R3: games 49-56 fed by R2 games 33-48
    elseif ($gameId <= 60) { $base = 56; $offset = 49; } // R4: games 57-60 fed by R3 games 49-56
    elseif ($gameId <= 62) { $base = 60; $offset = 57; } // F4: games 61-62 fed by R4 games 57-60
    else                   { return [61, 62]; }           // Championship: game 63 fed by F4 games 61-62

    $pos = $gameId - $base; // 1-based position within this round
    $feeder1 = $offset + ($pos - 1) * 2;
    $feeder2 = $feeder1 + 1;
    return [$feeder1, $feeder2];
}

function getTeamsForGame($gameId, $teams, $winners) {
    if ($gameId <= 32) {
        // Round 1: teams from initial seeding
        $slot1 = ($gameId * 2) - 1;
        $slot2 = $gameId * 2;
        return [
            trim($teams[$slot1] ?? ''),
            trim($teams[$slot2] ?? ''),
        ];
    }
    // Later rounds: teams are winners of feeder games
    $feeders = getFeederGames($gameId);
    if (!$feeders) return ['', ''];
    return [
        trim($winners[$feeders[0]] ?? ''),
        trim($winners[$feeders[1]] ?? ''),
    ];
}

// Helper: check if a name matches any variant from ESPN
function nameMatchesAny($bracketName, $espnNames) {
    $bracketNorm = strtolower(trim($bracketName));
    if (empty($bracketNorm)) return false;
    foreach ($espnNames as $n) {
        if (strtolower(trim($n)) === $bracketNorm) return true;
    }
    return false;
}

// ---------------------------------------------------------
// 3. Match ESPN results to bracket games
// ---------------------------------------------------------
$updatedCount = 0;
$log = [];

// Find the team's current active game (first unfilled game in their path)
function findActiveGame($teamName, $teams, $winners) {
    // Find starting slot (1-64)
    $startSlot = null;
    foreach ($teams as $key => $val) {
        if (is_numeric($key) && strtolower(trim($val)) === strtolower(trim($teamName))) {
            $startSlot = (int)$key;
            break;
        }
    }
    if (!$startSlot) return null;

    $currentGame = (int)ceil($startSlot / 2);

    while ($currentGame <= 63) {
        $existingWinner = trim($winners[$currentGame] ?? '');

        if (strtolower($existingWinner) === strtolower(trim($teamName))) {
            // Already won this game, advance to next round
            $currentGame = getNextGame($currentGame);
            if ($currentGame === null) return null; // Won championship already
        } elseif (empty($existingWinner)) {
            // This is the active (unfilled) game
            return $currentGame;
        } else {
            // Someone else won this game — team is eliminated
            return null;
        }
    }
    return null;
}

function getNextGame($currentGame) {
    if ($currentGame <= 32) return 32 + (int)ceil($currentGame / 2);
    if ($currentGame <= 48) return 48 + (int)ceil(($currentGame - 32) / 2);
    if ($currentGame <= 56) return 56 + (int)ceil(($currentGame - 48) / 2);
    if ($currentGame <= 60) return 60 + (int)ceil(($currentGame - 56) / 2);
    if ($currentGame <= 62) return 63;
    return null; // Game 63 is the championship, no next game
}

foreach ($completedGames as $game) {
    $winnerNames = $game['winner'];
    $loserNames  = $game['loser'];

    // Find the winning team's bracket name (match against slots 1-64)
    $bracketWinner = null;
    foreach ($teams as $key => $val) {
        if (!is_numeric($key)) continue;
        if (nameMatchesAny($val, $winnerNames)) {
            $bracketWinner = trim($val);
            break;
        }
    }
    if (!$bracketWinner) continue; // Winner not in this tournament

    // Find the losing team's bracket name
    $bracketLoser = null;
    foreach ($teams as $key => $val) {
        if (!is_numeric($key)) continue;
        if (nameMatchesAny($val, $loserNames)) {
            $bracketLoser = trim($val);
            break;
        }
    }
    if (!$bracketLoser) continue; // Loser not in this tournament

    // Find the winner's current active game
    $activeGame = findActiveGame($bracketWinner, $teams, $winners);
    if ($activeGame === null) {
        $log[] = "$bracketWinner: no active game (already advanced or eliminated)";
        continue;
    }

    // Validate: the two teams in this bracket game must match the ESPN matchup
    $gamePlayers = getTeamsForGame($activeGame, $teams, $winners);
    $team1 = strtolower($gamePlayers[0]);
    $team2 = strtolower($gamePlayers[1]);
    $wNorm = strtolower($bracketWinner);
    $lNorm = strtolower($bracketLoser);

    // Both teams must be filled in (both must have reached this round)
    if (empty($gamePlayers[0]) || empty($gamePlayers[1])) {
        $log[] = "$bracketWinner: Game $activeGame waiting for opponent (one side empty)";
        continue;
    }

    // Check that the winner and loser are actually the two teams in this game
    $matchesGame = (($team1 === $wNorm && $team2 === $lNorm) ||
                    ($team1 === $lNorm && $team2 === $wNorm));

    if (!$matchesGame) {
        $log[] = "$bracketWinner: Game $activeGame is $gamePlayers[0] vs $gamePlayers[1] — doesn't match ESPN result ($bracketWinner beat $bracketLoser). Skipped.";
        continue;
    }

    // All validated — record the winner for this single game only
    $winners[$activeGame] = $bracketWinner;
    $updatedCount++;
    $log[] = "$bracketWinner beat $bracketLoser in Game $activeGame ✓";
}

// ---------------------------------------------------------
// 4. Save & Report
// ---------------------------------------------------------
include("header.php");
echo '<div id="main"><div class="full">';
echo '<h2>Live Score Fetch</h2>';

echo '<p>Found ' . count($completedGames) . ' completed games from ESPN feed.</p>';

if ($updatedCount > 0) {
    // Update Master Bracket using prepared statement
    $setClauses = [];
    $params = [];
    for ($i = 1; $i <= 63; $i++) {
        $setClauses[] = "`$i` = ?";
        $params[] = $winners[$i] ?? '';
    }
    $params[] = 2; // WHERE id=2
    $sql = "UPDATE master SET " . implode(", ", $setClauses) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    echo "<p style='color:#22c55e; font-weight:bold;'>Updated $updatedCount game(s) in master bracket.</p>";
    echo "<p><a href='score.php'>Score Brackets Now &rarr;</a></p>";
} else {
    echo "<p>No updates needed — no new results matched pending bracket games.</p>";
}

if (!empty($log)) {
    echo '<div style="background:rgba(0,0,0,0.3); padding:1rem; border-radius:8px; margin-top:1rem; font-family:monospace; font-size:0.85rem;">';
    echo '<strong>Details:</strong><br>';
    foreach ($log as $entry) {
        echo h($entry) . '<br>';
    }
    echo '</div>';
}

echo '<p style="margin-top:1.5rem;"><a href="index.php" class="btn-outline">&larr; Back to Admin</a></p>';
echo '</div></div></div>';
include('footer.php');
echo '</body>
</html>';


