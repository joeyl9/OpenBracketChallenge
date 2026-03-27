<?php
include("admin/database.php");
include("admin/functions.php");
include("header.php");

// Fetch Teams
$team_data = $db->query("SELECT * FROM `master` WHERE `id`=1")->fetch(PDO::FETCH_ASSOC);
$seedMap = getSeedMap($db);
// Format team names with seeds
for($i=1; $i<=64; $i++) {
    $val = isset($team_data[$i]) ? $team_data[$i] : '';
    if ($val) {
        $seed = isset($seedMap[$val]) ? $seedMap[$val] : '';
        $team_data[$i] = "$seed. $val";
    }
}

// Fetch Current Master (Real Results)
$master = $db->query("SELECT * FROM `master` WHERE `id`=2")->fetch(PDO::FETCH_ASSOC);

// Map Region IDs
$meta = $db->query("SELECT * FROM meta WHERE id=1")->fetch(PDO::FETCH_ASSOC);

?>
<style>
.interactive-team {
    cursor: pointer;
    transition: background 0.2s;
}
.interactive-team:hover {
    background: rgba(255,255,255,0.1);
}
.team-selected {
    background: var(--accent-orange) !important;
    color: white !important;
}

/* Bracket Overflow Handling */
#interactive-bracket {
    overflow-x: auto;
    padding-bottom: 20px;
    display: flex;
    justify-content: flex-start; /* Start aligned to allow full scroll */
}
/* Allow the bracket to scroll horizontally on small screens without shifting content off-canvas unexpectedly. */
.bracket-split-left, .bracket-split-right { min-width: 300px; margin: 0 10px; }
.bracket-center { min-width: 250px; margin: 0 10px; }

</style>

<div id="main" class="full">
	
	<div class="content-card" style="width:98%; margin:0 auto; overflow-x:auto;">
		<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
			<h2 style="margin:0; color:var(--accent-orange);"><i class="fa-solid fa-crystal-ball"></i> Interactive Scenario Planner</h2>
			<a href="choose.php" style="padding:8px 16px; background:var(--accent-orange); color:var(--accent-text); border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9em; transition:background 0.2s;"><i class="fa-solid fa-arrow-left"></i> Back to Standings</a>
		</div>

		<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding-top:10px; text-align:center; width: 100%;">
			<p style="margin-bottom:20px;">Click teams to advance them. Click "Calculate Rank" to see where you'd finish.</p>
			
			<div style="display:flex; gap:15px; flex-wrap:wrap; justify-content:center;">
				<button onclick="calculateScenario()" style="padding:15px 30px; font-size:1.2rem; border-radius:6px; cursor:pointer; background:var(--accent-orange); color:white; border:none;">
					<i class="fa-solid fa-rocket"></i> Calculate My Rank
				</button>
				<button onclick="resetScenario()" style="padding:15px 30px; border-radius:6px; cursor:pointer; background:transparent; color:var(--accent-orange) !important; border:1px solid var(--accent-orange); transition:all 0.2s;" onmouseover="this.style.background='var(--accent-orange)'; this.style.color='var(--accent-text) !important';" onmouseout="this.style.background='transparent'; this.style.color='var(--accent-orange) !important';">
					<i class="fa-solid fa-rotate-left"></i> Reset
				</button>
			</div>
			
			<div id="sim-result" style="margin-top:20px; display:none; background:#1e293b; padding:20px; border-radius:8px; border:1px solid #334155; max-width:500px; width:100%;">
				<h3 style="color:var(--accent-orange); margin:0;">Simulated Result</h3>
				<div id="sim-content" style="font-size:1.1rem; margin-top:10px;"></div>
			</div>
		</div>


    <!-- Bracket Container -->
    <div class="bracket-wrapper" id="interactive-bracket" style="background: transparent;">
        <!-- Render similar layout to view.php but adding interactive IDs -->
        
        <script>
        // Initial State from PHP
        const teamData = <?php echo json_encode($team_data); ?>;
        const masterData = <?php echo json_encode($master); ?>;
        const scenario = {}; // Overrides

        function initBracket() {
            // Fill known results pre-rendered by PHP
            // JS progression path mapping:
            // R1: Games 1-32. Winner of G1 goes to G33 Slot 0, G2 to G33 Slot 1.
            // Next Game = 32 + ceil(CurrentGame/2). Slot = (CurrentGame-1)%2.
        }
        
        function advance(gameId, pickText) {
            // 1. Update Scenario
            scenario[gameId] = cleanName(pickText);
            
            // 2. Find Next Game Slot
            // R1 (1-32) -> R2 (33-48)
            // R2 (33-48) -> R3 (49-56)
            // R3 (49-56) -> R4 (57-60)
            // R4 (57-60) -> F4 (61-62)
            // F4 (61-62) -> Champ (63)
            
            let nextGame = 0;
            if (gameId <= 32) nextGame = 32 + Math.ceil(gameId/2);
            else if (gameId <= 48) nextGame = 48 + Math.ceil((gameId-32)/2);
            else if (gameId <= 56) nextGame = 56 + Math.ceil((gameId-48)/2);
            else if (gameId <= 60) nextGame = 60 + Math.ceil((gameId-56)/2); // 57->61, 58->61, 59->62, 60->62
            else if (gameId <= 62) nextGame = 63;
            
            if(nextGame === 61 && (gameId === 57 || gameId === 58)) nextGame = 61;
            if(nextGame === 61 && (gameId === 59 || gameId === 60)) nextGame = 62; // Correct offset for Region 3/4
            
            // Standard Logic: 
            // 1-32 -> 33-48
            // 33-48 -> 49-56
            // 49-56 -> 57-60
            // 57,58 -> 61
            // 59,60 -> 62
            // 61,62 -> 63
            
            // Determine Target DOM Element
            // Mapping uses IDs `slot_GAMEID_0` and `slot_GAMEID_1`.
            
            // Calc next:
            let nextG = 0;
            let slot = 0;
             if (gameId <= 32) { nextG = 32 + Math.ceil(gameId/2); slot = (gameId%2 === 1) ? 0 : 1; }
             else if (gameId <= 48) { nextG = 48 + Math.ceil((gameId-32)/2); slot = (gameId%2 === 1) ? 0 : 1; }
             else if (gameId <= 56) { nextG = 56 + Math.ceil((gameId-48)/2); slot = (gameId%2 === 1) ? 0 : 1; }
             else if (gameId <= 60) { // 57->61(0), 58->61(1), 59->62(0), 60->62(1)
                 if (gameId==57) { nextG=61; slot=0; }
                 if (gameId==58) { nextG=61; slot=1; }
                 if (gameId==59) { nextG=62; slot=0; }
                 if (gameId==60) { nextG=62; slot=1; }
             }
             else if (gameId <= 62) { nextG = 63; slot = (gameId==61) ? 0 : 1; }
             
             if(nextG > 0) {
                 const el = document.getElementById(`slot_${nextG}_${slot}`);
                 if(el) {
                     el.innerText = pickText;
                     el.dataset.team = cleanName(pickText);
                     // Reset future path:
                     resetFuture(nextG);
                 }
             }
        }
        
        function resetFuture(gameId) {
             // Future progression resets are not automatic; user must explicitly click the new winner to override downstream games.
        }

        function cleanName(txt) {
            return txt.trim(); // Simpler than the regex stripping for simulation matching
        }
        
        function calculateScenario() {
            const btn = document.querySelector('button[onclick="calculateScenario()"]');
            btn.innerHTML = "⏳ Calculating...";
            btn.disabled = true;
            
            // Build Scenario Object
            // The `advance` function updates the `scenario` global object directly with user selections.
            // It tracks winners at each node to calculate hypothetical scoring.
            
            fetch('api/simulate.php', {
                method: 'POST',
                body: JSON.stringify({ scenario: scenario })
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = "<i class='fa-solid fa-rocket'></i> Calculate My Rank";
                btn.disabled = false;
                
                const resDiv = document.getElementById('sim-result');
                const content = document.getElementById('sim-content');
                resDiv.style.display = 'block';
                
                // XSS-safe: build DOM instead of innerHTML with user-controlled data
                content.innerHTML = ''; // clear
                if(data.success) {
                    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
                    var rankDiv = document.createElement('div');
                    rankDiv.innerHTML = 'You would be Rank: <strong style="color:var(--accent-orange); font-size:1.5em;">#' + esc(String(data.my_rank)) + '</strong>';
                    content.appendChild(rankDiv);
                    var scoreDiv = document.createElement('div');
                    scoreDiv.textContent = 'Score: ' + data.my_score;
                    content.appendChild(scoreDiv);
                    var hr = document.createElement('hr');
                    hr.style.cssText = 'border-color:#444; margin:10px 0;';
                    content.appendChild(hr);
                    var topDiv = document.createElement('div');
                    topDiv.textContent = 'Top Score: ' + data.top_score + ' (' + data.top_user + ')';
                    content.appendChild(topDiv);
                } else {
                    var errSpan = document.createElement('span');
                    errSpan.style.color = 'red';
                    errSpan.textContent = 'Error: ' + data.error;
                    content.appendChild(errSpan);
                }
            });
        }
        
        function resetScenario() {
            location.reload();
        }
        
        function teamClick(el) {
            const gid = el.getAttribute('data-gameid');
            const txt = el.innerText;
            if(!txt || txt === 'TBD' || txt === '&nbsp;') return;
            
            // Visual feedback on the clicked element
            // Remove selected from sibling?
            const parent = el.parentElement;
            parent.querySelectorAll('.team').forEach(t => t.classList.remove('team-selected'));
            el.classList.add('team-selected');
            
            // Advance
            advance(parseInt(gid), txt);
        }
        </script>
        
        <?php
        // RENDER FUNCTIONS (Inline for modifications)
        function renderInteractiveRegion($name, $startTeamIndex, $r1Games, $r2Games, $r3Games, $r4Game, $team_data, $master) {
            echo "<div class='region-container'>";
            
            // R1
            echo "<div class='round'><h3>".h($name)." Round 1</h3>";
            foreach($r1Games as $gId) {
                // Teams feeding this game
                $t1_idx = ($gId * 2) - 1;
                $t2_idx = $gId * 2;
                $t1 = $team_data[$t1_idx] ?? "TBD";
                $t2 = $team_data[$t2_idx] ?? "TBD";
                
                // Check if already decided in Master
                $decided = isset($master[$gId]) ? $master[$gId] : false;
                $c1 = ($decided && $decided == cleanName($t1)) ? 'team-selected' : '';
                $c2 = ($decided && $decided == cleanName($t2)) ? 'team-selected' : '';
                
                echo "<div class='matchup'>
                        <div class='team interactive-team $c1' data-gameid='$gId' onclick='teamClick(this)'>$t1</div>
                        <div class='team interactive-team $c2' data-gameid='$gId' onclick='teamClick(this)'>$t2</div>
                      </div>";
            }
            echo "</div>";
            
            // R2 (Slots for winners of R1)
            echo "<div class='round'><h3>Round 2</h3>";
            foreach($r2Games as $gId) {
                // Determine source games (e.g., G33 sources G1, G2)
                $srcG1 = ($gId - 32) * 2 - 1; // e.g. 1
                $srcG2 = ($gId - 32) * 2;     // e.g. 2
                
                $val1 = isset($master[$srcG1]) ? $master[$srcG1] : '&nbsp;';
                $val2 = isset($master[$srcG2]) ? $master[$srcG2] : '&nbsp;';
                
                $decided = !empty($master[$gId]) ? $master[$gId] : false;
                $c1 = ($decided && $decided === $val1) ? 'team-selected' : '';
                $c2 = ($decided && $decided === $val2) ? 'team-selected' : '';
                
                echo "<div class='matchup'>
                        <div id='slot_{$gId}_0' class='team interactive-team $c1' data-gameid='$gId' onclick='teamClick(this)'>$val1</div>
                        <div id='slot_{$gId}_1' class='team interactive-team $c2' data-gameid='$gId' onclick='teamClick(this)'>$val2</div>
                      </div>";
            }
            echo "</div>";
            
            // R3
            echo "<div class='round'><h3>Round of 16</h3>";
            foreach($r3Games as $gId) {
                // G49 <- G33, G34
                $srcG1 = 33 + ($gId - 49) * 2;
                $srcG2 = $srcG1 + 1;
                $val1 = isset($master[$srcG1]) ? $master[$srcG1] : '&nbsp;';
                $val2 = isset($master[$srcG2]) ? $master[$srcG2] : '&nbsp;';

                $decided = !empty($master[$gId]) ? $master[$gId] : false;
                $c1 = ($decided && $decided === $val1) ? 'team-selected' : '';
                $c2 = ($decided && $decided === $val2) ? 'team-selected' : '';

                echo "<div class='matchup'>
                        <div id='slot_{$gId}_0' class='team interactive-team $c1' data-gameid='$gId' onclick='teamClick(this)'>$val1</div>
                        <div id='slot_{$gId}_1' class='team interactive-team $c2' data-gameid='$gId' onclick='teamClick(this)'>$val2</div>
                      </div>";
            }
            echo "</div>";
            
            // R4
            echo "<div class='round'><h3>Quarterfinals</h3>";
            $gId = $r4Game;
            $srcG1 = 49 + ($gId - 57) * 2;
            $srcG2 = $srcG1 + 1;
            $val1 = isset($master[$srcG1]) ? $master[$srcG1] : '&nbsp;';
            $val2 = isset($master[$srcG2]) ? $master[$srcG2] : '&nbsp;';
            
            $decided = !empty($master[$gId]) ? $master[$gId] : false;
            $c1 = ($decided && $decided === $val1) ? 'team-selected' : '';
            $c2 = ($decided && $decided === $val2) ? 'team-selected' : '';
            
            echo "<div class='matchup'>
                    <div id='slot_{$gId}_0' class='team interactive-team $c1' data-gameid='$gId' onclick='teamClick(this)'>$val1</div>
                    <div id='slot_{$gId}_1' class='team interactive-team $c2' data-gameid='$gId' onclick='teamClick(this)'>$val2</div>
                  </div>";
            echo "</div></div>";
        }
        
        function cleanName($n) {
            return trim(preg_replace('/^\d+\.\s+/', '', explode(' - ', $n)[0]));
        }
        ?>

        <!-- RENDER -->
        <div class="bracket-split-left">
            <?php renderInteractiveRegion($meta['region1'], 1, array(1,2,3,4,5,6,7,8), array(33,34,35,36), array(49,50), 57, $team_data, $master); ?>
            <?php renderInteractiveRegion($meta['region2'], 17, array(9,10,11,12,13,14,15,16), array(37,38,39,40), array(51,52), 58, $team_data, $master); ?>
        </div>

        <div class="bracket-center">
            <h2>SEMIFINALS</h2>
            <!-- F4 G61 (Sources 57, 58) -->
            <?php
            $val1 = $master['57'] ?? '&nbsp;';
            $val2 = $master['58'] ?? '&nbsp;';
            $decided = !empty($master['61']) ? $master['61'] : false;
            $c1 = ($decided && $decided === $val1) ? 'team-selected' : '';
            $c2 = ($decided && $decided === $val2) ? 'team-selected' : '';
            ?>
            <div class="matchup">
                <div id='slot_61_0' class='team interactive-team <?php echo $c1; ?>' data-gameid='61' onclick='teamClick(this)'><?php echo $val1; ?></div>
                <div id='slot_61_1' class='team interactive-team <?php echo $c2; ?>' data-gameid='61' onclick='teamClick(this)'><?php echo $val2; ?></div>
            </div>
            
            <!-- F4 G62 (Sources 59, 60) -->
            <?php
            $val1 = $master['59'] ?? '&nbsp;';
            $val2 = $master['60'] ?? '&nbsp;';
            $decided = !empty($master['62']) ? $master['62'] : false;
            $c1 = ($decided && $decided === $val1) ? 'team-selected' : '';
            $c2 = ($decided && $decided === $val2) ? 'team-selected' : '';
            ?>
            <div class="matchup">
                <div id='slot_62_0' class='team interactive-team <?php echo $c1; ?>' data-gameid='62' onclick='teamClick(this)'><?php echo $val1; ?></div>
                <div id='slot_62_1' class='team interactive-team <?php echo $c2; ?>' data-gameid='62' onclick='teamClick(this)'><?php echo $val2; ?></div>
            </div>
            
            <h2>CHAMPIONSHIP</h2>
            <!-- Champ G63 -->
            <?php
            $val1 = $master['61'] ?? '&nbsp;';
            $val2 = $master['62'] ?? '&nbsp;';
            $decided = !empty($master['63']) ? $master['63'] : false;
            $c1 = ($decided && $decided === $val1) ? 'team-selected' : '';
            $c2 = ($decided && $decided === $val2) ? 'team-selected' : '';
            ?>
            <div class="matchup" style="border:2px solid var(--accent-orange);">
                <div id='slot_63_0' class='team interactive-team <?php echo $c1; ?>' data-gameid='63' onclick='teamClick(this)'><?php echo $val1; ?></div>
                <div id='slot_63_1' class='team interactive-team <?php echo $c2; ?>' data-gameid='63' onclick='teamClick(this)'><?php echo $val2; ?></div>
            </div>
            
            <h3>Champion</h3>
            <div id='slot_64_0' class='team' style="font-size:1.5em; color:var(--accent-orange); font-weight:bold;">
                <?php echo $master['63'] ?? '?'; ?>
            </div>
        </div>

        <div class="bracket-split-right">
            <?php renderInteractiveRegion($meta['region3'], 33, array(17,18,19,20,21,22,23,24), array(41,42,43,44), array(53,54), 59, $team_data, $master); ?>
            <?php renderInteractiveRegion($meta['region4'], 49, array(25,26,27,28,29,30,31,32), array(45,46,47,48), array(55,56), 60, $team_data, $master); ?>
        </div>
    </div>
    </div>
</div>
</div>
</body>
</html>
