<?php
include("header.php");

$msg = "";
$error = "";

function archiveSegment($db, $year, $type, $params) {
    // Unpack Params
    $entryCost = (float)$params['cost'];
    $potCut = (float)$params['cut'];
    $cutType = (int)$params['cutType'];
    $tiebreaker = (int)$params['tiebreaker'];
    $p1_pct = (float)$params['payout_1'] / 100;
    $p2_pct = (float)$params['payout_2'] / 100;
    $p3_pct = (float)$params['payout_3'] / 100;
    
    // 1. Fetch Ranks
    // Filter by Bracket Type
    // Main: type != 'sweet16'
    // Sweet16: type = 'sweet16'
    
    if ($type === 'sweet16') {
        $query = "SELECT s.id, s.score, b.name, b.email, b.tiebreaker
                  FROM scores s
                  JOIN brackets b ON s.id = b.id
                  WHERE s.scoring_type = 'main'
                  AND b.type = 'sweet16'
                  ORDER BY s.score DESC, ABS(CAST(b.tiebreaker AS SIGNED) - ?) ASC";
    } else {
        $query = "SELECT s.id, s.score, b.name, b.email, b.tiebreaker
                  FROM scores s
                  JOIN brackets b ON s.id = b.id
                  WHERE s.scoring_type = 'main'
                  AND (b.type != 'sweet16' OR b.type IS NULL)
                  ORDER BY s.score DESC, ABS(CAST(b.tiebreaker AS SIGNED) - ?) ASC";
    }

    $stmt = $db->prepare($query);
    $stmt->execute([$tiebreaker]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = count($results);
    if($count == 0) return 0; // No brackets found

    // Calculate Pot
    $grossPot = $count * $entryCost;
    $houseTake = ($cutType == 1) ? $grossPot * ($potCut / 100) : $potCut;
    $netPot = max(0, $grossPot - $houseTake);

    $prize1 = $netPot * $p1_pct;
    $prize2 = $netPot * $p2_pct;
    $prize3 = $netPot * $p3_pct;

    // Rank Logic (Strict Tiebreaker)
    $rankedResults = [];
    $rank = 0;
    $prev_score = -1;
    $prev_diff = -1;
    $counter = 1;

    foreach($results as $row) {
        $diff = abs((int)$row['tiebreaker'] - $tiebreaker);
        if ($row['score'] != $prev_score || $diff != $prev_diff) {
            $rank = $counter;
            $prev_score = $row['score'];
            $prev_diff = $diff;
        }
        $row['calculated_rank'] = $rank;
        $rankedResults[] = $row;
        $counter++;
    }

    // Group for Payouts
    $rankGroups = [];
    foreach($rankedResults as $row) {
        $r = $row['calculated_rank'];
        if(!isset($rankGroups[$r])) $rankGroups[$r] = [];
        $rankGroups[$r][] = $row['id'];
    }

    $rankToPrize = [];
    foreach($rankGroups as $r => $ids) {
        $c = count($ids);
        $money = 0;
        for($i = 0; $i < $c; $i++) {
            $slot = $r + $i;
            if ($slot == 1) $money += $prize1;
            elseif ($slot == 2) $money += $prize2;
            elseif ($slot == 3) $money += $prize3;
        }
        $rankToPrize[$r] = ($c > 0) ? ($money / $c) : 0;
    }

    // Insert
    $inserted = 0;
    $mQ = $db->query("SELECT * FROM master WHERE id=2");
    $masterBracket = $mQ->fetch(PDO::FETCH_ASSOC);

    foreach($rankedResults as $row) {
        $earnings = $rankToPrize[$row['calculated_rank']] ?? 0;
        
        // Stats
        $chatQ = $db->prepare("SELECT COUNT(*) FROM comments WHERE bracket = ?");
        $chatQ->execute([$row['id']]);
        $chatCount = $chatQ->fetchColumn();

        $picksQ = $db->prepare("SELECT `63` FROM brackets WHERE id = ?");
        $picksQ->execute([$row['id']]);
        $userPicks = $picksQ->fetch(PDO::FETCH_ASSOC);
        $champPick = $userPicks['63'] ?? '';

        $correct = 0;
        if($masterBracket) {
            $fullBracketQ = $db->prepare("SELECT * FROM brackets WHERE id = ?");
            $fullBracketQ->execute([$row['id']]);
            $fullBracket = $fullBracketQ->fetch(PDO::FETCH_ASSOC);
            
            // Check games appropriate for type
            // Main: 1-63. Sweet16: 49-63.
            $startGame = ($type === 'sweet16') ? 49 : 1;
            
            for($g=$startGame; $g<=63; $g++) {
                if(!empty($masterBracket[$g]) && isset($fullBracket[$g]) && $fullBracket[$g] == $masterBracket[$g]) {
                    $correct++;
                }
            }
        }

        $ins = $db->prepare("INSERT INTO historical_results (email, bracket_name, year, tourney_type, `rank`, score, earnings, chat_count, champion_pick, games_correct) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $ins->execute([
                $row['email'], $row['name'], $year, $type,
                $row['calculated_rank'], $row['score'], $earnings,
                $chatCount, $champPick, $correct
            ]);
            $inserted++;
            
            $badgeQ = $db->prepare("SELECT badge_id, awarded_at FROM bracket_badges WHERE bracket_id = ?");
            $badgeQ->execute([$row['id']]);
            $badges = $badgeQ->fetchAll(PDO::FETCH_ASSOC);
            if($badges) {
                $bIns = $db->prepare("INSERT INTO historical_badges (email, badge_id, year, tourney_type, awarded_at) VALUES (?, ?, ?, ?, ?)");
                foreach($badges as $b) {
                    $bIns->execute([$row['email'], $b['badge_id'], $year, $type, $b['awarded_at']]);
                }
            }
        } catch (PDOException $e) { }
    }
    return $inserted;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();
    $year = intval($_POST['year']);
    $confirm = $_POST['confirm'];

    if ($confirm === "CONFIRM" && $year > 2000) {
        $metaQ = $db->query("SELECT * FROM meta WHERE id=1");
        $meta = $metaQ->fetch(PDO::FETCH_ASSOC);
        $tiebreaker = (int)$meta['tiebreaker'];

        // 1. Archive Main
        $mainParams = [
            'cost' => $meta['cost'], 
            'cut' => $meta['cut'], 
            'cutType' => $meta['cutType'],
            'payout_1' => $meta['payout_1'],
            'payout_2' => $meta['payout_2'],
            'payout_3' => $meta['payout_3'],
            'tiebreaker' => $tiebreaker
        ];
        $mainCount = archiveSegment($db, $year, 'main', $mainParams);

        // 2. Archive Second Chance
        $s16Params = [
            'cost' => $meta['sweet16_cost'], 
            'cut' => $meta['sweet16_cut'], 
            'cutType' => $meta['sweet16_cutType'],
            'payout_1' => $meta['sweet16_payout_1'],
            'payout_2' => $meta['sweet16_payout_2'],
            'payout_3' => $meta['sweet16_payout_3'],
            'tiebreaker' => $tiebreaker
        ];
        $s16Count = archiveSegment($db, $year, 'sweet16', $s16Params);

        $msg = "Archived $mainCount Main Brackets and $s16Count Second Chance Brackets for $year!";
        
    } else {
        $error = "Please confirm correctly and enter a valid year.";
    }
}
?>

<div id="main">
    <div class="full">
        <h2>Archive Season to History</h2>
        
        <div class="dashboard-card" style="display:block; max-width:600px; margin:0 auto; cursor:default; border-color:var(--accent-orange);">
            <div style="text-align:center; margin-bottom:20px;">
                <i class="fa-solid fa-box-archive" style="font-size:3em; color:var(--accent-orange); margin-bottom:15px;"></i>
                <p style="color:var(--text-light); line-height:1.6;">
                    <strong>Ready to wrap up?</strong><br>
                    Run this <u>ONLY</u> once at the very end of the tournament.<br>
                    It copies current standings, earnings, and badges to the History table.
                </p>
                <p style="color:var(--accent-orange); font-size:0.9em; margin-top:10px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Now supports Main & Second Chance!
                </p>
            </div>

            <?php if($msg) echo "<div style='color:#22c55e; background:rgba(34,197,94,0.1); font-weight:bold; margin-bottom:20px; padding:15px; border:1px solid #22c55e; border-radius:8px; text-align:center;'>$msg</div>"; ?>
            <?php if($error) echo "<div style='color:#ef4444; background:rgba(239,68,68,0.1); font-weight:bold; margin-bottom:20px; padding:15px; border:1px solid #ef4444; border-radius:8px; text-align:center;'>$error</div>"; ?>

            <form method="post" action="archive_season.php">
                <?php csrf_field(); ?>
                <div style="margin-bottom:20px;">
                    <label style="color:var(--text-muted); display:block; margin-bottom:8px; font-weight:bold;">Season Year</label>
                    <input type="number" name="year" value="<?php echo date("Y"); ?>" required 
                           style="width:100%; padding:12px; background:rgba(0,0,0,0.2); border:1px solid var(--border-color); color:var(--text-light); border-radius:6px; font-size:1.1em;">
                </div>

                <div style="margin-bottom:25px;">
                    <label style="color:var(--text-muted); display:block; margin-bottom:8px; font-weight:bold;">Type "CONFIRM" to proceed</label>
                    <input type="text" name="confirm" required placeholder="CONFIRM" 
                           style="width:100%; padding:12px; background:rgba(0,0,0,0.2); border:1px solid var(--border-color); color:var(--text-light); border-radius:6px; font-size:1.1em;">
                </div>

                <input type="submit" value="Archive Season Results" class="button" 
                       style="width:100%; cursor:pointer; font-weight:bold; padding:15px; font-size:1.1em; text-transform:uppercase; letter-spacing:1px; border:none; border-radius:6px;">
            </form>
        </div>

        <div style="text-align:center; margin-top:30px;">
             <a href="index.php" class="btn-outline">&larr; Back to Dashboard</a>
        </div>
    </div>
</div>

</div> 
<?php include('footer.php'); ?>
</body>
</html>


