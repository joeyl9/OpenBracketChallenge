<div class="nav">
<?php
// Helper for rankings
if (!function_exists('getRanksForScores')) {
    function getRanksForScores( $scoreData ) {
        $rankCounter = 1;
        $currentScore = -1;
        $rankMap = [];
        foreach( $scoreData as $scoreInfo ) {
            if( $currentScore != $scoreInfo['score'] ) {
                $rank = $rankCounter;
            }
            $currentScore = $scoreInfo['score'];
            $rankMap[ $scoreInfo['id'] ] = $rank;
            $rankCounter += 1;
        }
        return $rankMap;
    }
}

// Check if tournament is closed
$closedQuery = "SELECT closed FROM `meta` WHERE id=1 LIMIT 1";
$c_stmt = $db->query($closedQuery);
$c_res = $c_stmt->fetch(PDO::FETCH_ASSOC);
$is_closed = ($c_res['closed'] == 1);

// WIDGET 1: LIVE CHATTER (Moved from index.php)
?>
<?php
// TOGGLE CONTROL (Moved to Top)
$showSweet16 = (!empty($meta['sweet16Competition'])); 
?>

<?php if($showSweet16): ?>
<!-- Toggle Container: Full Width, Right Aligned -->
<div style="width:100%; grid-column: 1 / -1; display:flex; justify-content:flex-end; margin-bottom:10px; height:fit-content;">
    <!-- Toggle Pill: Smaller Size -->
    <div style="background:rgba(255,255,255,0.05); padding:3px; border-radius:20px; display:flex; align-items:center; gap:2px;">
        <div onclick="toggleSidebar('main')" id="sb-btn-main" style="padding:4px 12px; border-radius:15px; cursor:pointer; font-weight:bold; font-size:0.75em; transition:all 0.2s; background:var(--accent-orange); color:white;">Main</div>
        <div onclick="toggleSidebar('sweet16')" id="sb-btn-s16" style="padding:4px 12px; border-radius:15px; cursor:pointer; font-weight:bold; font-size:0.75em; transition:all 0.2s; color:var(--text-muted);">Second Chance</div>
    </div>
</div>

<script>
function toggleSidebar(view) {
    if(view === 'main') {
        $('#sidebar-main').css('display', 'contents');
        $('#sidebar-sweet16').hide();
        $('#sb-btn-main').css({background:'var(--accent-orange)', color:'white'});
        $('#sb-btn-s16').css({background:'transparent', color:'var(--text-muted)'});
    } else {
        $('#sidebar-main').hide();
        $('#sidebar-sweet16').css('display', 'contents');
        $('#sb-btn-s16').css({background:'var(--accent-orange)', color:'white'});
        $('#sb-btn-main').css({background:'transparent', color:'var(--text-muted)'});
    }
}
</script>
<?php endif; ?>

<div class="sidebar-card">
    <h3 class="sidebar-header"><i class="fa-solid fa-comments"></i> Live Chatter <span style="font-size:0.5em; color:#22c55e; vertical-align:middle; display:inline-block; animation: pulse 2s infinite;">●</span></h3>
    <div id="live-chatter-sidebar">
        <?php
        $tickerQuery = "SELECT c.content, c.from, b.name, c.time, c.bracket FROM `comments` c, `brackets` b WHERE b.id = c.bracket ORDER BY c.time DESC LIMIT 5";
        $tickerData = $db->query($tickerQuery);
        $hasMessages = false;
        
        while ($t = $tickerData->fetch(PDO::FETCH_ASSOC)) {
            $hasMessages = true;
            echo "<div class='chat-bubble'>";
            echo "<div style='font-size:0.85em; margin-bottom:2px;'><strong style='color:var(--text-light);'>" . h(stripslashes($t['from'])) . "</strong></div>";
            echo "<a href='view.php?id=".$t['bracket']."#comments' style='color:var(--text-muted); text-decoration:none; display:block; font-size:0.9em; line-height:1.4;'> " . h(substr(stripslashes($t['content']), 0, 80)) . "...</a>";
            echo "</div>";
        }
        
        if (!$hasMessages) {
            echo "<div style='color:var(--text-muted); font-style:italic; padding:10px; text-align:center;'>No smack talk yet...</div>";
        }
        ?>
    </div>
    <div style="text-align:center; margin-top:10px;">
        <a href="#" onclick="location.reload(); return false;" style="font-size:0.8em; color:var(--text-light);"><i class="fa-solid fa-rotate"></i> Refresh</a>
    </div>
</div>



<!-- MAIN TOURNAMENT CONTAINER -->
<div id="sidebar-main" style="display:contents">
    <?php
    // WIDGET 2: STANDINGS (MAIN)
    $top = 5;
    ?>
    <div class="sidebar-card">
        <h3 class="sidebar-header"><i class="fa-solid fa-trophy"></i> Top 5 Standings</h3>
        <?php
        if($is_closed) {
            $query = 'SELECT * FROM `scores` WHERE `scoring_type`="main" ORDER BY `score` DESC, `name` ASC LIMIT 10'; // Optimization: Limit query
            $stmt = $db->query($query);  
            $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if( !empty($scores) ) {
                $rankMap = getRanksForScores( $scores );
                $commentMap = getCommentsMap($db);
                
                echo "<ul class='leaderboard-list'>";
                foreach( $scores as $score ) {
                    $rank = $rankMap[$score['id']];
                    if( $rank > $top ) break;
                    
                    $id = $score['id'];
                    $name = stripslashes($score['name']);
                    $rankClass = ($rank <= 3) ? 'top-3' : '';
                    
                    echo "<li class='leaderboard-row'>";
                    echo "<div class='leaderboard-rank $rankClass'>$rank</div>";
                    echo "<div class='leaderboard-user'>";
                    echo "<a href=\"view.php?id=$id\" style='color:var(--text-light); text-decoration:none;'>" . h($name) . "</a>";
                    if (isset($commentMap[$score['id']]) && $commentMap[$score['id']] > 0) {
                         echo " <span style='font-size:0.7em; color:var(--accent-orange); margin-left:5px;'><i class='fa-solid fa-comment'></i></span>";
                    }
                    echo "</div>";
                    echo "</li>";
                }
                echo "</ul>";
                echo "<div style='margin-top:15px; text-align:center;'>";
                echo "<a href=\"standings.php?type=normal\" style='display:inline-block; padding:8px 16px; background:var(--accent-orange); color:var(--accent-text); border-radius:4px; text-decoration:none; font-size:0.85em; font-weight:bold; transition:opacity 0.2s;'>View Full Standings <i class='fa-solid fa-arrow-right' style='font-size:0.8em; margin-left:5px;'></i></a>";
                echo "</div>";
            } else {
                echo "<p style='text-align:center; color:var(--text-muted); padding:10px;'>Waiting for game results...</p>";
            }
        } else {
            echo "<p style='text-align:center; color:var(--text-muted); padding:10px;'>Standings will appear here once the tournament starts.</p>";
        }
        ?>
    </div>

    <?php
    // WIDGET 3: SITE STATS (MAIN)
    // Calc Main Stats
    $entries = $db->query("SELECT COUNT(id) FROM `brackets` WHERE type!='sweet16'")->fetchColumn();
    $paidentries = $db->query("SELECT COUNT(id) FROM `brackets` WHERE `paid`=1 AND type!='sweet16'")->fetchColumn();
    $participants = $db->query("SELECT COUNT(DISTINCT email) FROM `brackets` WHERE type!='sweet16'")->fetchColumn();

    // Pot Calculation
    $query = "SELECT cost,cut,cutType,payout_1,payout_2,payout_3,refund_last FROM `meta` WHERE id=1";
    $pot = $db->query($query)->fetch(PDO::FETCH_ASSOC);
    $totalPot = 0;

    if($is_closed && $pot['cost'] != 0) {     
        if( $meta['cutType'] == 0 )
        {
            $totalPot = ( $paidentries * $meta['cost'] ) - $meta['cut'];
        }
        else
        {
            $totalPot = ( $paidentries * $meta['cost'] ) * ( (100 - $meta['cut'])/100 );
        }
        

    }
    ?>

    <div class="sidebar-card">
        <h3 class="sidebar-header"><i class="fa-solid fa-chart-pie"></i> Tournament Stats</h3>
        <div class="stat-grid">
            <div class="stat-item">
                <span class="stat-value"><?php echo $participants; ?></span>
                <span class="stat-label">Players</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo $entries; ?></span>
                <span class="stat-label">Brackets</span>
            </div>
        </div>
        
        <?php if($is_closed && $pot['cost'] != 0): ?>
        <div style="margin-top:1rem; background:rgba(6, 78, 59, 0.4); padding:1rem; border-radius:6px; border:1px solid rgba(16, 185, 129, 0.2); text-align:center;">
            <span class="stat-label" style="color:#6ee7b7;">Total Pot</span>
            <span class="stat-value" style="font-size:1.5rem; color:#d1fae5; margin-top:5px;">$<?php echo number_format($totalPot, 2); ?></span>
            <div style="font-size:0.8rem; color:#a7f3d0; margin-top:5px;">
                 (<?php echo $paidentries; ?> paid entries)
            </div>
        </div>
        
        <div style="margin-top:1rem;">
            <div style="font-size:0.85rem; color:var(--text-light); margin-bottom:5px; text-transform:uppercase; letter-spacing:0.05em; font-weight:bold;">Projected Payouts</div>
            <ul style="list-style:none; padding:0; margin:0; font-size:0.9rem;">
                <?php
                // Recalculate specific payouts
                 $netPot = $totalPot;
                 // Refund Logic
                 if($pot['refund_last'] && $paidentries > 0) {
                     $netPot -= $pot['cost'];
                     echo "<li style='display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid rgba(255,255,255,0.05);'>";
                     echo "<span>Last Place (Refund)</span><span>$".number_format($pot['cost'], 2)."</span></li>";
                 }
                 
                 $p1_amt = $netPot * ($pot['payout_1'] / 100);
                 $p2_amt = $netPot * ($pot['payout_2'] / 100);
                 $p3_amt = $netPot * ($pot['payout_3'] / 100);
                 
                 echo "<li style='display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid rgba(255,255,255,0.05); color:var(--accent-orange); font-weight:bold;'>";
                 echo "<span>1st Place</span><span>$".number_format($p1_amt, 2)."</span></li>";
                 
                 if($p2_amt > 0) {
                     echo "<li style='display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid rgba(255,255,255,0.05);'>";
                     echo "<span>2nd Place</span><span>$".number_format($p2_amt, 2)."</span></li>";
                 }
                 if($p3_amt > 0) {
                     echo "<li style='display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid rgba(255,255,255,0.05);'>";
                     echo "<span>3rd Place</span><span>$".number_format($p3_amt, 2)."</span></li>";
                 }
                ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <?php
    // WIDGET 4: FAVORITES (MAIN)
    if($is_closed && $entries != 0):
    ?>
    <div class="sidebar-card">
        <h3 class="sidebar-header"><i class="fa-solid fa-heart"></i> Fan Favorites</h3>
        <?php
        $query = "SELECT `63`, COUNT(*) AS `quantity` FROM `brackets` WHERE type!='sweet16' GROUP BY `63` ORDER BY `quantity` DESC LIMIT 5";
        $stmt = $db->query($query);
        
        while( $favorite = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            $percent = round($favorite['quantity']/$entries*100, 1);
            $teamName = $favorite['63'];
            ?>
            <div class="favorite-team-row">
                <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                    <a href='picks.php?team=<?php echo urlencode($teamName); ?>' style="color:var(--text-light); text-decoration:none;"><?php echo htmlspecialchars($teamName); ?></a>
                    <span style="color:var(--text-light);"><?php echo $percent; ?>%</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width:<?php echo $percent; ?>%;"></div>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
    <?php endif; ?>
</div>

<!-- SECOND CHANCE CONTAINER -->
<?php if($showSweet16): ?>
<div id="sidebar-sweet16" style="display:none;">
    <?php
    // WIDGET 2: STANDINGS (SECOND CHANCE)
    // Uses scoring_type = 'sweet16'
    ?>
    <div class="sidebar-card">
        <h3 class="sidebar-header"><i class="fa-solid fa-trophy"></i> Second Chance Standings</h3>
        <?php
        if($is_closed) {
            // Fix: Join with brackets table to filter by type='sweet16', assuming scoring_type='main' (Standard Scoring)
            $query = 'SELECT s.* FROM `scores` s 
                      JOIN `brackets` b ON s.id = b.id 
                      WHERE s.scoring_type="main" AND b.type="sweet16" 
                      ORDER BY s.score DESC, s.name ASC LIMIT 10';
            $stmt = $db->query($query);  
            $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if( !empty($scores) ) {
                $rankMap = getRanksForScores( $scores );
                $commentMap = getCommentsMap($db);
                
                echo "<ul class='leaderboard-list'>";
                $rowCount = 0;
                foreach( $scores as $score ) {
                    $rank = $rankMap[$score['id']];
                    if( $rowCount >= 5 ) break; // Strict Limit: 5 rows
                    $rowCount++;
                    
                    $id = $score['id'];
                    $name = stripslashes($score['name']);
                    $rankClass = ($rank <= 3) ? 'top-3' : '';
                    
                    echo "<li class='leaderboard-row'>";
                    echo "<div class='leaderboard-rank $rankClass'>$rank</div>";
                    echo "<div class='leaderboard-user'>";
                    echo "<a href=\"view.php?id=$id\" style='color:var(--text-light); text-decoration:none;'>" . h($name) . "</a>";
                     if (isset($commentMap[$score['id']]) && $commentMap[$score['id']] > 0) {
                         echo " <span style='font-size:0.7em; color:var(--accent-orange); margin-left:5px;'><i class='fa-solid fa-comment'></i></span>";
                    }
                    echo "</div>";
                    echo "</li>";
                }
                echo "</ul>";
                echo "<div style='margin-top:15px; text-align:center;'>";
                echo "<a href=\"standings.php?type=normal&view=sweet16\" style='display:inline-block; padding:8px 16px; background:var(--accent-orange); color:var(--accent-text); border-radius:4px; text-decoration:none; font-size:0.85em; font-weight:bold; transition:opacity 0.2s;'>View Full Standings <i class='fa-solid fa-arrow-right' style='font-size:0.8em; margin-left:5px;'></i></a>";
                echo "</div>";
            } else {
                echo "<p style='text-align:center; color:var(--text-muted); padding:10px;'>Waiting for Second Chance results...</p>";
            }
        } else {
            echo "<p style='text-align:center; color:var(--text-muted); padding:10px;'>Standings will appear here after Round 2.</p>";
        }
        ?>
    </div>

    <?php
    // WIDGET 3: SITE STATS (SWEET 16)
    $s16_entries = $db->query("SELECT COUNT(id) FROM `brackets` WHERE type='sweet16'")->fetchColumn();
    $s16_paid = $db->query("SELECT COUNT(id) FROM `brackets` WHERE `paid`=1 AND type='sweet16'")->fetchColumn();
    $s16_parts = $db->query("SELECT COUNT(DISTINCT email) FROM `brackets` WHERE type='sweet16'")->fetchColumn();

    // Second Chance Pot
    $s16_cost = $meta['sweet16_cost'] ?? 0;
    $s16_cut = $meta['sweet16_cut'] ?? 0;
    $s16_cutType = $meta['sweet16_cutType'] ?? 0;
    $s16_p1 = $meta['sweet16_payout_1'] ?? 100;
    $s16_p2 = $meta['sweet16_payout_2'] ?? 0;
    $s16_p3 = $meta['sweet16_payout_3'] ?? 0;

    $s16_totalPot = 0;
    if($is_closed && $s16_cost != 0) {
        if($s16_cutType == 1) { // %
             $cut = (100-$s16_cut)/100;
             $s16_totalPot = round($s16_paid*$s16_cost*$cut, 2);
        } else {
             $s16_totalPot = $s16_paid*$s16_cost-$s16_cut;
        }
    }
    ?>

    <div class="sidebar-card">
        <h3 class="sidebar-header"><i class="fa-solid fa-chart-pie"></i> Second Chance Stats</h3>
        <div class="stat-grid">
            <div class="stat-item">
                <span class="stat-value"><?php echo $s16_parts; ?></span>
                <span class="stat-label">Players</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo $s16_entries; ?></span>
                <span class="stat-label">Brackets</span>
            </div>
        </div>
        
        <?php if($is_closed && $s16_cost != 0): ?>
        <div style="margin-top:1rem; background:rgba(6, 78, 59, 0.4); padding:1rem; border-radius:6px; border:1px solid rgba(16, 185, 129, 0.2); text-align:center;">
             <span class="stat-label" style="color:#6ee7b7;">Second Chance Pot</span>
            <span class="stat-value" style="font-size:1.5rem; color:#d1fae5; margin-top:5px;">$<?php echo number_format($s16_totalPot, 2); ?></span>
        </div>
        
        <div style="margin-top:1rem;">
             <div style="font-size:0.85rem; color:var(--text-light); margin-bottom:5px; text-transform:uppercase; letter-spacing:0.05em; font-weight:bold;">Projected Payouts</div>
             <ul style="list-style:none; padding:0; margin:0; font-size:0.9rem;">
                 <?php
                 $p1_amt = $s16_totalPot * ($s16_p1 / 100);
                 $p2_amt = $s16_totalPot * ($s16_p2 / 100);
                 
                 echo "<li style='display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid rgba(255,255,255,0.05); color:var(--accent-orange); font-weight:bold;'>";
                 echo "<span>1st Place</span><span>$".number_format($p1_amt, 2)."</span></li>";
                 
                 if($p2_amt > 0) {
                     echo "<li style='display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid rgba(255,255,255,0.05);'>";
                     echo "<span>2nd Place</span><span>$".number_format($p2_amt, 2)."</span></li>";
                 }
                 ?>
             </ul>
        </div>
        <?php endif; ?>
    </div>
    
    <?php
    // WIDGET 4: FAVORITES (SECOND CHANCE)
    if($is_closed && $s16_entries != 0):
    ?>
    <div class="sidebar-card">
         <h3 class="sidebar-header"><i class="fa-solid fa-heart"></i> Second Chance Favorites</h3>
         <?php
         $query = "SELECT `63`, COUNT(*) AS `quantity` FROM `brackets` WHERE type='sweet16' GROUP BY `63` ORDER BY `quantity` DESC LIMIT 5";
         $stmt = $db->query($query);
         
         while( $favorite = $stmt->fetch(PDO::FETCH_ASSOC) ) {
             $percent = round($favorite['quantity']/$s16_entries*100, 1);
             $teamName = $favorite['63'];
             ?>
             <div class="favorite-team-row">
                 <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                     <a href='picks.php?team=<?php echo urlencode($teamName); ?>' style="color:var(--text-light); text-decoration:none;"><?php echo htmlspecialchars($teamName); ?></a>
                     <span style="color:var(--text-light);"><?php echo $percent; ?>%</span>
                 </div>
                 <div class="progress-bar-bg">
                     <div class="progress-bar-fill" style="width:<?php echo $percent; ?>%;"></div>
                 </div>
             </div>
             <?php
         }
         ?>
    </div>
    <?php endif; ?>

</div>
<?php endif; ?>



<style>
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.4; }
    100% { opacity: 1; }
}
</style>

</div>
