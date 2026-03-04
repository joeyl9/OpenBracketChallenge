<?php
include("header.php");
include("admin/functions.php");

// 1. Check if Sweet 16 Competition is Enabled
if( !isset($meta['sweet16Competition']) || $meta['sweet16Competition'] == 0 ) {
    echo '<div id="main" class="full"><div style="background:#1e293b; padding:40px; text-align:center; border-radius:12px;">';
    echo '<h2 style="color:var(--text-light);">Second Chance Pool Not Active</h2>';
    echo '<p style="color:var(--text-muted);">Please check back later.</p>';
    echo '<a href="dashboard.php" class="finish-btn" style="display:inline-block; margin-top:20px; padding:10px 20px;">Return to Dashboard</a>';
    echo '</div></div>';
    exit();
}

$master_query = "SELECT * FROM `master` WHERE `id`=2"; // Select Winners So Far
$stmt = $db->query($master_query);
$winners = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Validate Sweet 16 is Known (Teams 1-48 determine Sweet 16)
$sweet16determined = true;
for( $i=1; $i <= 48; $i++) {
    if( $winners[$i] == "" ) {
        $sweet16determined = false;
        break;
    }
}

if( $sweet16determined == false && !isset($_GET['debug'])) {
    echo '<div id="main"><div class="left_side">The Sweet 16 field is not yet set. Check back after the Second Round!</div></div>';
    exit();
}

// 3. Duplicate Check for THIS user and THIS bracket type (Session-only)
if(isset($_SESSION['useremail'])) {
	$u_email = $_SESSION['useremail'];
	$check_q = "SELECT id FROM `brackets` WHERE email=:email AND `type`='sweet16' LIMIT 1";
	$stmt = $db->prepare($check_q);
	$stmt->execute(array(':email' => $u_email));
	$existing = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if($existing) {
		echo '<div id="main" class="full"><div style="background:#1e293b; padding:40px; text-align:center; border-radius:12px;">
				<h2 style="color:var(--accent-orange);">You are entered!</h2>
				<p>You have already submitted your Second Chance bracket.</p>
				<br>
				<a href="edit.php?id='.$existing['id'].'" class="finish-btn" style="background:var(--accent-orange); color:white; padding:15px 30px; text-decoration:none; border-radius:5px; font-weight:bold;">EDIT YOUR PICKS</a>
			  </div></div>';
		exit();
	}
}

$seedMap = getSeedMap($db);

// Load Master Data Needed for Display
$teams = $db->query("SELECT * FROM `master` WHERE `id`=1")->fetch(PDO::FETCH_ASSOC);
$teamNames = $db->query("SELECT * FROM `master` WHERE `id`=1")->fetch(PDO::FETCH_ASSOC);
$seeds = $db->query("SELECT * FROM `master` WHERE `type`='seeds'")->fetch(PDO::FETCH_ASSOC);

// Map Winner Names
for( $i=1; $i <= 64; $i++) {
    // If a winner exists, use that. Else use TBD (Sweet 16 check guarantees availability for 1-48)
    $winnerNames[$i] = ($winners[$i]) ? $seedMap[$winners[$i]].". ".$winners[$i] : "TBD";
}
?>

<script type="text/javascript">
function validateFields(alertText) {
    // Only check games 49-63
	for( var i=49; i<64; i++ ) {
		var field = document.getElementById('game'+i);
		if( !field || field.value == "" ) {
			alert( "You must pick a winner for all games in the Sweet 16 and beyond." );
			if(field) field.focus();
			return false;
		}
	}
	// Check Personal Info
    var req = ['bracketname','name','e-mail','password','tiebreaker'];
    for(var k=0; k<req.length; k++){
        if(document.getElementsByName(req[k])[0].value == ""){
            alert("Please fill out all fields.");
            return false;
        }
    }
	return window.confirm(alertText);
}

// Chain Logic
function update(gameId, nextGameId, slotIndex) {
    var select = document.getElementById(gameId);
    var nextSelect = document.getElementById(nextGameId);
    var choice = select.options[select.selectedIndex].value;
    var text = select.options[select.selectedIndex].text;
    
    if(nextSelect) {
        nextSelect.options[slotIndex] = new Option(text, choice);
    }
}
</script>

<div id="main" class="full">
    <div style="background:#1e293b; padding:20px; border-radius:12px; border:1px solid #334155;">
        <h2 style="color:var(--accent-orange); border-bottom:1px solid #334155; padding-bottom:15px;"><i class="fa-solid fa-rotate"></i> Second Chance Bracket</h2>
        <p style="color:var(--text-muted); margin-bottom:20px;">
            Your first bracket busted? No problem. Use the <strong>Sweet 16</strong> to redeem yourself!<br>
            <em>Entry Fee: Same as main pool (if applicable).</em>
        </p>

        <form method="post" name="bracket" id="bracket" action="bracket.php">
            <?php csrf_field(); ?>
            <div style="background:rgba(0,0,0,0.2); padding:20px; margin-bottom:20px; border-radius:8px; display:flex; gap:20px; flex-wrap:wrap;">
                <div><label>Bracket Name:</label><br><input type="text" name="bracketname" required></div>
                <div><label>Your Name:</label><br><input type="text" name="name" required></div>
                <div><label>Email:</label><br><input type="email" name="e-mail" required></div>
                <div><label>Password:</label><br><input type="password" name="password" required></div>
                <input type="hidden" name="bracket_type" value="sweet16">
            </div>

            <!-- Sweet 16 Layout -->
            <!-- Matches 49-56 Setup -->
            
            <div style="overflow-x:auto;">
            <table width="100%" cellpadding="5" cellspacing="0" style="color:white;">
                <tr>
                    <td colspan="4" style="background:var(--primary-blue); font-weight:bold; padding:10px;">Sweet 16 Matches</td>
                    <td style="background:var(--primary-blue); font-weight:bold; padding:10px;">Elastic 8</td>
                    <td style="background:var(--primary-blue); font-weight:bold; padding:10px;">Final Four</td>
                </tr>
                
                <?php
                // Helper to render select
                function renderSelect($id, $nextId, $slot) {
                    return "<select name='$id' id='$id' class='forms' style='width:100%; padding:5px;' onchange=\"update('$id','$nextId',$slot)\"></select>";
                }
                
                // We need to initialize the options for the first level (Sweet 16 games: 49-56)
                // Games 49-56 inputs come from Winners of 33-48
                // Game 49: Winner(33) vs Winner(34)
                
                $matchups = [
                    // Region 1
                    49 => [33, 34, 'game57', 0],
                    50 => [35, 36, 'game57', 1],
                    // Region 2
                    51 => [37, 38, 'game58', 0],
                    52 => [39, 40, 'game58', 1],
                    // Region 3
                    53 => [41, 42, 'game59', 0],
                    54 => [43, 44, 'game59', 1],
                    // Region 4
                    55 => [45, 46, 'game60', 0],
                    56 => [47, 48, 'game60', 1]
                ];
                
                foreach($matchups as $gId => $data) {
                    $w1 = $data[0]; $w2 = $data[1];
                    $next = $data[2]; $slot = $data[3];
                    
                    echo "<tr>";
                    echo "<td>Match $gId</td>";
                    echo "<td>".$winnerNames[$w1]."</td>";
                    echo "<td>vs</td>";
                    echo "<td>".$winnerNames[$w2]."</td>";
                    // The Select for THIS game result
                    echo "<td><select name='game$gId' id='game$gId' class='forms' onchange=\"update('game$gId','$next',$slot)\">
                            <option value=''>Pick Winner...</option>
                            <option value='".$winners[$w1]."'>".$winnerNames[$w1]."</option>
                            <option value='".$winners[$w2]."'>".$winnerNames[$w2]."</option>
                          </select></td>";
                    echo "</tr>";
                }
                ?>
            </table>
            </div>

            <hr style="border:0; border-top:1px solid #334155; margin:20px 0;">

            <!-- Elite 8 / F4 / Champ Selection -->
            <!-- Selects are populated via JS update() logic when previous rounds are selected -->
            
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-top:20px;">
                <div>
                    <h3>Elite 8 Winners</h3>
                    <label>Region 1 Final (Game 57)</label><br>
                    <select name="game57" id="game57" size="2" style="width:100%; height:60px;" onchange="update('game57','game61',0)"></select>
                    <br><br>
                    
                    <label>Region 2 Final (Game 58)</label><br>
                     <select name="game58" id="game58" size="2" style="width:100%; height:60px;" onchange="update('game58','game61',1)"></select>
                     <br><br>
                     
                    <label>Region 3 Final (Game 59)</label><br>
                     <select name="game59" id="game59" size="2" style="width:100%; height:60px;" onchange="update('game59','game62',0)"></select>
                     <br><br>
                     
                    <label>Region 4 Final (Game 60)</label><br>
                     <select name="game60" id="game60" size="2" style="width:100%; height:60px;" onchange="update('game60','game62',1)"></select>
                </div>
                
                <div>
                    <h3>Final Four Winners</h3>
                    <label>Semi-Final 1 (Game 61)</label><br>
                    <select name="game61" id="game61" size="2" style="width:100%; height:60px;" onchange="update('game61','game63',0)"></select>
                    <br><br>
                    
                    <label>Semi-Final 2 (Game 62)</label><br>
                    <select name="game62" id="game62" size="2" style="width:100%; height:60px;" onchange="update('game62','game63',1)"></select>
                </div>
                
                <div style="background:rgba(251, 191, 36, 0.1); padding:15px; border-radius:8px; border:1px solid #fbbf24;">
                    <h3 style="color:#fbbf24;"><i class="fa-solid fa-trophy"></i> National Champion</h3>
                    <select name="game63" id="game63" size="2" style="width:100%; height:60px;" required></select>
                    
                    <p style="margin-top:20px;">
                        <label>Tiebreaker (Points):</label><br>
                        <input type="number" name="tiebreaker" style="width:80px; text-align:center; font-weight:bold;" required>
                    </p>
                    
                    <input type="submit" name="submit" value="Submit Second Chance" class="finish-btn" style="width:100%; margin-top:10px; cursor:pointer;" onclick="return validateFields('Ready to submit?');">
                </div>
            </div>

        </form>
    </div>
</div>

</body>
</html>
