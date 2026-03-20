<?php
include("header.php");
include("admin/functions.php");

// Strict check: Sweet 16 must be enabled to access this page
if( empty($meta['sweet16Competition']) ) {
    header("Location: index.php");
    exit();
}

// 1. Check Sweet 16 Closed Status
if(!empty($meta['sweet16_closed'])) {
    echo '<div id="main"><div class="left_side">Second Chance submission is closed.</div></div>';
    exit();
}

// 2. Check Sweet 16 Deadline
if(!empty($meta['sweet16_deadline'])) {
    $deadline = strtotime($meta['sweet16_deadline']);
    if(time() > $deadline) {
         echo '<div id="main"><div class="left_side">The deadline for Second Chance submissions has passed.</div></div>';
         exit();
    }
}

// 3. Check Registration Mode (Gates)
$reg_mode = isset($meta['sweet16_reg_mode']) ? $meta['sweet16_reg_mode'] : 0; // 0=Open, 1=Pass, 2=Token
$reg_pass_error = false;

// MODE 1: Password Restricted
if($reg_mode == 1) {
    $entered_pass = isset($_POST['reg_password_attempt']) ? $_POST['reg_password_attempt'] : '';
    $session_pass = isset($_SESSION['s16_reg_authorized']) ? $_SESSION['s16_reg_authorized'] : false;

    if(!$session_pass) {
        if($entered_pass === $meta['sweet16_reg_password']) {
            $_SESSION['s16_reg_authorized'] = true;
        } else {
             // Show Password Form
            echo '<div id="main" class="full"><div class="content-card" style="max-width:500px; margin:50px auto; text-align:center;">';
            echo '<h2 style="color:var(--accent-orange);">Password Required</h2>';
            echo '<p>This Second Chance tournament is password protected.</p>';
             if(isset($_POST['reg_password_attempt'])) echo '<p style="color:red;">Incorrect Password</p>';
            echo '<form method="post">';
            echo '<input type="password" name="reg_password_attempt" placeholder="Enter Registration Password" style="padding:10px; border-radius:4px; border:1px solid #444; background:#111; color:white; margin-bottom:15px; width:80%;"><br>';
            echo '<input type="submit" value="Enter" class="btn">';
            echo '</form></div></div>';
            include("footer.php");
            exit();
        }
    }
}

// MODE 2: Token Restricted
if($reg_mode == 2) {
    $token = isset($_GET['token']) ? $_GET['token'] : (isset($_SESSION['s16_reg_token']) ? $_SESSION['s16_reg_token'] : '');
    
    if($token === $meta['sweet16_reg_token'] && !empty($meta['sweet16_reg_token'])) {
         $_SESSION['s16_reg_token'] = $token; // Save to session
    } else {
        echo '<div id="main" class="full"><div class="content-card" style="max-width:500px; margin:50px auto; text-align:center;">';
        echo '<h2 style="color:#ef4444;"><i class="fa-solid fa-lock"></i> Restricted Access</h2>';
        echo '<p>You need a valid invitation link to join this Second Chance tournament.</p>';
        echo '</div></div>';
        include("footer.php");
        exit();
    }
}

// FETCH DATA
$master_query = "SELECT * FROM `master` WHERE `id`=2"; //select winners
$stmt = $db->query($master_query);
$winners = $stmt->fetch(PDO::FETCH_ASSOC);
    
$sweet16determined = true;
// Check if games 1-48 are decided (Round 1 & 2)
for( $i=1; $i <= 48; $i++)
{
    if( empty($winners[$i]) )
    {
        $sweet16determined = false;
        break;
    }
}

// If missing winner in first two rounds
if( $sweet16determined == false ) {
    echo '<div id="main"><div class="left_side">The Round of 16 field is not yet determined. All games in Round 1 & 2 must have winners in the Master Bracket.</div></div>';
    exit();
}

$seedMap = getSeedMap($db);

// Helper to get formatted name
function teamStr($name, $seedMap) {
    if(empty($name)) return "TBD";
    $seed = isset($seedMap[$name]) ? $seedMap[$name] : "?";
    return "<span style='color:var(--accent-orange); font-weight:bold; margin-right:5px;'>$seed</span> " . $name;
}

function teamVal($name) {
    return $name; // Just the name for value
}


if($user_id = (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null)) {
    $u_stmt = $db->prepare("SELECT email, name FROM users WHERE id=?");
    $u_stmt->execute([$user_id]);
    $u_res = $u_stmt->fetch(PDO::FETCH_ASSOC);
    $user_email = $u_res['email'];
    $u_name_display = !empty($u_res['name']) ? $u_res['name'] : $user_email;
} else {
    // Should be redirected by header check usually, but fallback
    $u_name_display = "Guest";
    $user_email = "Not Logged In";
}

// DUPLICATE/LIMIT CHECK
// Check how many 'sweet16' brackets this user has
$count_q = "SELECT COUNT(*) FROM `brackets` WHERE user_id=:uid AND `type`='sweet16'";
$stmt = $db->prepare($count_q);
$stmt->execute(array(':uid' => $user_id));
$bracket_count = $stmt->fetchColumn();

// Get Limit (Default to 1 if not set)
$limit = isset($meta['max_sweet16_brackets']) ? (int)$meta['max_sweet16_brackets'] : 1;

if($bracket_count >= $limit) {
    echo '<div id="main" style="padding:40px; text-align:center;">
            <div class="left_side" style="float:none; margin:0 auto; width:100%; max-width:800px;">
                <h2>Max Second Chance Brackets Reached</h2>
                <p>You have submitted ' . $bracket_count . ' of ' . $limit . ' allowed Second Chance bracket(s).</p>
                <br>
                <a href="dashboard.php" class="finish-btn" style="background:var(--accent-orange); color:white; padding:15px 30px; text-decoration:none; border-radius:5px; font-weight:bold;">GO TO DASHBOARD</a>
            </div>
          </div>
          </div>';
    include("footer.php");
    exit();
}
?>

<div id="main" style="max-width: 100%; padding: 0;">
<form method="post" name="bracket" id="bracket" action="bracket.php" style="width: 100%;" onsubmit="return validateForm()">
<?php csrf_field(); ?>
<input type="hidden" name="bracket_type" value="sweet16">

<div style="background: #111; padding: 20px; color: #fff; margin-bottom: 20px; border-bottom: 2px solid var(--accent-orange);">
	<h3 style="margin-top:0;">Second Chance Entry</h3>
	
    <div style="display:flex; flex-wrap:wrap; gap:20px; justify-content:center;">
        <div>
            <label>Bracket Name:</label><br>
            <input type="text" name="bracketname" required style="padding:10px; border-radius:4px; border:1px solid #444; background:#333; color:white; width:200px;">
        </div>

        <!-- Display User Info -->
        <div style="display:flex; align-items:center; gap:10px; background:rgba(255,255,255,0.1); padding:10px; border-radius:4px;">
            <i class="fa-solid fa-user-circle" style="font-size:2em; color:var(--accent-orange); opacity:0.8;"></i>
            <div>
                <div style="font-size:0.8em; color:var(--text-muted);">Logged in as</div>
                <div style="font-weight:bold;"><?php echo htmlspecialchars($u_name_display); ?></div>
            </div>
        </div>
        
        <!-- Hidden Inputs -->
        <input type="hidden" name="name" value="<?php echo htmlspecialchars($u_name_display); ?>">
        <input type="hidden" name="e-mail" value="<?php echo htmlspecialchars($user_email); ?>">
	</div>
</div>

<!-- Hidden Inputs for Games (Sweet 16 is Games 49-63) -->
<!-- Populate 1..48 with Master Winners so the database record is complete for legacy compatibility -->
<?php 
// Pre-fill Rounds 1 & 2 from Master logic
for($i=1; $i<=48; $i++) { 
    echo "<input type='hidden' name='game$i' value='".htmlspecialchars($winners[$i])."'>"; 
}
// Empty inputs for User Picks
for($i=49; $i<=63; $i++) { 
    echo "<input type='hidden' name='game$i' id='input_game$i' value=''>"; 
} 
?>

<div class="bracket-wrapper" style="background: transparent; width: 100% !important; display: flex; justify-content: space-between;">
	
    <!-- LEFT SIDE (Regions 1 & 2) -->
	<div class="bracket-split-left">
		
        <!-- REGION 1 (SOUTH) -->
        <div class="region-container">
            <!-- Sweet 16 Round -->
            <div class="round">
                <div style="text-align:center; color:var(--text-muted); font-size:0.8em; margin-bottom:5px;">ROUND OF 16</div>
                <!-- Game 49 -->
                <div class="matchup">
                     <div class="team" onclick="pickWinner(49, 'input_game49', 57, 0, this)" data-value="<?php echo htmlspecialchars($winners[33], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[33], $seedMap); ?></div>
                     <div class="team" onclick="pickWinner(49, 'input_game49', 57, 0, this)" data-value="<?php echo htmlspecialchars($winners[34], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[34], $seedMap); ?></div>
                </div>
                <!-- Game 50 -->
                <div class="matchup">
                     <div class="team" onclick="pickWinner(50, 'input_game50', 57, 1, this)" data-value="<?php echo htmlspecialchars($winners[35], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[35], $seedMap); ?></div>
                     <div class="team" onclick="pickWinner(50, 'input_game50', 57, 1, this)" data-value="<?php echo htmlspecialchars($winners[36], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[36], $seedMap); ?></div>
                </div>
            </div>
            <!-- Elite 8 -->
            <div class="round">
                <div style="text-align:center; color:var(--text-muted); font-size:0.8em; margin-bottom:5px;">QUARTERFINALS</div>
                <!-- Game 57 -->
                <div class="matchup">
                    <div class="team" onclick="pickWinner(57, 'input_game57', 61, 0, this)" id="slot_57_0">Wait...</div>
                    <div class="team" onclick="pickWinner(57, 'input_game57', 61, 0, this)" id="slot_57_1">Wait...</div>
                </div>
            </div>
        </div>

        <!-- REGION 2 (EAST) -->
        <div class="region-container" style="margin-top:20px;">
            <!-- Sweet 16 Round -->
            <div class="round">
                <div style="text-align:center; color:var(--text-muted); font-size:0.8em; margin-bottom:5px;">ROUND OF 16</div>
                <!-- Game 51 -->
                <div class="matchup">
                     <div class="team" onclick="pickWinner(51, 'input_game51', 58, 0, this)" data-value="<?php echo htmlspecialchars($winners[37], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[37], $seedMap); ?></div>
                     <div class="team" onclick="pickWinner(51, 'input_game51', 58, 0, this)" data-value="<?php echo htmlspecialchars($winners[38], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[38], $seedMap); ?></div>
                </div>
                <!-- Game 52 -->
                <div class="matchup">
                     <div class="team" onclick="pickWinner(52, 'input_game52', 58, 1, this)" data-value="<?php echo htmlspecialchars($winners[39], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[39], $seedMap); ?></div>
                     <div class="team" onclick="pickWinner(52, 'input_game52', 58, 1, this)" data-value="<?php echo htmlspecialchars($winners[40], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[40], $seedMap); ?></div>
                </div>
            </div>
            <!-- Elite 8 -->
            <div class="round">
                <div style="text-align:center; color:var(--text-muted); font-size:0.8em; margin-bottom:5px;">QUARTERFINALS</div>
                <!-- Game 58 -->
                <div class="matchup">
                    <div class="team" onclick="pickWinner(58, 'input_game58', 61, 1, this)" id="slot_58_0">Wait...</div>
                    <div class="team" onclick="pickWinner(58, 'input_game58', 61, 1, this)" id="slot_58_1">Wait...</div>
                </div>
            </div>
        </div>

	</div>

	<!-- CENTER (Final Four) -->
	<div class="bracket-center">
		<h2 style="text-align:center; color:#fff; font-size:1.2rem;">SEMIFINALS</h2>
		<!-- Final Four Game 1 -->
		<div class="matchup" id="matchup_61">
			<div class="team" onclick="pickWinner(61, 'input_game61', 63, 0, this)" id="slot_61_0">Winner Reg 1</div>
			<div class="team" onclick="pickWinner(61, 'input_game61', 63, 0, this)" id="slot_61_1">Winner Reg 2</div>
		</div>

		<!-- Final Four Game 2 -->
		<div class="matchup" id="matchup_62">
			<div class="team" onclick="pickWinner(62, 'input_game62', 63, 1, this)" id="slot_62_0">Winner Reg 3</div>
			<div class="team" onclick="pickWinner(62, 'input_game62', 63, 1, this)" id="slot_62_1">Winner Reg 4</div>
		</div>

		<h2 style="text-align:center; color:var(--accent-orange); font-size:1.4rem;">CHAMPIONSHIP</h2>
		<!-- Championship -->
		<div class="matchup" id="matchup_63" style="border: 2px solid var(--accent-orange);">
			<div class="team" onclick="pickWinner(63, 'input_game63', null, null, this)" id="slot_63_0">Winner Semi 1</div>
			<div class="team" onclick="pickWinner(63, 'input_game63', null, null, this)" id="slot_63_1">Winner Semi 2</div>
		</div>
		
		<div style="text-align:center; margin-top:20px;">
			<h3 style="color:#fff;">Champion</h3>
			<div id="champion_display" style="font-size:1.5em; font-weight:bold; color:var(--accent-orange); min-height:40px; margin-bottom: 20px;">?</div>
			
			<div style="background: #222; padding: 15px; border-radius: 5px;">
				<label style="color:#fff; font-weight:bold;">Tiebreaker</label><br>
				<span style="font-size:0.8rem; color:var(--text-muted);">(Total Points in Final Game)</span><br>
				<input type="number" name="tiebreaker" style="width:80px; text-align:center; margin-top:5px; padding:8px; font-weight:bold; color:black;" required>
				<br><br>
				<input type="submit" name="submit" value="Submit Bracket" class="finish-btn" style="width: 100%; white-space: normal; padding:10px 20px; font-size:1.1em; cursor:pointer; background: var(--accent-orange); color: white; border:none; border-radius:4px;">
			</div>
		</div>
	</div>

	<!-- RIGHT SIDE (Regions 3 & 4) -->
	<div class="bracket-split-right">
        
         <!-- REGION 3 (MIDWEST) - CORRECT ORDER: Sweet 16 (Right) -> Elite 8 (Inner) -->
        <div class="region-container">
            <!-- Sweet 16 Round (FIRST CHILD = FAR RIGHT in row-reverse) -->
            <div class="round">
                <div style="text-align:center; color:var(--text-muted); font-size:0.8em; margin-bottom:5px;">ROUND OF 16</div>
                <!-- Game 53 -->
                <div class="matchup">
                     <div class="team" onclick="pickWinner(53, 'input_game53', 59, 0, this)" data-value="<?php echo htmlspecialchars($winners[41], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[41], $seedMap); ?></div>
                     <div class="team" onclick="pickWinner(53, 'input_game53', 59, 0, this)" data-value="<?php echo htmlspecialchars($winners[42], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[42], $seedMap); ?></div>
                </div>
                <!-- Game 54 -->
                <div class="matchup">
                     <div class="team" onclick="pickWinner(54, 'input_game54', 59, 1, this)" data-value="<?php echo htmlspecialchars($winners[43], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[43], $seedMap); ?></div>
                     <div class="team" onclick="pickWinner(54, 'input_game54', 59, 1, this)" data-value="<?php echo htmlspecialchars($winners[44], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[44], $seedMap); ?></div>
                </div>
            </div>
            
            <!-- Elite 8 (SECOND CHILD = LEFT OF FAR RIGHT) -->
            <div class="round">
                <div style="text-align:center; color:var(--text-muted); font-size:0.8em; margin-bottom:5px;">QUARTERFINALS</div>
                <!-- Game 59 -->
                <div class="matchup">
                     <div class="team" onclick="pickWinner(59, 'input_game59', 62, 0, this)" id="slot_59_0">Wait...</div>
                     <div class="team" onclick="pickWinner(59, 'input_game59', 62, 0, this)" id="slot_59_1">Wait...</div>
                </div>
            </div>
        </div>

        <!-- REGION 4 (WEST) -->
        <div class="region-container" style="margin-top:20px;">
            <!-- Sweet 16 Round (Rightmost) -->
            <div class="round">
                <div style="text-align:center; color:var(--text-muted); font-size:0.8em; margin-bottom:5px;">ROUND OF 16</div>
                <!-- Game 55 -->
                <div class="matchup">
                     <div class="team" onclick="pickWinner(55, 'input_game55', 60, 0, this)" data-value="<?php echo htmlspecialchars($winners[45], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[45], $seedMap); ?></div>
                     <div class="team" onclick="pickWinner(55, 'input_game55', 60, 0, this)" data-value="<?php echo htmlspecialchars($winners[46], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[46], $seedMap); ?></div>
                </div>
                <!-- Game 56 -->
                <div class="matchup">
                     <div class="team" onclick="pickWinner(56, 'input_game56', 60, 1, this)" data-value="<?php echo htmlspecialchars($winners[47], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[47], $seedMap); ?></div>
                     <div class="team" onclick="pickWinner(56, 'input_game56', 60, 1, this)" data-value="<?php echo htmlspecialchars($winners[48], ENT_QUOTES, 'UTF-8'); ?>"><?php echo teamStr($winners[48], $seedMap); ?></div>
                </div>
            </div>
            
            <!-- Elite 8 (Inner) -->
            <div class="round">
                <div style="text-align:center; color:var(--text-muted); font-size:0.8em; margin-bottom:5px;">QUARTERFINALS</div>
                <!-- Game 60 -->
                <div class="matchup">
                     <div class="team" onclick="pickWinner(60, 'input_game60', 62, 1, this)" id="slot_60_0">Wait...</div>
                     <div class="team" onclick="pickWinner(60, 'input_game60', 62, 1, this)" id="slot_60_1">Wait...</div>
                </div>
            </div>
        </div>

	</div>
</div>
</form>
</div>

<script>
function validateForm() {
    var missing = [];
    // Check Games 49-63 (Sweet 16 onwards)
    for(var i=49; i<=63; i++) {
        var el = document.getElementById('input_game' + i);
        if(el) {
            var val = el.value;
            if(!val || val === "") {
                missing.push(i);
            }
        }
    }
    
    if(missing.length > 0) {
        alert("Please complete your bracket! You have missed " + missing.length + " game(s).");
        return false;
    }
    
    // Check Tiebreaker
    var tie = document.getElementsByName('tiebreaker')[0].value;
    if(!tie || tie === "") {
        alert("Please enter a tiebreaker value.");
        return false;
    }
    
    return true;
}

function pickWinner(gameId, inputId, nextGameId, nextSlotIndex, element) {
	// element is the clicked div
    
	var teamNameHTML = element.innerHTML;
	var teamValue = element.getAttribute('data-value'); 

    // Extract just name for value if needed, but data-value should satisfy
	if(!teamValue || teamValue.includes("Wait")) return; // Can't pick empty

	// Update hidden input
	document.getElementById(inputId).value = teamValue;

	// Visual Selection
	var parent = element.parentElement;
	var teams = parent.getElementsByClassName('team');
	for(var i=0; i<teams.length; i++) teams[i].classList.remove('selected');
	element.classList.add('selected');

	// Advance Logic
	if(nextGameId) {
		var nextSlotId = 'slot_' + nextGameId + '_' + nextSlotIndex;
		var nextSlot = document.getElementById(nextSlotId);
		if(nextSlot) {
			var oldValue = nextSlot.getAttribute('data-value');
			if(oldValue && oldValue !== teamValue) {
				clearDownstream(nextGameId, nextSlotIndex, oldValue);
			}
			nextSlot.innerHTML = teamNameHTML; // Copy HTML (with seed span)
			nextSlot.setAttribute('data-value', teamValue);
		}
	} else {
		// Champion
		document.getElementById('champion_display').innerHTML = teamNameHTML;
	}
}

function clearDownstream(gameId, slotIndex, cascadedValue) {
    var slotEl = document.getElementById('slot_' + gameId + '_' + slotIndex);
    if (!slotEl) return;

    var oldValue = cascadedValue || slotEl.getAttribute('data-value');
    if (!oldValue) return;

    var inputEl = document.getElementById('input_game' + gameId);
    if (!inputEl || inputEl.value !== oldValue) return;

    inputEl.value = '';

    var onClickAttr = slotEl.getAttribute('onclick');

    slotEl.innerHTML = '&nbsp;';
    slotEl.removeAttribute('data-value');
    slotEl.classList.remove('selected');

    if (onClickAttr) {
        var fullMatch = onClickAttr.match(/pickWinner[^(]*\(([^)]+)\)/);
        if (fullMatch) {
            var args = fullMatch[1].split(',').map(function(s){ return s.trim().replace(/['"]/g,''); });
            var nextGameId = args[2] !== 'null' ? parseInt(args[2]) : null;
            var nextSlotIdx = args[3] !== 'null' ? parseInt(args[3]) : null;

            if (nextGameId === null) {
                document.getElementById('champion_display').innerHTML = '?';
            } else {
                var nextSlotEl = document.getElementById('slot_' + nextGameId + '_' + nextSlotIdx);
                if (nextSlotEl && nextSlotEl.getAttribute('data-value') === oldValue) {
                    clearDownstream(nextGameId, nextSlotIdx, oldValue);
                }
            }
        }
    }
}
</script>

</body>
</html>
