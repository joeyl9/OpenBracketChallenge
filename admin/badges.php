<?php
class BadgeManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // installBadges removed (handled by SQL script)


    public function awardBadges($bracketId) {
        // Fetch Bracket Data
        $stmt = $this->db->prepare("SELECT * FROM brackets WHERE id = ?");
        $stmt->execute([$bracketId]);
        $bracket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bracket) return;

        // Fetch Meta for Deadline
        $metaQ = $this->db->query("SELECT deadline FROM meta WHERE id=1");
        $meta = $metaQ->fetch(PDO::FETCH_ASSOC);

        // --- Logic: Early Bird (id=1) ---
        // Submitted > 48 hours before deadline
        if (!empty($meta['deadline']) && !empty($bracket['created_at'])) {
            $deadline = new DateTime($meta['deadline']);
            $submitted = new DateTime($bracket['created_at']);
            
            // Calculate 48 hours before deadline
            $earlyLimit = clone $deadline;
            $earlyLimit->modify('-48 hours');
            
            if ($submitted < $earlyLimit) {
                $this->grant($bracketId, 'Early Bird');
            }
        }
        
        // --- Logic: Dark Horse (id=2) ---
        // Champion is at index 63
        $champName = $bracket['63'];
        // Need seed map
        $teamQ = $this->db->query("SELECT * FROM master WHERE id=1");
        $teams = $teamQ->fetch(PDO::FETCH_ASSOC);
        $seedQ = $this->db->query("SELECT * FROM master WHERE id=4");
        $seeds = $seedQ->fetch(PDO::FETCH_ASSOC);

        // Build simple name->seed map
        $nameToSeed = [];
        for($i=1; $i<=64; $i++) {
            if(isset($teams[$i]) && isset($seeds[$i])) {
                $nameToSeed[$teams[$i]] = $seeds[$i];
            }
        }

        if (isset($nameToSeed[$champName]) && $nameToSeed[$champName] > 4) {
             $this->grant($bracketId, 'Dark Horse');
        }

        // --- Logic: Chalk (id=3) ---
        // Final Four are winners of games 57, 58, 59, 60
        $f4_1 = $bracket['57']; 
        $f4_2 = $bracket['58'];
        $f4_3 = $bracket['59'];
        $f4_4 = $bracket['60'];
        
        $f4_teams = [$f4_1, $f4_2, $f4_3, $f4_4];

        // Chalk Check (All 1 seeds)
        if (
            isset($nameToSeed[$f4_1]) && $nameToSeed[$f4_1] == 1 &&
            isset($nameToSeed[$f4_2]) && $nameToSeed[$f4_2] == 1 &&
            isset($nameToSeed[$f4_3]) && $nameToSeed[$f4_3] == 1 &&
            isset($nameToSeed[$f4_4]) && $nameToSeed[$f4_4] == 1
        ) {
            $this->grant($bracketId, 'Chalk');
        }

        // --- Logic: Cinderella Story (id=7) ---
        // Any seed >= 10 in Final Four
        $hasCinderella = false;
        foreach($f4_teams as $team) {
            if(isset($nameToSeed[$team]) && $nameToSeed[$team] >= 10) {
                 $hasCinderella = true;
                 break;
            }
        }
        if($hasCinderella) {
            $this->grant($bracketId, 'Cinderella Story');
        }

        // --- Logic: Giant Killer (id=4) ---
        // Picked a 16 seed to win a game
        // --- Logic: Upset City (id=5) ---
        // Picked a 13+ seed to win a game
        $hasGiantKiller = false;
        $hasUpset = false;
        
        for($i=1; $i<=32; $i++) {
            $winner = $bracket[$i];
            if (isset($nameToSeed[$winner])) {
                $seed = $nameToSeed[$winner];
                if ($seed >= 13) $hasUpset = true;
                if ($seed == 16) $hasGiantKiller = true;
            }
        }
        
        if ($hasGiantKiller) {
             $this->grant($bracketId, 'Giant Killer');
        }
        if ($hasUpset) {
            $this->grant($bracketId, 'Upset City');
        }
        
        // --- Logic: Underdog Lover (id=8) ---
        // Picked 5+ upsets in Round 1
        $upsetCount = 0;
        for($i=1; $i<=32; $i++) {
            $winner = $bracket[$i];
            // Upset definition: Lower seed (higher number) beats Higher seed (lower number)
            // Need game matchup info. For simplicty, R1 games are structured.
             $gameId = $i;
             // Calculate opponent seeds
             // $t1_idx = ($gameId * 2) - 1; $t2_idx = $gameId * 2;
             // Logic is complex without re-querying structure.
             // Simplified: Just count high seeds winning.
             if (isset($nameToSeed[$winner]) && $nameToSeed[$winner] >= 9) { // 9 beats 8 is simplest upset
                 $upsetCount++;
             }
        }
        if($upsetCount >= 5) {
            $this->grant($bracketId, 'Underdog Lover');
        }
    }

    public function awardEndGameBadges($bracketId) {
        // Fetch Bracket Data
        $stmt = $this->db->prepare("SELECT * FROM brackets WHERE id = ?");
        $stmt->execute([$bracketId]);
        $bracket = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$bracket) return;

        // Fetch User Email safely from users table
        $userQ = $this->db->prepare("SELECT email FROM users WHERE id = ?");
        $userQ->execute([$bracket['user_id']]);
        $user = $userQ->fetch(PDO::FETCH_ASSOC);
        $email = $user ? $user['email'] : $bracket['email']; // Fallback

        $userChamp = $bracket['63'];

        // Fetch Master Bracket
        $mStmt = $this->db->query("SELECT * FROM master WHERE id = 1"); 
        $master = $mStmt->fetch(PDO::FETCH_ASSOC);

        // Fetch Team Seeds
        $teamQ = $this->db->query("SELECT * FROM master WHERE id=1");
        $teams = $teamQ->fetch(PDO::FETCH_ASSOC);
        $seedQ = $this->db->query("SELECT * FROM master WHERE id=4");
        $seeds = $seedQ->fetch(PDO::FETCH_ASSOC);
        
        $nameToSeed = [];
        for($i=1; $i<=64; $i++) {
            if(isset($teams[$i]) && isset($seeds[$i])) {
                $nameToSeed[$teams[$i]] = $seeds[$i];
            }
        }

        // --- Logic: Heartbreak (id=9) ---
        // Champion eliminated in Round 1
        // Check Master Losers (id=3) for games 1-32
        $loserQ = $this->db->query("SELECT * FROM master WHERE id=3");
        $losers = $loserQ->fetch(PDO::FETCH_ASSOC);
        
        $isHeartbreak = false;
        if($losers) {
            for($g=1; $g<=32; $g++) {
                if(isset($losers[$g]) && $losers[$g] == $userChamp) {
                    $isHeartbreak = true;
                    break;
                }
            }
        }
        
        if($isHeartbreak) {
            $this->grant($bracketId, 'Heartbreak');
        }
        
        // --- Logic: Crystal Ball (id=10) ---
        // Correctly predicted entire Final Four
        // F4 are winners of 57, 58, 59, 60
        if(
            $bracket['57'] == $master['57'] &&
            $bracket['58'] == $master['58'] &&
            $bracket['59'] == $master['59'] &&
            $bracket['60'] == $master['60'] &&
            !empty($master['60'])
        ) {
            $this->grant($bracketId, 'Crystal Ball');
        }

        // --- Logic: Close But No Cigar (id=11) ---
        // Champion lost in Final Game (63)
        // Means UserChamp was Runner Up in Master.
        // Master Runner Up is the loser of Game 63.
        // Who played in 63? Winners of 61 and 62.
        $mF1 = $master['61'];
        $mF2 = $master['62'];
        $mChamp = $master['63'];
        $mRunnerUp = ($mF1 == $mChamp) ? $mF2 : $mF1;
        
        if($userChamp == $mRunnerUp && !empty($mChamp)) {
            $this->grant($bracketId, 'Close But No Cigar');
        }

        // --- Logic: Back-to-Back (id=12) ---
        // Won last year.
        // Check historical_results for email + year-1 + rank=1
        $year = date("Y"); // or from meta settings
        $prevYear = $year - 1;
        
        $hfStmt = $this->db->prepare("SELECT id FROM historical_results WHERE email = ? AND year = ? AND rank = 1");
        $hfStmt->execute([$email, $prevYear]);
        if($hfStmt->fetch()) {
            $this->grant($bracketId, 'Back-to-Back');
        }

        // --- Logic: Money Badges (Lifetime) ---
        // High Roller (> $500 Lifetime Earnings)
        $moneyStmt = $this->db->prepare("SELECT SUM(earnings) FROM historical_results WHERE email = ?");
        $moneyStmt->execute([$email]);
        $totalMoney = $moneyStmt->fetchColumn();
        
        if ($totalMoney >= 500) {
            $this->grant($bracketId, 'High Roller');
        }
        
        // Money Bags (Top 3 in 3+ Seasons)
        $top3Stmt = $this->db->prepare("SELECT COUNT(*) FROM historical_results WHERE email = ? AND rank <= 3");
        $top3Stmt->execute([$email]);
        $top3Count = $top3Stmt->fetchColumn();
        
        if ($top3Count >= 3) {
            $this->grant($bracketId, 'Money Bags');
        }
    }

    private function grant($bracketId, $badgeName) {
        $bStmt = $this->db->prepare("SELECT id FROM badges WHERE name = ?");
        $bStmt->execute([$badgeName]);
        $badge = $bStmt->fetch();

        if ($badge) {
            $ins = $this->db->prepare("INSERT IGNORE INTO bracket_badges (bracket_id, badge_id) VALUES (?, ?)");
            $ins->execute([$bracketId, $badge['id']]);
        }
    }
    
    public function getBadges($bracketId) {
        $stmt = $this->db->prepare("
            SELECT b.* 
            FROM badges b
            JOIN bracket_badges bb ON b.id = bb.badge_id
            WHERE bb.bracket_id = ?
        ");
        $stmt->execute([$bracketId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
