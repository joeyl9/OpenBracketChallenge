<?php
/**
 * cron_live_update.php
 * ---------------------
 * CLI-only script that:
 *   1. Resolves any "Team A or Team B" First Four placeholder slots
 *   2. Fetches completed games from the ESPN API
 *   3. Updates the master bracket winners table
 *   4. Re-scores all user brackets
 *
 * USAGE (from your server, inside the admin/ directory):
 *   php cron_live_update.php
 *
 * CRON EXAMPLE (every 5 minutes during tournament hours):
 *   * /5 * * * * php /path/to/your/site/admin/cron_live_update.php >> /path/to/logs/live_update.log 2>&1
 *
 * SECURITY: This script refuses to run over HTTP. It is CLI-only.
 */

// -------------------------------------------------------
// 0. CLI Guard — hard refuse if called over HTTP
// -------------------------------------------------------
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Forbidden: This script may only be run from the command line.\n");
}

define('CRON_MODE', true);
$startTime = microtime(true);

function clog(string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}

// -------------------------------------------------------
// Bootstrap
// -------------------------------------------------------
$adminDir = __DIR__;
require_once $adminDir . '/database.php';
require_once $adminDir . '/functions.php';

// -------------------------------------------------------
// 1. Check the live-scoring toggle in the DB
// -------------------------------------------------------
$stmt = $db->query("SELECT use_live_scoring FROM meta WHERE id=1");
$metaRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($metaRow['use_live_scoring'])) {
    clog("Live Scoring is DISABLED in Settings. Exiting.");
    exit(0);
}

clog("Live scoring is enabled. Fetching ESPN data...");

// -------------------------------------------------------
// 2. Fetch Data (ESPN Public API)
// -------------------------------------------------------
$apiUrl = 'https://site.api.espn.com/apis/site/v2/sports/basketball/mens-college-basketball/scoreboard?limit=100&groups=100';

$json = @file_get_contents($apiUrl);
if ($json === false && function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'TourneyBracket/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $json = curl_exec($ch);
    curl_close($ch);
}

if (empty($json)) {
    clog("ERROR: Could not retrieve data from ESPN API. Exiting.");
    exit(1);
}

// Parse completed games into matchup pairs
$completedGames = [];
$data = json_decode($json, true);
if (isset($data['events'])) {
    foreach ($data['events'] as $event) {
        $status = $event['status']['type']['state'] ?? '';
        if ($status !== 'post') continue;

        $winnerNames = [];
        $loserNames  = [];
        foreach ($event['competitions'][0]['competitors'] as $competitor) {
            $names = [];
            if (!empty($competitor['team']['displayName']))      $names[] = $competitor['team']['displayName'];
            if (!empty($competitor['team']['shortDisplayName']))  $names[] = $competitor['team']['shortDisplayName'];
            if (!empty($competitor['team']['name']))             $names[] = $competitor['team']['name'];
            if (!empty($competitor['team']['nickname']))         $names[] = $competitor['team']['nickname'];
            if (!empty($competitor['team']['location']))         $names[] = $competitor['team']['location'];

            if (isset($competitor['winner']) && $competitor['winner'] === true) {
                $winnerNames = $names;
            } else {
                $loserNames = $names;
            }
        }
        if (!empty($winnerNames) && !empty($loserNames)) {
            $completedGames[] = ['winner' => $winnerNames, 'loser' => $loserNames];
        }
    }
}

clog("Found " . count($completedGames) . " completed game(s) in ESPN feed.");

if (empty($completedGames)) {
    clog("No completed games to process. Exiting.");
    exit(0);
}

// -------------------------------------------------------
// 3. Load Master Bracket
// -------------------------------------------------------
$masterQ = $db->query("SELECT * FROM master WHERE id=1");
$teams   = $masterQ->fetch(PDO::FETCH_ASSOC);

$masterWinQ = $db->query("SELECT * FROM master WHERE id=2");
$winners    = $masterWinQ->fetch(PDO::FETCH_ASSOC);
if (!$winners) {
    $db->exec("INSERT INTO master (id) VALUES (2)");
    $winners = array_fill(1, 63, "");
}

// -------------------------------------------------------
// Helper functions
// -------------------------------------------------------
function getFeederGames(int $gameId): ?array {
    if ($gameId <= 32) return null;
    if ($gameId <= 48)      { $base = 32; $offset = 1;  }
    elseif ($gameId <= 56)  { $base = 48; $offset = 33; }
    elseif ($gameId <= 60)  { $base = 56; $offset = 49; }
    elseif ($gameId <= 62)  { $base = 60; $offset = 57; }
    else { return [61, 62]; }
    $pos = $gameId - $base;
    $feeder1 = $offset + ($pos - 1) * 2;
    return [$feeder1, $feeder1 + 1];
}

function getTeamsForGame(int $gameId, array $teams, array $winners): array {
    if ($gameId <= 32) {
        return [trim($teams[$gameId * 2 - 1] ?? ''), trim($teams[$gameId * 2] ?? '')];
    }
    $feeders = getFeederGames($gameId);
    if (!$feeders) return ['', ''];
    return [trim($winners[$feeders[0]] ?? ''), trim($winners[$feeders[1]] ?? '')];
}

function normalizeTeamName(string $name): string {
    $name = preg_replace('/\bst\.\b/i', 'state', $name);
    $name = preg_replace('/\bst\b(?!\w)/i', 'state', $name);
    $name = preg_replace("/['\.\-]/", '', $name);
    return strtolower(trim(preg_replace('/\s+/', ' ', $name)));
}

function nameMatchesAny(string $bracketName, array $espnNames): bool {
    $bracketNorm     = normalizeTeamName(trim($bracketName));
    if (empty($bracketNorm)) return false;
    $bracketNoParens = normalizeTeamName(trim(preg_replace('/\s*\(.*?\)/', '', $bracketName)));

    foreach ($espnNames as $n) {
        $espnNorm = normalizeTeamName(trim($n));
        if ($espnNorm === $bracketNorm) return true;
        if (!empty($bracketNoParens) && $bracketNoParens !== $bracketNorm && $espnNorm === $bracketNoParens) return true;
    }
    return false;
}

// Soft match: word-overlap fallback for when hard match fails.
// "Miami (FL)" vs "Miami Hurricanes" -> "miami" overlaps -> true.
function nameSoftMatch(string $bracketName, array $espnNames): bool {
    $bracketNorm  = normalizeTeamName(trim(preg_replace('/\s*\(.*?\)/', '', $bracketName)));
    if (empty($bracketNorm)) return false;
    $bracketWords = array_filter(explode(' ', $bracketNorm), function($w) { return strlen($w) > 2; });
    if (empty($bracketWords)) return false;

    foreach ($espnNames as $n) {
        $espnWords = array_filter(explode(' ', normalizeTeamName(trim($n))), function($w) { return strlen($w) > 2; });
        if (!empty(array_intersect($bracketWords, $espnWords))) return true;
    }
    return false;
}

function getNextGame(int $currentGame): ?int {
    if ($currentGame <= 32) return 32 + (int)ceil($currentGame / 2);
    if ($currentGame <= 48) return 48 + (int)ceil(($currentGame - 32) / 2);
    if ($currentGame <= 56) return 56 + (int)ceil(($currentGame - 48) / 2);
    if ($currentGame <= 60) return 60 + (int)ceil(($currentGame - 56) / 2);
    if ($currentGame <= 62) return 63;
    return null;
}

function findActiveGame(string $teamName, array $teams, array $winners): ?int {
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
            $currentGame = getNextGame($currentGame);
            if ($currentGame === null) return null;
        } elseif (empty($existingWinner)) {
            return $currentGame;
        } else {
            return null;
        }
    }
    return null;
}

// -------------------------------------------------------
// 4. First Four Resolution
// -------------------------------------------------------
// Slots containing "Team A or Team B" are First Four play-in placeholders.
// When ESPN has the result, we resolve the slot to the actual winner and
// update master id=1 (teams table) so normal matching works correctly.
// -------------------------------------------------------
$firstFourResolved = []; // slot# => resolved team name

foreach ($teams as $slot => $slotValue) {
    if (!is_numeric($slot)) continue;
    $slotValue = trim($slotValue);

    // Only process "X or Y" placeholders
    if (stripos($slotValue, ' or ') === false) continue;

    // Split into the two candidate teams
    $candidates = array_map('trim', preg_split('/\s+or\s+/i', $slotValue, 2));
    if (count($candidates) !== 2 || empty($candidates[0]) || empty($candidates[1])) continue;

    // Find the ESPN game where both candidates appear (one winner, one loser)
    foreach ($completedGames as $game) {
        $c0winsC1loses = nameMatchesAny($candidates[0], $game['winner']) && nameMatchesAny($candidates[1], $game['loser']);
        $c1winsC0loses = nameMatchesAny($candidates[1], $game['winner']) && nameMatchesAny($candidates[0], $game['loser']);

        if (!$c0winsC1loses && !$c1winsC0loses) continue;

        // Keep the bracket's own spelling of the winning team's name
        $resolved = $c0winsC1loses ? $candidates[0] : $candidates[1];

        $firstFourResolved[(int)$slot] = $resolved;
        $teams[$slot] = $resolved; // Update in-memory so normal matching sees real name
        clog("  FIRST FOUR: Slot $slot — '$slotValue' resolved to '$resolved' ✓");
        break;
    }
}

// Persist First Four resolutions to master id=1 (teams table)
if (!empty($firstFourResolved)) {
    $ffClauses = [];
    $ffParams  = [];
    foreach ($firstFourResolved as $slot => $name) {
        $ffClauses[] = "`$slot` = ?";
        $ffParams[]  = $name;
    }
    $ffParams[] = 1; // WHERE id=1
    $ffSql  = "UPDATE master SET " . implode(", ", $ffClauses) . " WHERE id = ?";
    $ffStmt = $db->prepare($ffSql);
    $ffStmt->execute($ffParams);
    clog("Saved " . count($firstFourResolved) . " First Four resolution(s) to master teams.");
}

// -------------------------------------------------------
// 5. Match ESPN results to bracket games
// -------------------------------------------------------
$updatedCount = 0;

foreach ($completedGames as $game) {
    $winnerNames = $game['winner'];
    $loserNames  = $game['loser'];

    // Step 1: Hard-match the winner against bracket slots
    $bracketWinner = null;
    foreach ($teams as $key => $val) {
        if (!is_numeric($key)) continue;
        if (nameMatchesAny($val, $winnerNames)) { $bracketWinner = trim($val); break; }
    }
    if (!$bracketWinner) {
        clog("  SKIP: ESPN winner [" . implode(' / ', $winnerNames) . "] not found in bracket.");
        continue;
    }

    // Step 2: Find the winner's active game
    $activeGame = findActiveGame($bracketWinner, $teams, $winners);
    if ($activeGame === null) {
        clog("  SKIP: $bracketWinner — no active game (already advanced or eliminated).");
        continue;
    }

    // Step 3: Get the two bracket teams for this game
    $gamePlayers = getTeamsForGame($activeGame, $teams, $winners);
    if (empty($gamePlayers[0]) || empty($gamePlayers[1])) {
        clog("  WAIT: $bracketWinner — Game $activeGame waiting for opponent.");
        continue;
    }

    // Step 4: Identify the bracket opponent
    $bracketOpponent = (strtolower($gamePlayers[0]) === strtolower($bracketWinner))
        ? $gamePlayers[1]
        : $gamePlayers[0];

    // Step 5: Verify ESPN's loser matches the bracket opponent.
    // Hard match first; soft word-overlap fallback for e.g. "Miami (FL)" vs "Miami Hurricanes".
    $loserHardMatch = nameMatchesAny($bracketOpponent, $loserNames);
    $loserSoftMatch = !$loserHardMatch && nameSoftMatch($bracketOpponent, $loserNames);

    if (!$loserHardMatch && !$loserSoftMatch) {
        clog("  SKIP: $bracketWinner Game $activeGame — opponent '$bracketOpponent' doesn't match ESPN loser ["
           . implode(' / ', $loserNames) . "].");
        continue;
    }

    $matchNote = $loserSoftMatch ? " (soft match)" : "";
    $winners[$activeGame] = $bracketWinner;
    $updatedCount++;
    clog("  UPDATE: $bracketWinner beat $bracketOpponent in Game $activeGame ✓$matchNote");
}

// -------------------------------------------------------
// 6. Save master bracket winners if any updates were made
// -------------------------------------------------------
if ($updatedCount > 0) {
    $setClauses = [];
    $params     = [];
    for ($i = 1; $i <= 63; $i++) {
        $setClauses[] = "`$i` = ?";
        $params[]     = $winners[$i] ?? '';
    }
    $params[] = 2; // WHERE id=2
    $sql  = "UPDATE master SET " . implode(", ", $setClauses) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    clog("Saved $updatedCount updated game(s) to master bracket.");
}

// -------------------------------------------------------
// 7. Re-score brackets if anything changed
// -------------------------------------------------------
$anyChanges = $updatedCount > 0 || !empty($firstFourResolved);

if ($anyChanges) {
    clog("Re-scoring all brackets...");
    require_once $adminDir . '/score.php'; // defines run_scoring()
    run_scoring($db);
    clog("Scoring complete.");
} else {
    clog("No bracket updates needed — nothing new matched pending games.");
}

$elapsed = round(microtime(true) - $startTime, 2);
clog("Done in {$elapsed}s.");
exit(0);
