<?php
session_start();
include_once("admin/database.php");
include_once("admin/functions.php");

// DB Checks (Modernized)
$closedQ = "SELECT `closed`, `deadline`, `reg_mode`, `reg_password`, `reg_token`, `max_brackets` FROM `meta` WHERE `id`=1";
$stmt = $db->query($closedQ);
$metaRow = $stmt->fetch(PDO::FETCH_ASSOC);

$isClosed = $metaRow['closed'] != 0;
if (!$isClosed && !empty($metaRow['deadline'])) {
    if (new DateTime() > new DateTime($metaRow['deadline'])) {
        $isClosed = true;
    }
}

if($isClosed) {
    include("header.php");
    ?>
    <div id="main" style="padding:40px 20px; text-align:center;">
        <div style="max-width:800px; margin:0 auto; background:#1e293b; padding:40px; border-radius:10px; border:1px solid #334155; box-shadow:0 10px 25px rgba(0,0,0,0.5);">
            <div style="font-size:4rem; margin-bottom:20px;"><i class="fa-solid fa-basketball" style="color: var(--accent-orange);"></i></div>
            <h2 style="color:var(--accent-orange); font-size:2.5em; margin-bottom:10px; text-transform:uppercase; letter-spacing:1px;">Tournament Underway!</h2>
            <p style="color:var(--text-light); font-size:1.2em; line-height:1.6; margin-bottom:30px;">
                Bracket submission is now closed as the games have begun.<br>
                Check back specifically to see how your picks are performing!
            </p>
            
            <div style="display:flex; justify-content:center; gap:20px; flex-wrap:wrap;">
                <a href="choose.php" style="background:#3b82f6; color:white; padding:15px 30px; text-decoration:none; border-radius:5px; font-weight:bold; font-size:1.1em; transition:all 0.2s;">
                    View Standings
                </a>
                <?php if(!isset($_SESSION['user_id'])) { ?>
                <a href="#" onclick="toggleLogin(event)" style="background:#22c55e; color:white; padding:15px 30px; text-decoration:none; border-radius:5px; font-weight:bold; font-size:1.1em; transition:all 0.2s;">
                    Log In
                </a>
                <?php } else { ?>
                <a href="dashboard.php" style="background:#f59e0b; color:white; padding:15px 30px; text-decoration:none; border-radius:5px; font-weight:bold; font-size:1.1em; transition:all 0.2s;">
                    Go to Dashboard
                </a>
                <?php } ?>
            </div>
        </div>
    </div>

    </body>
    </html>
    <?php
    exit();
}

/**
 * REGISTRATION RESTRICTIONS
 */
$reg_mode = isset($metaRow['reg_mode']) ? $metaRow['reg_mode'] : 0; // 0=Open, 1=Pass, 2=Token

// MODE 1: Password Restricted
$reg_pass_error = false;
if($reg_mode == 1) {
    if(isset($_POST['reg_pass_check'])) {
        if($_POST['reg_pass_check'] === $metaRow['reg_password']) {
            $_SESSION['reg_unlocked'] = true;
        } else {
            $reg_pass_error = true;
        }
    }
    
    if(!isset($_SESSION['reg_unlocked'])) {
        include("header.php");
        echo '<div id="main" style="padding:40px; text-align:center;">
                <div style="background:#1e293b; padding:40px; border-radius:12px; max-width:500px; margin:0 auto; border:1px solid #334155;">
                    <h2 style="color:var(--accent-orange); margin-top:0;"><i class="fa-solid fa-lock"></i> Restricted Access</h2>
                    <p style="color:var(--text-light);">A password is required to register for this tournament.</p>
                    <form method="post">
                        <input type="password" name="reg_pass_check" placeholder="Enter Registration Password" style="padding:10px; width:80%; border-radius:4px; border:1px solid #444; background:#0f172a; color:white;">
                        <br><br>
                        <button type="submit" style="padding:10px 20px; background:var(--accent-orange); border:none; color:white; border-radius:4px; font-weight:bold; cursor:pointer;">Unlock Registration</button>
                    </form>';
        if($reg_pass_error) echo '<p style="color:#ef4444; margin-top:10px;">Incorrect Password</p>';
        echo '  </div>
              </div>
              </div>';
        exit();
    }
}

// MODE 2: Token Restricted
if($reg_mode == 2) {
    $valid_token = $metaRow['reg_token'];
    $url_token = isset($_GET['token']) ? $_GET['token'] : (isset($_SESSION['reg_token']) ? $_SESSION['reg_token'] : '');
    
    if($url_token === $valid_token) {
        $_SESSION['reg_token'] = $url_token; // persist in session
    } else {
        include("header.php");
        echo '<div id="main" style="padding:40px; text-align:center;">
                <div style="background:#1e293b; padding:40px; border-radius:12px; max-width:500px; margin:0 auto; border:1px solid #334155;">
                    <h2 style="color:#ef4444; margin-top:0;"><i class="fa-solid fa-ban"></i> Access Denied</h2>
                    <p style="color:var(--text-light);">Registration is via invite link only.</p>
                    <p style="color:var(--text-muted); font-size:0.9em;">Please contact the administrator for a valid registration link.</p>
                </div>
              </div>
              </div>';
        exit();
    }
}


if( isset($meta['sweet16Competition']) && $meta['sweet16Competition'] == 1 ) {
	echo '<div id="main"><div class="left_side">You have reached this page by mistake. Use Second Chance link.</div></div>';
	exit();
}

$email = "SELECT * FROM `meta` WHERE `id`=1"; // Fetch all meta for checks
$stmt = $db->query($email);
$email = $stmt->fetch(PDO::FETCH_ASSOC);

$teams = "SELECT * FROM `master` WHERE `id`=1"; 
$stmt = $db->query($teams);
$teams = $stmt->fetch(PDO::FETCH_ASSOC);

$teamNames = "SELECT * FROM `master` WHERE `id`=1"; 
$stmt = $db->query($teamNames);
$teamNames = $stmt->fetch(PDO::FETCH_ASSOC);

$seedsQuery = "SELECT * FROM `master` WHERE `id`=4"; 
$stmt = $db->query($seedsQuery);
$seeds = $stmt->fetch(PDO::FETCH_ASSOC);

include("header.php");

// AUTH CHECK (Session-only)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_email = isset($_SESSION['useremail']) ? $_SESSION['useremail'] : null;

if(!$user_id) {
    echo '<div id="main" style="padding:40px; min-height:500px; display:flex; justify-content:center; align-items:center;">
            <div style="background:#1e293b; padding:40px; border-radius:12px; width:100%; max-width:500px; border:1px solid #334155; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); text-align:center;">
                <h2 style="color:var(--accent-orange); margin-top:0; font-size:2rem; margin-bottom:15px;"><i class="fa-solid fa-lock"></i> Login Required</h2>
                <p style="color:var(--text-light); margin-bottom:30px; font-size:1.1em;">You must be logged in to create a bracket.</p>
                <div style="display:flex; gap:15px; justify-content:center;">
                    <a href="login.php" style="background:var(--accent-orange); color:white; padding:12px 30px; text-decoration:none; border-radius:6px; font-weight:bold; transition:all 0.2s;">Log In</a>
                    <a href="register.php" style="background:#334155; color:white; padding:12px 30px; text-decoration:none; border-radius:6px; font-weight:bold; transition:all 0.2s; border:1px solid #475569;">Register</a>
                </div>
            </div>
          </div>
          </div>';
    exit();
}

// RESTRICTION: Default Admin (Break Glass) cannot submit brackets
if(is_admin() && empty($user_id)) {
    echo '<div id="main" style="padding:40px; text-align:center;">
            <div style="background:#1e293b; padding:40px; border-radius:12px; max-width:600px; margin:0 auto; border:1px solid #334155;">
                <h2 style="color:#f59e0b; margin-top:0;"><i class="fa-solid fa-user-shield"></i> Administrator Account</h2>
                <p style="color:var(--text-light); font-size:1.1em;">The default <strong>Administrator</strong> account is for site management only.</p>
                <p style="color:var(--text-muted);">To participate in the tournament, please Register a new personal account.</p>
                <br>
                <div style="display:flex; gap:15px; justify-content:center;">
                    <a href="admin/index.php" style="background:#3b82f6; color:white; padding:12px 20px; text-decoration:none; border-radius:6px; font-weight:bold;">Go to Dashboard</a>
                    <a href="register.php" style="background:#22c55e; color:white; padding:12px 20px; text-decoration:none; border-radius:6px; font-weight:bold;">Register New Account</a>
                </div>
            </div>
          </div>
          </div>';
    include("footer.php");
    exit();
}

// INITIALIZATION CHECK: Master Bracket (ID=1) must exist and have teams
if(!$teams || empty($teams['1'])) {
     echo '<div id="main" style="padding:40px; text-align:center;">
            <div style="background:#1e293b; padding:40px; border-radius:12px; max-width:600px; margin:0 auto; border:1px solid #334155;">
                <h2 style="color:#ef4444; margin-top:0;"><i class="fa-solid fa-triangle-exclamation"></i> Tournament Not Ready</h2>
                <p style="color:var(--text-light); font-size:1.1em;">The tournament bracket has not been initialized yet.</p>
                <p style="color:var(--text-muted);">Please wait for the administrator to set up the teams.</p>
                <br>
                <a href="index.php" style="background:#334155; color:white; padding:12px 20px; text-decoration:none; border-radius:6px; font-weight:bold;">Return Home</a>
            </div>
          </div>
          </div>';
    include("footer.php");
    exit();   
}

// DUPLICATE/LIMIT CHECK
// Check how many 'main' brackets this user has
$count_q = "SELECT COUNT(*) FROM `brackets` WHERE user_id=:uid AND `type`='main'";
$stmt = $db->prepare($count_q);
$stmt->execute(array(':uid' => $user_id));
$bracket_count = $stmt->fetchColumn();

// Get Limit (Default to 1 if not set)
$limit = isset($metaRow['max_brackets']) ? (int)$metaRow['max_brackets'] : 1;

if($bracket_count >= $limit) {
    echo '<div id="main" style="padding:40px; text-align:center;">
            <div class="left_side" style="float:none; margin:0 auto; width:100%; max-width:800px;">
                <h2>Max Brackets Reached</h2>
                <p>You have submitted ' . $bracket_count . ' of ' . $limit . ' allowed bracket(s).</p>
                <br>
                <a href="dashboard.php" class="finish-btn" style="background:var(--accent-orange); color:white; padding:15px 30px; text-decoration:none; border-radius:5px; font-weight:bold;">GO TO DASHBOARD</a>
            </div>
          </div>
          </div>';
    exit();
}
?>

<!-- Form Inputs at Top -->
<div id="main" style="max-width: 100%; padding: 0;">
<form method="post" name="bracket" id="bracket" action="bracket.php" style="width: 100%;" onsubmit="return validateForm()">
<?php csrf_field(); ?>
<div style="background: #222; padding: 20px; color: #fff; margin-bottom: 20px; border-bottom: 2px solid var(--accent-orange);">
	<h3 style="margin-top:0;">Bracket Details</h3>
	
    <div style="display:flex; flex-wrap:wrap; gap:20px; justify-content:center;">
        <div>
            <label>Bracket Name:</label><br>
            <input type="text" name="bracketname" required style="padding:10px; border-radius:4px; border:1px solid #444; background:#333; color:white; width:200px;">
        </div>

        <?php 
             // LOGGED IN (Always true now)
             $u_name_display = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : $user_email;
        ?>
            <!-- Display User Info -->
            <div style="display:flex; align-items:center; gap:10px; background:rgba(255,255,255,0.1); padding:10px; border-radius:4px;">
                <i class="fa-solid fa-user-circle" style="font-size:2em; color:var(--accent-orange);"></i>
                <div>
                    <div style="font-size:0.8em; color:var(--text-muted);">Logged in as</div>
                    <div style="font-weight:bold;"><?php echo htmlspecialchars($user_email); ?></div>
                </div>
            </div>
            
            <!-- Hidden Inputs -->
            <input type="hidden" name="name" value="<?php echo htmlspecialchars($u_name_display); ?>">
            <input type="hidden" name="e-mail" value="<?php echo htmlspecialchars($user_email); ?>">

        <input type="hidden" name="bracket_type" value="main">
	</div>
</div>

<!-- Hidden Inputs for Games -->
<?php for($i=1; $i<=63; $i++) { echo "<input type='hidden' name='game$i' id='input_game$i' value=''>"; } ?>

<div class="bracket-wrapper" style="background: transparent; width: 100% !important; display: flex; justify-content: space-between;">
	<!-- LEFT SIDE (Regions 1 & 2) -->
	<div class="bracket-split-left">
		<?php renderRegion($meta['region1'], 1, array(1,2,3,4,5,6,7,8), array(33,34,35,36), array(49,50), 57, $teams, $teamNames, $seeds, 61, 0); ?>
		<?php renderRegion($meta['region2'], 17, array(9,10,11,12,13,14,15,16), array(37,38,39,40), array(51,52), 58, $teams, $teamNames, $seeds, 61, 1); ?>
	</div>

	<!-- CENTER (Final Four) -->
	<div class="bracket-center">
		<h2 style="text-align:center; color:#fff; font-size:1.2rem;">FINAL FOUR</h2>
		<!-- Final Four Game 1 -->
		<div class="matchup" id="matchup_61">
			<div class="team" onclick="pickWinner(61, 'input_game61', 63, 0)" id="slot_61_0">Waiting for <?php echo htmlspecialchars($meta['region1'] ?? ''); ?></div>
			<div class="team" onclick="pickWinner(61, 'input_game61', 63, 0)" id="slot_61_1">Waiting for <?php echo htmlspecialchars($meta['region2'] ?? ''); ?></div>
		</div>

		<!-- Final Four Game 2 -->
		<div class="matchup" id="matchup_62">
			<div class="team" onclick="pickWinner(62, 'input_game62', 63, 1)" id="slot_62_0">Waiting for <?php echo htmlspecialchars($meta['region3'] ?? ''); ?></div>
			<div class="team" onclick="pickWinner(62, 'input_game62', 63, 1)" id="slot_62_1">Waiting for <?php echo htmlspecialchars($meta['region4'] ?? ''); ?></div>
		</div>

		<h2 style="text-align:center; color:var(--accent-orange); font-size:1.4rem;">CHAMPIONSHIP</h2>
		<!-- Championship -->
		<div class="matchup" id="matchup_63" style="border: 2px solid var(--accent-orange);">
			<div class="team" onclick="pickWinner(63, 'input_game63', null, null)" id="slot_63_0">Winner Semi 1</div>
			<div class="team" onclick="pickWinner(63, 'input_game63', null, null)" id="slot_63_1">Winner Semi 2</div>
		</div>
		
		<div style="text-align:center; margin-top:20px;">
			<h3 style="color:#fff;">Champion</h3>
			<div id="champion_display" style="font-size:1.5em; font-weight:bold; color:var(--accent-orange); min-height:40px; margin-bottom: 20px;">?</div>
			
			<div style="background: #333; padding: 15px; border-radius: 5px;">
				<label style="color:#fff; font-weight:bold;">Tiebreaker</label><br>
				<span style="font-size:0.8rem; color:var(--text-muted);">(Total Points in Final Game)</span><br>
				<input type="number" name="tiebreaker" style="width:60px; text-align:center; margin-top:5px; padding:5px; font-weight:bold;" required>
				<br><br>
				<input type="submit" name="submit" value="Submit Bracket" class="finish-btn" style="width: 100%; white-space: normal; padding:10px 20px; font-size:1.1em; cursor:pointer; background: var(--accent-orange); color: white; border:none; border-radius:4px;">
			</div>
		</div>
	</div>

	<!-- RIGHT SIDE (Regions 3 & 4) -->
	<div class="bracket-split-right">
		<?php renderRegion($meta['region3'], 33, array(17,18,19,20,21,22,23,24), array(41,42,43,44), array(53,54), 59, $teams, $teamNames, $seeds, 62, 0); ?>
		<?php renderRegion($meta['region4'], 49, array(25,26,27,28,29,30,31,32), array(45,46,47,48), array(55,56), 60, $teams, $teamNames, $seeds, 62, 1); ?>
	</div>
</div>
</form>
</div>

<script>
function validateForm() {
    var missing = [];
    // Check Games 1-63
    for(var i=1; i<=63; i++) {
        var val = document.getElementById('input_game' + i).value;
        if(!val || val === "") {
            missing.push(i);
        }
    }
    
    if(missing.length > 0) {
        alert("Please complete your bracket! You have missed " + missing.length + " game(s).");
        return false;
    }
    
    // Check Tiebreaker
    var tie = document.getElementsByName('tiebreaker')[0].value;
    if(!tie || tie === "") {
        alert("Please enter a tiebreaker value (Total points in Championship game).");
        return false;
    }
    
    return true;
}

function pickWinner(gameId, inputId, nextGameId, nextSlotIndex) {
	// Identify clicked team (this is tricky because 'this' isn't passed directly, event.target is used)
	var target = event.target; 
	if(!target.classList.contains('team')) return;

	var teamNameText = target.innerText;
	var teamHTML = target.innerHTML; // Fixed: Grab HTML to keep the seed span styling
	var teamValue = target.getAttribute('data-value'); // If we store proper value here

	if(teamNameText.includes("Waiting")) return; // Can't pick empty

	// Update hidden input
	document.getElementById(inputId).value = teamValue || teamNameText; // Fallback

	// Visual Selection
	var parent = target.parentElement;
	var teams = parent.getElementsByClassName('team');
	for(var i=0; i<teams.length; i++) teams[i].classList.remove('selected');
	target.classList.add('selected');

	// Advance Logic
	if(nextGameId) {
		var nextSlotId = 'slot_' + nextGameId + '_' + nextSlotIndex;
		var nextSlot = document.getElementById(nextSlotId);
		if(nextSlot) {
			nextSlot.innerHTML = teamHTML; // Fixed: Use innerHTML to keep the seed span
			nextSlot.setAttribute('data-value', teamValue || teamNameText);

		}
	} else {
		// Champion
		document.getElementById('champion_display').innerHTML = teamHTML; // Fixed: Use innerHTML
	}
}
</script>

<?php include("footer.php"); ?>
</body>
</html>

<?php
// PHP Helper to render a region
function renderRegion($name, $startTeamIndex, $r1Games, $r2Games, $r3Games, $r4Game, $teams, $teamNames, $seeds, $destGameId, $destSlot) {
	echo "<div class='region-container'>";
	
	// Round 1
	echo "<div class='round'><h3>$name Round 1</h3>";
	foreach($r1Games as $index => $gId) {
		$t1_idx = ($gId * 2) - 1;
		$t2_idx = $gId * 2;
		$t1_name = $teamNames[(string)$t1_idx];
		$t2_name = $teamNames[(string)$t2_idx];
		$t1_seed = isset($seeds[(string)$t1_idx]) ? "<span style='color:#fbbf24; font-size:0.8em; margin-right:5px;'>" . $seeds[(string)$t1_idx] . "</span>" : "";
		$t2_seed = isset($seeds[(string)$t2_idx]) ? "<span style='color:#fbbf24; font-size:0.8em; margin-right:5px;'>" . $seeds[(string)$t2_idx] . "</span>" : "";
		
		$nextGame = 32 + ceil($gId/2);
		$nextSlot = ($gId % 2 == 1) ? 0 : 1; 

		$t1_safe = htmlspecialchars($t1_name, ENT_QUOTES, 'UTF-8');
		$t2_safe = htmlspecialchars($t2_name, ENT_QUOTES, 'UTF-8');

		echo "<div class='matchup' id='matchup_$gId'>
				<div class='team' data-value='$t1_safe' onclick='pickWinner($gId, \"input_game$gId\", $nextGame, $nextSlot)'>{$t1_seed}$t1_name</div>
				<div class='team' data-value='$t2_safe' onclick='pickWinner($gId, \"input_game$gId\", $nextGame, $nextSlot)'>{$t2_seed}$t2_name</div>
			  </div>";
	}
	echo "</div>";

	// Round 2
	echo "<div class='round'><h3>Round 2</h3>";
	foreach($r2Games as $gId) {
		$nextGame = 49 + floor(($gId-33)/2);
		$nextSlot = ($gId % 2 == 1) ? 0 : 1;

		echo "<div class='matchup' id='matchup_$gId'>
				<div class='team' id='slot_{$gId}_0' onclick='pickWinner($gId, \"input_game$gId\", $nextGame, $nextSlot)'>&nbsp;</div>
				<div class='team' id='slot_{$gId}_1' onclick='pickWinner($gId, \"input_game$gId\", $nextGame, $nextSlot)'>&nbsp;</div>
			  </div>";
	}
	echo "</div>";

	// Round 3 (Sweet 16)
	echo "<div class='round'><h3>Round of 16</h3>";
	foreach($r3Games as $gId) {
		$nextGame = 57 + floor(($gId-49)/2);
		$nextSlot = ($gId % 2 == 1) ? 0 : 1;
		
		echo "<div class='matchup' id='matchup_$gId'>
				<div class='team' id='slot_{$gId}_0' onclick='pickWinner($gId, \"input_game$gId\", $nextGame, $nextSlot)'>&nbsp;</div>
				<div class='team' id='slot_{$gId}_1' onclick='pickWinner($gId, \"input_game$gId\", $nextGame, $nextSlot)'>&nbsp;</div>
			  </div>";
	}
	echo "</div>";

	// Round 4 (Elite 8)
	echo "<div class='round'><h3>Elite 8</h3>";
	$gId = $r4Game;
	
	echo "<div class='matchup' id='matchup_$gId'>
			<div class='team' id='slot_{$gId}_0' onclick='pickWinner($gId, \"input_game$gId\", $destGameId, $destSlot)'>&nbsp;</div>
			<div class='team' id='slot_{$gId}_1' onclick='pickWinner($gId, \"input_game$gId\", $destGameId, $destSlot)'>&nbsp;</div>
		  </div>";
	echo "</div>";
	
	echo "</div>"; // End Region Container
}
?>

