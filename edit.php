<?php
session_start();
include_once("admin/database.php");
include_once("admin/functions.php");

// 1. Auth Checks
require_once __DIR__ . '/includes/require_login.php';
$user_email = $auth_user_email;

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	die("Invalid Request");
}
$bracket_id = $_GET['id'];

// 2. Fetch Bracket & Verify Owner
$query = "SELECT * FROM `brackets` WHERE `id`=:id";
$stmt = $db->prepare($query);
$stmt->execute(array(':id' => $bracket_id));
$bracket_data = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$bracket_data) {
	die("Bracket not found.");
}
if($bracket_data['email'] !== $user_email) {
	die("Access Denied: You do not own this bracket.");
}

// 3. Check Protocol (Closed?)
$meta_q = "SELECT * FROM `meta` WHERE `id`=1";
$stmt = $db->query($meta_q);
$meta = $stmt->fetch(PDO::FETCH_ASSOC);

$is_locked = false;
$b_type = isset($bracket_data['type']) ? $bracket_data['type'] : 'main';

if($b_type == 'sweet16') {
    if($meta['sweet16_closed'] == 1) $is_locked = true;
    if(!empty($meta['sweet16_deadline']) && time() > strtotime($meta['sweet16_deadline'])) $is_locked = true;
} else {
    if($meta['closed'] == 1) $is_locked = true;
    if(!empty($meta['deadline']) && time() > strtotime($meta['deadline'])) $is_locked = true;
}

if($is_locked) {
	die("Tournament is closed. Edits are no longer allowed.");
}

// 4. Fetch Teams (Masters)
$teams = "SELECT * FROM `master` WHERE `id`=1"; 
$stmt = $db->query($teams);
$teams = $stmt->fetch(PDO::FETCH_ASSOC);

$teamNames = "SELECT * FROM `master` WHERE `id`=1"; 
$stmt = $db->query($teamNames);
$teamNames = $stmt->fetch(PDO::FETCH_ASSOC);

$seedsQuery = "SELECT * FROM `master` WHERE `id`=4"; 
$stmt = $db->query($seedsQuery);
$seeds = $stmt->fetch(PDO::FETCH_ASSOC);

// Meta already fetched above


include("header.php");
?>

<div id="main" style="max-width: 100%; padding: 0;">
<form method="post" name="bracket" id="bracket" action="update_bracket.php" style="width: 100%;" onsubmit="return validateForm()">
<?php csrf_field(); ?>
<input type="hidden" name="bracket_id" value="<?php echo $bracket_id; ?>">

<div style="background: #222; padding: 20px; color: #fff; margin-bottom: 20px; border-bottom: 2px solid var(--accent-orange);">
	<h3 style="margin-top:0;">Edit Bracket: <?php echo htmlspecialchars(stripslashes($bracket_data['name'])); ?></h3>
	<div style="display:flex; flex-wrap:wrap; gap:20px; justify-content:center;">
		<div><label>Bracket Name:</label><br><input type="text" name="bracketname" value="<?php echo htmlspecialchars(stripslashes($bracket_data['name'])); ?>" required <?php echo ($bracket_id == 2) ? 'readonly style="padding:5px; background:#444; color:#ccc;"' : 'style="padding:5px;"'; ?>></div>
		<div><label>Your Name:</label><br><input type="text" name="name" value="<?php echo htmlspecialchars(stripslashes($bracket_data['person'])); ?>" required <?php echo ($bracket_id == 2) ? 'readonly style="padding:5px; background:#444; color:#ccc;"' : 'style="padding:5px;"'; ?>></div>
		<!-- Email Readonly -->
		<div><label>Email:</label><br><input type="email" name="e-mail" value="<?php echo htmlspecialchars($bracket_data['email']); ?>" readonly style="padding:5px; background:#444; color:#ccc;"></div>
		<!-- User password editing is handled within the profile settings view -->
	</div>
</div>


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
				<span style="font-size:0.8rem; color:#ccc;">(Total Points in Final Game)</span><br>
				<input type="number" name="tiebreaker" value="<?php echo htmlspecialchars($bracket_data['tiebreaker']); ?>" style="width:60px; text-align:center; margin-top:5px; padding:5px; font-weight:bold;" required>
				<br><br>
				<input type="submit" name="submit" value="Save Changes" class="finish-btn" style="width: 100%; white-space: normal; padding:10px 20px; font-size:1.1em; cursor:pointer; background: var(--accent-orange); color: white; border:none; border-radius:4px;">
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
    var sweet16Mode = <?php echo ($b_type == 'sweet16') ? 'true' : 'false'; ?>;
    var start = sweet16Mode ? 49 : 1;

    for(var i=start; i<=63; i++) {
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
    
    var tie = document.getElementsByName('tiebreaker')[0].value;
    if(!tie || tie === "") {
        alert("Please enter a tiebreaker value.");
        return false;
    }
    
    return true;
}

// Refactored to separate Logic from Event
function pickWinner(gameId, inputId, nextGameId, nextSlotIndex) {
	// Identify clicked team
	var target = event.target; 
	if(!target.classList.contains('team')) return;
	triggerSelect(target, gameId, inputId, nextGameId, nextSlotIndex);
}

function triggerSelect(target, gameId, inputId, nextGameId, nextSlotIndex) {
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
			var oldValue = nextSlot.getAttribute('data-value');
			if(oldValue && oldValue !== (teamValue || teamNameText)) {
				clearDownstream(nextGameId, nextSlotIndex, oldValue);
			}
			nextSlot.innerHTML = teamHTML; // Fixed: Use innerHTML to keep the seed span
			nextSlot.setAttribute('data-value', teamValue || teamNameText);
			nextSlot.classList.remove('selected'); // Reset next slot selection status
		}
	} else {
		// Champion
		document.getElementById('champion_display').innerHTML = teamHTML; // Fixed: Use innerHTML
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

// RESTORE PICKS
var savedPicks = <?php
	// Create JSON object of gameID => Pick
	$picks = array();
	for($i=1; $i<=63; $i++) {
		$col = (string)$i;
		if(isset($bracket_data[$col])) {
			$picks[$i] = stripslashes($bracket_data[$col]);
		}
	}
	echo json_encode($picks);
?>;

window.onload = function() {
	// Iterate through Rounds to ensure correct propagation order
	// Games 1-32 (Round 1)
	for(var g=1; g<=32; g++) restoreGame(g);
	// Games 33-48 (Round 2)
	for(var g=33; g<=48; g++) restoreGame(g);
	// Games 49-56 (Sweet 16)
	for(var g=49; g<=56; g++) restoreGame(g);
	// Games 57-60 (Elite 8)
	for(var g=57; g<=60; g++) restoreGame(g);
	// Games 61-62 (Final Four)
	for(var g=61; g<=62; g++) restoreGame(g);
	// Game 63 (Champ)
	restoreGame(63);
};

function restoreGame(gameId) {
	var pickedTeam = savedPicks[gameId];
	if(!pickedTeam) return;

	// Find the dom element in this matchup that matches the picked team
	var matchup = document.getElementById('matchup_' + gameId);
	if(!matchup) return;

	var teams = matchup.getElementsByClassName('team');
	var foundTarget = null;
	for(var i=0; i<teams.length; i++) {
		if(teams[i].getAttribute('data-value') === pickedTeam || teams[i].innerText === pickedTeam) {
			foundTarget = teams[i];
			break;
		}
	}

	if(foundTarget) {
		// Extract the next game ID and slot from the element's onclick attribute.
		// Expected format: onclick="pickWinner(1, 'input_game1', 33, 0)"
		var onClickAttr = foundTarget.getAttribute('onclick');
		// Parse params: pickWinner(61, 'input_game61', 63, 0)
		var match = onClickAttr.match(/pickWinner\(\d+, ['"]([^'"]+)['"], (\d+|null), (\d+|null)\)/);
		if(match) {
			var nextGame = match[2] === 'null' ? null : parseInt(match[2]);
			var nextSlot = match[3] === 'null' ? null : parseInt(match[3]);
			var inputId = 'input_game' + gameId;
			
			triggerSelect(foundTarget, gameId, inputId, nextGame, nextSlot);
		}
	}
}

</script>

</body>
</html>

<?php
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
