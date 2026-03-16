<?php
include 'functions.php';
validatecookie();
include("header.php");

// Fetch Teams (Master ID=1)
$teamsQuery = "SELECT * FROM `master` WHERE `id`=1"; 
$stmt = $db->query($teamsQuery);
if(!($teams = $stmt->fetch(PDO::FETCH_ASSOC))) {
    echo "<div id='main'><div class='full text-center'>
            <h2>Tournament Not Initialized</h2>
            <p>You must initialize the bracket (load teams) before you can edit the master bracket.</p>
            <br>
            <a href='index.php' class='btn-outline'>&larr; Back to Dashboard</a>
          </div></div>";
    include("../footer.php");
	exit();
}

// Prevent the default Administrator account from creating or viewing personal brackets.
// The default Admin is only allowed to edit the Master Bracket (ID=0).

$id = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : 0);

// Restrict "Break Glass" Admin from Creating/Editing PERSONAL brackets,
// but ALLOW editing the Master Bracket (ID=0).
if(is_admin() && !isset($_SESSION['user_id'])) {
    if($id != 0) {
        echo "<div id='main'><div class='full text-center'>
                <h2>Admin Restricted</h2>
                <p>The default Administrator account cannot create personal brackets.</p>
                <p>Please create a separate user account to participate.</p>
                <br>
                <a href='index.php' class='btn-outline'>&larr; Back to Dashboard</a>
              </div></div>";
        include("../footer.php");
        exit();
    }
}

// Fetch Picks (Master ID=2 or User Bracket)
$id = isset($_POST['id']) ? $_POST['id'] : 0;

if($id == 0) {
	$query = "SELECT * FROM `master` WHERE `id`=2";
	$stmt = $db->query($query);
	$picks = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$picks) $picks = array(); // Initialize if empty
	$bracketName = "The Master Bracket";
    $picks['name'] = "Master Bracket";
}
else {
	$query = "SELECT * FROM `brackets` WHERE `id` = ?"; 
	$stmt = $db->prepare($query);
	$stmt->execute([$id]);
	$picks = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$picks) die("Bracket not found.");
	$bracketName = $picks['name'] . "'s Bracket";
}

// Tiebreaker logic
if( $id!=0 ) {
	$tb = $picks['tiebreaker'];
}
else {
	$tb = $meta['tiebreaker'];
}

// Safety Lock
$isLocked = false;
$lockMsg = "";
// If editing Master Bracket (id=0) AND Tournament is NOT Closed
if($id == 0 && $meta['closed'] == 0) {
    $isLocked = true;
    $lockMsg = "SAFETY LOCK: The Master Bracket cannot be edited while the tournament is OPEN. Games must start before you can advance teams.";
}

?>

<div id="main" style="max-width: 100%; box-sizing: border-box;">
	<div class="full">
		<h2 style="margin-top:0;"><?php echo $bracketName; ?></h2>
        <div style="margin-bottom: 20px;">
            <a href="index.php" class="btn-outline">&larr; Back to Dashboard</a>
        </div>
		<form method="post" name="bracket" class="bracket" id="bracket" action="update.php?id=<?php echo $id?>" style="width: 100%;">
			<input type="hidden" name="id" value="<?php echo $id; ?>">

			<div style="background: rgba(255,255,255,0.05); padding: 20px; color: var(--text-light); margin-bottom: 20px; border-bottom: 2px solid var(--accent-orange); border-radius: 8px;">
				
                <?php if($isLocked) { ?>
                <div style="background: #ef4444; color: white; padding: 15px; border-radius: 6px; margin-bottom: 10px; font-weight: bold; text-align: center;">
                    <i class="fas fa-lock"></i> <?php echo $lockMsg; ?>
                </div>
                <?php } ?>

				<p class="highlight" style="font-size: 0.9em; color: var(--text-light);"><em><strong>Please note:</strong></em> Click a team to advance it. Changes overwrite the existing data.</p>
				
				<?php if($id != 0) { ?>
				<div style="margin-top: 10px;">
					<strong>Editing User:</strong> <?php echo htmlspecialchars($picks['person']); ?> (<?php echo htmlspecialchars($picks['email']); ?>)
				</div>
				<?php } ?>
			</div>

			<!-- Hidden Inputs -->
			<?php for($i=1; $i<=63; $i++) { 
				echo "<input type='hidden' name='$i' id='input_$i' value=''>"; 
			} ?>

            <div class="bracket-wrapper" style="background: transparent; width: 100% !important; display: flex; justify-content: space-between; <?php if($isLocked) echo 'pointer-events: none; opacity: 0.6; filter: grayscale(1);'; ?>">
				<!-- LEFT SIDE -->
				<div class="bracket-split-left">
					<?php renderAdminRegion($meta['region1'], 1, array(1,2,3,4,5,6,7,8), array(33,34,35,36), array(49,50), 57, $teams, 61, 0); ?>
					<?php renderAdminRegion($meta['region2'], 17, array(9,10,11,12,13,14,15,16), array(37,38,39,40), array(51,52), 58, $teams, 61, 1); ?>
				</div>

				<!-- CENTER -->
				<div class="bracket-center">
					<h2 style="text-align:center; color:var(--text-light); font-size:1.2rem;">SEMIFINALS</h2>
					
					<!-- Final Four Matchups -->
					<div class="matchup" id="matchup_61">
						<div class="team" onclick="pickWinner(61, 'input_61', 63, 0)" id="slot_61_0">Waiting for <?php echo $meta['region1']?></div>
						<div class="team" onclick="pickWinner(61, 'input_61', 63, 0)" id="slot_61_1">Waiting for <?php echo $meta['region2']?></div>
					</div>

					<div class="matchup" id="matchup_62">
						<div class="team" onclick="pickWinner(62, 'input_62', 63, 1)" id="slot_62_0">Waiting for <?php echo $meta['region3']?></div>
						<div class="team" onclick="pickWinner(62, 'input_62', 63, 1)" id="slot_62_1">Waiting for <?php echo $meta['region4']?></div>
					</div>

					<h2 style="text-align:center; color:var(--accent-orange); font-size:1.4rem;">CHAMPIONSHIP</h2>
					<div class="matchup" id="matchup_63" style="border: 2px solid var(--accent-orange);">
						<div class="team" onclick="pickWinner(63, 'input_63', null, null)" id="slot_63_0">Winner Semi 1</div>
						<div class="team" onclick="pickWinner(63, 'input_63', null, null)" id="slot_63_1">Winner Semi 2</div>
					</div>
					
					<div style="text-align:center; margin-top:20px;">
						<h3 style="color:var(--text-light);">Champion</h3>
						<div id="champion_display" style="font-size:1.5em; font-weight:bold; color:var(--accent-orange); min-height:40px; margin-bottom: 20px;">?</div>
						
						<div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 5px;">
							<label style="color:var(--text-light); font-weight:bold;">Tiebreaker</label><br>
							<input type="text" name="tiebreaker" value="<?php echo htmlspecialchars((string)$tb); ?>" style="width:60px; text-align:center; margin-top:5px; padding:5px; font-weight:bold; background:var(--secondary-blue); color:var(--text-light); border:1px solid var(--border-color);" size="10" maxlength="3">
							<br><br>
							<?php if(!$isLocked) { ?>
							<input type="submit" name="Submit" value="Save Bracket" class="finish-btn" style="width: 100%; white-space: normal; padding:10px 20px; font-size:1.1em; cursor:pointer; background: var(--accent-orange); color: white; border:none; border-radius:4px;">
							<?php } else { ?>
							<button disabled style="background:var(--border-color); color:var(--text-muted); width:100%; padding:10px; border:none; border-radius:4px; cursor:not-allowed;">LOCKED</button>
							<?php } ?>
						</div>
					</div>
				</div>

				<!-- RIGHT SIDE -->
				<div class="bracket-split-right">
					<?php renderAdminRegion($meta['region3'], 33, array(17,18,19,20,21,22,23,24), array(41,42,43,44), array(53,54), 59, $teams, 62, 0); ?>
					<?php renderAdminRegion($meta['region4'], 49, array(25,26,27,28,29,30,31,32), array(45,46,47,48), array(55,56), 60, $teams, 62, 1); ?>
				</div>
			</div>
		</form>
	</div>

</div>

<script>
function pickWinner(gameId, inputId, nextGameId, nextSlotIndex) {
	var target = event.target; 
	if(!target.classList.contains('team')) return;

    // Toggle Logic
    if(target.classList.contains('selected')) {
        // Deselect
        target.classList.remove('selected');
        document.getElementById(inputId).value = ''; // Clear hidden input
        
        // Propagate Clear
        clearNextSlot(nextGameId, nextSlotIndex);
        return;
    }

    // Select Logic
	triggerSelect(target, gameId, inputId, nextGameId, nextSlotIndex);
}

function triggerSelect(target, gameId, inputId, nextGameId, nextSlotIndex) {
	var teamName = target.innerText;
	var teamValue = target.getAttribute('data-value') || teamName;

	if(!teamName || teamName.includes("Waiting")) return;

	document.getElementById(inputId).value = teamValue;

	var parent = target.parentElement;
	var teams = parent.getElementsByClassName('team');
	for(var i=0; i<teams.length; i++) teams[i].classList.remove('selected');
	target.classList.add('selected');

	if(nextGameId) {
		var nextSlotId = 'slot_' + nextGameId + '_' + nextSlotIndex;
		var nextSlot = document.getElementById(nextSlotId);
		if(nextSlot) {
			nextSlot.innerText = teamName;
			nextSlot.setAttribute('data-value', teamValue);
			nextSlot.classList.remove('selected'); 
            // Note: Overwriting a slot does not auto-advance or clear subsequent rounds.
            // Users should toggle their current selection off before selecting a new team.
		}
	} else {
		document.getElementById('champion_display').innerText = teamName;
	}
}

function clearNextSlot(gameId, slotIndex) {
    if(!gameId) {
        // Champion Display
        document.getElementById('champion_display').innerText = "?";
        return;
    }

    var slotId = 'slot_' + gameId + '_' + slotIndex;
    var slot = document.getElementById(slotId);
    if(!slot) return;

    // Reset this slot
    slot.innerText = (gameId >= 61) ? "Waiting..." : "&nbsp;"; // Formatting choice
    slot.setAttribute('data-value', '');
    
    // Check if this slot was ITSELF selected as a winner
    if(slot.classList.contains('selected')) {
        slot.classList.remove('selected');
        
        // Clear hidden input for THIS game
        var inputId = 'input_' + gameId;
        var input = document.getElementById(inputId);
        if(input) input.value = '';

        // Recurse to next game
        // Need to parse onclick to find where it goes
        var onClickAttr = slot.getAttribute('onclick');
        if(onClickAttr) {
            var match = onClickAttr.match(/pickWinner\(\d+, ['"]([^'"]+)['"], (\d+|null), (\d+|null)\)/);
            if(match) {
                var nextGame = match[2] === 'null' ? null : parseInt(match[2]);
                var nextSlot = match[3] === 'null' ? null : parseInt(match[3]);
                clearNextSlot(nextGame, nextSlot);
            }
        }
    }
}

var savedPicks = <?php
	$jsPicks = array();
	for($i=1; $i<=63; $i++) {
		if(isset($picks[$i])) {
			$jsPicks[$i] = stripslashes($picks[$i]);
		}
	}
	echo json_encode($jsPicks);
?>;

window.onload = function() {
	for(var g=1; g<=32; g++) restoreGame(g);
	for(var g=33; g<=48; g++) restoreGame(g);
	for(var g=49; g<=56; g++) restoreGame(g);
	for(var g=57; g<=60; g++) restoreGame(g);
	for(var g=61; g<=62; g++) restoreGame(g);
	restoreGame(63);
};

function restoreGame(gameId) {
	var pickedTeam = savedPicks[gameId];
	if(!pickedTeam) return;

	var matchup = document.getElementById('matchup_' + gameId);
	if(!matchup) return;

	var teams = matchup.getElementsByClassName('team');
	var foundTarget = null;

	for(var i=0; i<teams.length; i++) {
		var tVal = teams[i].getAttribute('data-value');
        var tText = teams[i].innerText;
		if(tVal === pickedTeam || tText === pickedTeam) {
			foundTarget = teams[i];
			break;
		}
	}

	if(foundTarget) {
		var onClickAttr = foundTarget.getAttribute('onclick');
		var match = onClickAttr.match(/pickWinner\(\d+, ['"]([^'"]+)['"], (\d+|null), (\d+|null)\)/);
		if(match) {
			var nextGame = match[2] === 'null' ? null : parseInt(match[2]);
			var nextSlot = match[3] === 'null' ? null : parseInt(match[3]);
			var inputId = 'input_' + gameId;
			
			triggerSelect(foundTarget, gameId, inputId, nextGame, nextSlot);
		}
	}
}
</script>

<?php include('footer.php'); ?>
</body>
</html>

<?php
function renderAdminRegion($name, $startTeamIndex, $r1Games, $r2Games, $r3Games, $r4Game, $teams, $destGameId, $destSlot) {
	echo "<div class='region-container'>";
	echo "<div class='round'><h3>$name Round 1</h3>";
	foreach($r1Games as $gId) {
		$t1_idx = ($gId * 2) - 1;
		$t2_idx = $gId * 2;
		$t1_name = isset($teams[$t1_idx]) ? $teams[$t1_idx] : "TBD";
		$t2_name = isset($teams[$t2_idx]) ? $teams[$t2_idx] : "TBD";
		$nextGame = 32 + ceil($gId/2);
		$nextSlot = ($gId % 2 == 1) ? 0 : 1; $t1_safe = htmlspecialchars($t1_name, ENT_QUOTES, 'UTF-8'); $t2_safe = htmlspecialchars($t2_name, ENT_QUOTES, 'UTF-8'); 
		echo "<div class='matchup' id='matchup_$gId'>
				<div class='team' data-value='$t1_safe' onclick='pickWinner($gId, \"input_$gId\", $nextGame, $nextSlot)'>$t1_name</div>
				<div class='team' data-value='$t2_safe' onclick='pickWinner($gId, \"input_$gId\", $nextGame, $nextSlot)'>$t2_name</div>
			  </div>";
	}
	echo "</div>";

	echo "<div class='round'><h3>Round 2</h3>";
	foreach($r2Games as $gId) {
		$nextGame = 49 + floor(($gId-33)/2);
		$nextSlot = ($gId % 2 == 1) ? 0 : 1; $t1_safe = htmlspecialchars($t1_name, ENT_QUOTES, 'UTF-8'); $t2_safe = htmlspecialchars($t2_name, ENT_QUOTES, 'UTF-8');
		echo "<div class='matchup' id='matchup_$gId'>
				<div class='team' id='slot_{$gId}_0' onclick='pickWinner($gId, \"input_$gId\", $nextGame, $nextSlot)'>&nbsp;</div>
				<div class='team' id='slot_{$gId}_1' onclick='pickWinner($gId, \"input_$gId\", $nextGame, $nextSlot)'>&nbsp;</div>
			  </div>";
	}
	echo "</div>";

	echo "<div class='round'><h3>Round of 16</h3>";
	foreach($r3Games as $gId) {
		$nextGame = 57 + floor(($gId-49)/2);
		$nextSlot = ($gId % 2 == 1) ? 0 : 1; $t1_safe = htmlspecialchars($t1_name, ENT_QUOTES, 'UTF-8'); $t2_safe = htmlspecialchars($t2_name, ENT_QUOTES, 'UTF-8');
		echo "<div class='matchup' id='matchup_$gId'>
				<div class='team' id='slot_{$gId}_0' onclick='pickWinner($gId, \"input_$gId\", $nextGame, $nextSlot)'>&nbsp;</div>
				<div class='team' id='slot_{$gId}_1' onclick='pickWinner($gId, \"input_$gId\", $nextGame, $nextSlot)'>&nbsp;</div>
			  </div>";
	}
	echo "</div>";

	echo "<div class='round'><h3>Quarterfinals</h3>";
	$gId = $r4Game;
	echo "<div class='matchup' id='matchup_$gId'>
			<div class='team' id='slot_{$gId}_0' onclick='pickWinner($gId, \"input_$gId\", $destGameId, $destSlot)'>&nbsp;</div>
			<div class='team' id='slot_{$gId}_1' onclick='pickWinner($gId, \"input_$gId\", $destGameId, $destSlot)'>&nbsp;</div>
		  </div>";
	echo "</div>";
	echo "</div>"; 
}
?>




