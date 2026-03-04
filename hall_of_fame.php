<?php
include("admin/database.php");
include("admin/functions.php");

// Auth Check
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_email = isset($_SESSION['useremail']) ? $_SESSION['useremail'] : null;

include("header.php");

// If not logged in, stop here (Content is hidden below)
$champions = [];
$earnings_map = [];
$avg_ranks = [];
$my_history = [];

// Second Chance Containers
$s16_champions = [];

if ($user_id) {
    // Ensure we have email for historical lookup (Legacy FK)
    if(!$user_email) {
        $u = $db->prepare("SELECT email FROM users WHERE id = ?");
        $u->execute([$user_id]);
        $user_email = $u->fetchColumn();
    }
    
    // Fetch All Historical Data
    // Check if tourney_type exists (graceful fallback if schema not updated yet)
    try {
        $stmt = $db->query("SELECT * FROM historical_results ORDER BY year DESC, rank ASC");
        $all_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        $all_history = []; // Schema mismatch, likely need migration
    }

    foreach($all_history as $h) {
        // Default to 'main' if column missing or empty
        $type = isset($h['tourney_type']) ? $h['tourney_type'] : 'main';
        
        // 1. Champions Wall (Rank 1 for each year)
        if ($h['rank'] == 1) {
            // Use array to allow multiple champions per year (Main + S16)
            $champions[] = $h;
        }

        // 2. Leaderboards (Aggregate ALL types)
        // Earnings
        if (!isset($earnings_map[$h['email']])) {
            $earnings_map[$h['email']] = ['name' => $h['bracket_name'], 'amount' => 0, 'wins' => 0];
        }
        $earnings_map[$h['email']]['amount'] += $h['earnings'];
        if ($h['rank'] == 1) $earnings_map[$h['email']]['wins']++;
        
        // Calculate Average Rank based exclusively on Main tournament brackets.
        // Including Second Chance (S16) brackets would skew the averages due to pool size disparities.
        if($type == 'main') {
            if (!isset($rank_map[$h['email']])) {
                $rank_map[$h['email']] = ['name' => $h['bracket_name'], 'total_rank' => 0, 'years' => 0];
            }
            $rank_map[$h['email']]['total_rank'] += $h['rank'];
            $rank_map[$h['email']]['years']++;
            
            // 3. User History (For Chart - Data kept)
            if ($h['email'] == $user_email) {
                $my_history[] = $h;
            }
        }
    }

    // Sort Earnings
    uasort($earnings_map, function($a, $b) {
        return $b['amount'] <=> $a['amount']; // Desc
    });

    // Sort Avg Rank (Min 2 years)
    foreach($rank_map ?? [] as $email => $data) {
        if ($data['years'] >= 2) {
            $avg_ranks[] = [
                'name' => $data['name'],
                'avg' => round($data['total_rank'] / $data['years'], 1),
                'years' => $data['years']
            ];
        }
    }
    usort($avg_ranks, function($a, $b) {
        return $a['avg'] <=> $b['avg']; // Asc
    });
}
?>

<div id="main" class="full">
    <div class="content-card" style="width:98%; margin:0 auto;">
        <div style="border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:30px; text-align:center;">
            <h2 style="margin:0; color:var(--accent-orange);"><i class="fa-solid fa-landmark"></i> Hall of Fame</h2>
            <p style="color:var(--text-muted); margin-top:5px;">Legends never die. They just get archived.</p>
        </div>

        <!-- Section 1: Wall of Champions -->
        <?php if($user_id): ?>
        
        <h3 style="border-bottom: 2px solid var(--accent-orange); padding-bottom: 5px; margin-bottom: 20px;">🏆 Wall of Champions</h3>
        
        <?php
        // 1. Group Data by Year
        $history_by_year = [];
        $max_year = 0;
        foreach($champions as $c) {
            $y = $c['year'];
            $type = isset($c['tourney_type']) ? $c['tourney_type'] : 'main';
            if($y > $max_year) $max_year = $y;
            $history_by_year[$y][$type] = $c;
        }
        
        // 2. Separate Center (Max Year) from Wings
        $center_data = isset($history_by_year[$max_year]) ? $history_by_year[$max_year] : [];
        unset($history_by_year[$max_year]);
        
        // 3. Prepare Wing Data
        // Left Wing: ASC (Oldest -> Newest) so Newest is at the Right end
        $left_wing_data = $history_by_year;
        ksort($left_wing_data); 
        
        // Right Wing: DESC (Newest -> Oldest) so Newest is at the Left start
        $right_wing_data = $history_by_year;
        krsort($right_wing_data); 
        ?>

        <style>
            .hof-split-container {
                display: flex;
                justify-content: center;
                align-items: stretch; 
                width: 100%;
                overflow: hidden;
                position: relative;
                padding: 20px 0;
            }
            .wing-scroll {
                display: block; /* NOT FLEX - Critical for overflow fix */
                flex: 1 1 0; /* Still flexible within parent */
                min-width: 0; 
                overflow-x: auto;
                overflow-y: hidden;
                scrollbar-width: none; 
                -ms-overflow-style: none; 
                pointer-events: auto;
                direction: ltr; 
                white-space: nowrap; /* Prevent wrapping generally */
            }
            .wing-scroll::-webkit-scrollbar { 
                display: none; 
            }
            
            /* Left Wing: Align content to Right (margin-left:auto pushes block to right) */
            .left-wing .wing-track {
                margin-left: auto;
            }
            /* Right Wing: Align content to Left (default) */
            .right-wing .wing-track {
                margin-right: auto; 
            }
            
            .wing-track {
                display: inline-flex; /* Use inline-flex to wrap content tightly */
                flex-wrap: nowrap;
                width: max-content; /* Force width to content */
                gap: 20px;
                padding: 0 20px;
                vertical-align: top;
                height: 100%; /* Fill height */
                align-items: center; /* Center items vertically */
            }

            .center-anchor {
                flex: 0 0 auto; /* Fixed width, do not shrink */
                display: flex;
                gap: 20px;
                z-index: 10;
                padding: 0 10px;
                align-items: center;
                 box-shadow: 0 0 50px rgba(0,0,0,0.5);
                 background: rgba(0,0,0,0.2); 
                 border-radius: 16px;
                 backdrop-filter: blur(5px);
                 position: relative;
            }

            .hof-card {
                width: 220px; 
                flex-shrink: 0;
                border-radius: 12px; 
                padding: 20px; 
                text-align: center; 
                box-shadow: 0 4px 6px rgba(0,0,0,0.3); 
                position: relative; 
                overflow: hidden;
                box-sizing: border-box;
                transition: transform 0.2s;
                white-space: normal; /* Reset text wrap */
            }
            .hof-card:hover {
                transform: translateY(-5px);
            }
            .hof-spacer {
                width: 220px;
                flex-shrink: 0;
                border: none;
                background: transparent;
                box-shadow: none;
                visibility: hidden;
            }

            @media (max-width: 768px) {
                .hof-card, .hof-spacer { width: 180px; padding: 15px; }
                .center-anchor { gap: 10px; padding: 0 5px; }
            }
        </style>

        <div class="hof-split-container" id="hofContainer">
            <!-- LEFT WING: Older Main Champions (ASC) -->
            <div class="wing-scroll left-wing" id="wingLeft">
                <div class="wing-track">
                    <?php foreach($left_wing_data as $y => $data): ?>
                        <?php if(isset($data['main'])): $champ = $data['main']; ?>
                            <div class="hof-card" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color);">
                                <div style="font-size: 1.2rem; font-weight: bold; color: var(--text-muted); margin-bottom: 5px;"><?php echo $y; ?></div>
                                <div style="font-size: 0.75rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:5px;">Tournament Champion</div>
                                <div style="font-size: 1rem; font-weight: bold; color: var(--text-light); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($champ['bracket_name']); ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">Score: <?php echo $champ['score']; ?></div>
                            </div>
                        <?php else: ?>
                            <div class="hof-spacer"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CENTER ANCHOR -->
            <div class="center-anchor">
                <?php if(isset($center_data['main'])): $champ = $center_data['main']; ?>
                <div class="hof-card" style="background: var(--card-bg); border: 2px solid var(--accent-orange); transform: scale(1.05);">
                    <div style="font-size: 3rem; position: absolute; top: -10px; right: -10px; opacity: 0.1; color: var(--accent-orange);">🏆</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--accent-orange); margin-bottom: 5px;"><?php echo $max_year; ?></div>
                    <div style="font-size: 0.8rem; text-transform:uppercase; color:var(--text-light); margin-bottom:10px;">Tournament Champion</div>
                    <div style="font-size: 1.1rem; font-weight: bold; color: var(--text-light); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($champ['bracket_name']); ?></div>
                    <div style="font-size: 0.9rem; color: var(--text-muted);">Score: <?php echo $champ['score']; ?></div>
                    <?php if($champ['earnings'] > 0): ?>
                        <div style="margin-top: 5px; background: rgba(16, 185, 129, 0.2); color: #34d399; padding: 2px 8px; border-radius: 10px; display: inline-block; font-size: 0.8rem;">$<?php echo number_format($champ['earnings']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if(isset($center_data['sweet16'])): $champ = $center_data['sweet16']; ?>
                <div class="hof-card" style="background: rgba(255,255,255,0.05); border: 2px solid rgba(255,255,255,0.5); transform: scale(1.05);">
                     <div style="font-size: 3rem; position: absolute; top: -10px; right: -10px; opacity: 0.1; color: var(--text-light);">♛</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--text-light); margin-bottom: 5px;"><?php echo $max_year; ?></div>
                    <div style="font-size: 0.8rem; text-transform:uppercase; color:var(--text-light); margin-bottom:10px;">Second Chance Winner</div>
                    <div style="font-size: 1.1rem; font-weight: bold; color: var(--text-light); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($champ['bracket_name']); ?></div>
                    <div style="font-size: 0.9rem; color: var(--text-muted);">Score: <?php echo $champ['score']; ?></div>
                     <?php if($champ['earnings'] > 0): ?>
                        <div style="margin-top: 5px; background: rgba(16, 185, 129, 0.2); color: #34d399; padding: 2px 8px; border-radius: 10px; display: inline-block; font-size: 0.8rem;">$<?php echo number_format($champ['earnings']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT WING: Older Second Chance (DESC) -->
            <div class="wing-scroll right-wing" id="wingRight">
                 <div class="wing-track">
                    <?php foreach($right_wing_data as $y => $data): ?>
                        <?php if(isset($data['sweet16'])): $champ = $data['sweet16']; ?>
                            <div class="hof-card" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.2);">
                                <div style="font-size: 1.2rem; font-weight: bold; color: var(--text-muted); margin-bottom: 5px;"><?php echo $y; ?></div>
                                <div style="font-size: 0.75rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:5px;">Second Chance Winner</div>
                                <div style="font-size: 1rem; font-weight: bold; color: var(--text-light); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($champ['bracket_name']); ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">Score: <?php echo $champ['score']; ?></div>
                            </div>
                        <?php else: ?>
                             <div class="hof-spacer"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
            (function() {
                const left = document.getElementById('wingLeft');
                const right = document.getElementById('wingRight');
                const container = document.getElementById('hofContainer');
                
                // STATE: Current distance from center (0 = center)
                let currentDist = 0;
                let isSyncing = false;
                
                function getScrollLimits() {
                    const lMax = left.scrollWidth - left.clientWidth;
                    const rMax = right.scrollWidth - right.clientWidth;
                    // Use min() to ensure strict synchronization between the left and right wings.
                    return {
                        left: lMax,
                        right: rMax,
                        clamp: Math.min(lMax, rMax) 
                    };
                }

                // Render: Apply currentDist to both wings
                function render() {
                    isSyncing = true;
                    // Re-calc limits (e.g. resize)
                    const limits = getScrollLimits();
                    
                    // Clamp dist
                    if (currentDist > limits.clamp) currentDist = limits.clamp; 
                    
                    // Left Wing: target = leftMax - d
                    // With min() limit, currentDist <= limits.clamp <= limits.left
                    // So left.scrollLeft >= 0 always.
                    left.scrollLeft = limits.left - currentDist;
                    
                    // Right Wing: target = d
                    right.scrollLeft = currentDist;
                    
                    isSyncing = false;
                }

                // Handler for Scroll Events
                function handleScroll(e) {
                    if(isSyncing) return;
                    
                    const limits = getScrollLimits();
                    const target = e.target;
                    
                    if (target === left) {
                        // Left Wing logic: d = leftMax - scrollLeft
                        currentDist = limits.left - left.scrollLeft;
                    } else if (target === right) {
                        // Right Wing logic: d = scrollLeft
                        currentDist = right.scrollLeft;
                    }
                    
                    // Ensure non-negative
                    currentDist = Math.max(0, currentDist);
                    
                    // Sync the OTHER wing
                    render();
                }

                left.addEventListener('scroll', handleScroll);
                right.addEventListener('scroll', handleScroll);

                // Handler for Wheel (Horizontal Scroll)
                function handleWheel(e) {
                    // Capture vertical scroll (wheel) when hovering the champions container,
                    // translating it into ho
                    
                    if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                        const limits = getScrollLimits();
                        const SCROLL_SPEED = 1.5;
                        const delta = e.deltaY * SCROLL_SPEED;
                        
                        // Predict new dist
                        let newDist = currentDist + delta;
                        
                        // Check bounds
                        if (newDist < 0) newDist = 0;
                        if (newDist > limits.clamp) newDist = limits.clamp;
                        
                        // Prevent default scrolling only if the wings are actively moving within bounds.
                        if (newDist !== currentDist) {
                            e.preventDefault();
                            currentDist = newDist;
                            render();
                        }
                    }
                }
                
                // Attach wheel listener to container
                container.addEventListener('wheel', handleWheel, { passive: false });

                // Init Position (Nearest to center)
                function init() {
                    currentDist = 0;
                    render();
                }
                
                window.addEventListener('load', init);
                window.addEventListener('resize', init);
                // Slight delay to ensure paint
                setTimeout(init, 200);
            })();
        </script>

        <!-- Section 2: Leaderboards -->
        <style>
            .hof-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 40px;
                margin-bottom: 40px;
            }
            @media (min-width: 768px) {
                .hof-grid {
                    grid-template-columns: 1fr 1fr;
                }
            }
        </style>
        <div class="hof-grid">
            <!-- Money List -->
            <div>
                <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 5px;">💰 All-Time Earnings</h3>
                <div class="table-container">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="color: var(--text-muted); border-bottom: 1px solid #444;">
                            <th style="padding: 10px; text-align: left;">Name</th>
                            <th style="padding: 10px; text-align: center;">Wins</th>
                            <th style="padding: 10px; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 0;
                        foreach($earnings_map as $email => $data): 
                            if ($i >= 10) break;
                            if ($data['amount'] == 0) continue;
                        ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 10px;"><?php echo htmlspecialchars($data['name']); ?></td>
                            <td style="padding: 10px; text-align: center;"><?php echo $data['wins'] > 0 ? str_repeat('🏆', $data['wins']) : '-'; ?></td>
                            <td style="padding: 10px; text-align: right; color: #34d399; font-weight: bold;">$<?php echo number_format($data['amount']); ?></td>
                        </tr>
                        <?php $i++; endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Consistency -->
            <div>
                <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 5px;">🎯 Consistency Leaders (Min 2 Years)</h3>
                <div class="table-container">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="color: var(--text-muted); border-bottom: 1px solid #444;">
                            <th style="padding: 10px; text-align: left;">Name</th>
                            <th style="padding: 10px; text-align: center;">Years</th>
                            <th style="padding: 10px; text-align: right;">Avg Rank</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 0;
                        foreach($avg_ranks as $row): 
                            if ($i >= 10) break;
                        ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 10px;"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td style="padding: 10px; text-align: center;"><?php echo $row['years']; ?></td>
                            <td style="padding: 10px; text-align: right; font-weight: bold; color: var(--accent-orange);"><?php echo $row['avg']; ?></td>
                        </tr>
                        <?php $i++; endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>



        <?php else: ?>
        
        <!-- LOCKED VIEW -->
        <div style="text-align:center; padding:60px 20px; background:rgba(255,255,255,0.05); border-radius:12px; border:1px solid #333;">
            <i class="fa-solid fa-lock" style="font-size:3rem; color:var(--text-muted); margin-bottom:20px;"></i>
            <h3 style="color:#fff;">Members Only Access</h3>
            <p style="color:var(--text-muted); max-width:400px; margin:0 auto 20px;">The Hall of Fame archives are reserved for active tournament participants. Please log in to view historical records.</p>
            <a href="login.php" style="background:var(--accent-orange); color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;">Log In</a>
        </div>
        
        <?php endif; ?>
    </div>
    </div>
</div>


