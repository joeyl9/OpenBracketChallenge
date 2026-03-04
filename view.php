<?php

include("admin/database.php");
include("admin/functions.php");



// session_start(); // Handled in functions.php
// Auth Normalization (Session-only)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$user_email = isset($_SESSION['useremail']) ? $_SESSION['useremail'] : null;

$query = "SELECT closed FROM `meta` WHERE `id`=1";
$stmt = $db->query($query);
$meta = $stmt->fetch(PDO::FETCH_ASSOC);

$id = (int) $_GET['id'];
$query = "SELECT * FROM `brackets` WHERE `id` = :id"; //select entry
$stmt = $db->prepare($query);
$stmt->execute([':id' => $id]);
$picks = $stmt->fetch(PDO::FETCH_ASSOC);

// Access Control: Block if closed=0 AND not owner AND not admin
if($meta['closed'] == 0) {
    // Current User
    $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $is_owner = ($current_user_id > 0 && $current_user_id == $picks['user_id']);
    // Fallback Owner Check REMOVED for security


	$is_admin = (isset($_SESSION['admin']) && $_SESSION['admin'] == true);
	
	if(!$is_owner && !$is_admin) {
		$_SESSION['errors'] = "No peeking until submission is closed!";
		header('Location:index.php');
		exit();
	}
}

include("header.php");

if($picks['name'] != NULL)
{

	$team_query = "SELECT * FROM `master` WHERE `id`=1"; //select teams
	$stmt = $db->query($team_query);
	$team_data = $stmt->fetch(PDO::FETCH_ASSOC);
	
	$master_query = "SELECT * FROM `master` WHERE `id`=2"; //select winners
	$stmt = $db->query($master_query);
	$master_data = $stmt->fetch(PDO::FETCH_ASSOC);
	
	$loserMap = getLoserMap($db);
	$seedMap = getSeedMap($db);	
	
    // --- COMPARISON PREP ---
    $my_picks_json = "{}";
    $their_picks_json = "{}";
    $showcompare = false;

    // Capture Their Picks (Raw)
    $theirData = [];
    for($k=1; $k<=63; $k++) {
        $theirData[$k] = isset($picks[$k]) ? $picks[$k] : "";
    }
    $their_picks_json = json_encode($theirData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    // Fetch My Picks (Comparison)
    $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    // Only compare if logged in AND not viewing my own bracket
    if ($current_user_id > 0 && $picks['user_id'] != $current_user_id) {
        $stmtMe = $db->prepare("SELECT * FROM brackets WHERE user_id = ? AND type='main' LIMIT 1");
        $stmtMe->execute([$current_user_id]);
        $myBracket = $stmtMe->fetch(PDO::FETCH_ASSOC);
        
        if ($myBracket) {
            $showcompare = true;
            $myData = [];
            for($k=1; $k<=63; $k++) {
                $myData[$k] = isset($myBracket[$k]) ? $myBracket[$k] : "";
            }
            $my_picks_json = json_encode($myData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        }
    }
    // -----------------------
	
	for( $i=0; $i<64; $i++ )
	{
        // Safe access to data to prevent warnings
        $val = isset($team_data[$i]) ? $team_data[$i] : '';
        $seedInfo = isset($seedMap[$val]) ? $seedMap[$val] : '';
        if ($val !== '') {
            // Only add the dot if we actually have seed info
		    $team_data[$i] = ($seedInfo !== '') ? $seedInfo.". ".$val : $val;
        }
	}
	
	$query = "SELECT * FROM `scores` WHERE `id` = :id and scoring_type='main' "; //select entry
	$stmt = $db->prepare($query);
	$stmt->execute([':id' => $id]);
	$score_data = $stmt->fetch(PDO::FETCH_ASSOC);
	
	$query = "SELECT * FROM `best_scores` WHERE `id` = :id and scoring_type='main' "; //select entry
	$stmt = $db->prepare($query);
	$stmt->execute([':id' => $id]);
	$best_data = $stmt->fetch(PDO::FETCH_ASSOC);
	
	//get rank
	$query = "SELECT * FROM `scores`  WHERE scoring_type='main' ORDER BY `score` DESC";
	$result = $db->query($query);  
	$i=1;
	$rankCounter = 0;
	$prevScore = -1;
	while($user = $result->fetch(PDO::FETCH_ASSOC)) {
		// Print out the contents of each row into a table
		if( $user['score'] != $prevScore )
		{
			$rankCounter = $i;
		}
		
		if ($user['id'] == $id) {
			$rank = $rankCounter;
			break;
		}
		
		$prevScore = $user['score'];
		$i++;
			
	}
	
	$scoring = getScoringArray($db, 'main');
	$roundMap = getRoundMap();
	
	for($j=1;$j<64;$j++)
	{
		$pickDisplay = h($picks[$j]);
		$gameValue = $scoring[ $seedMap[$picks[$j]] ][ $roundMap[$j] ];
		$gameValueStr = " <span class=\"gamevalue\">(".$gameValue.")</span>";

        // Conditional Seed Display
        // seedMap returns NULL if key unset, so use !empty check
        $pickSeedVal = isset($seedMap[$picks[$j]]) ? $seedMap[$picks[$j]] : '';
		$pickSeed = (!empty($pickSeedVal)) ? "<span class=\"gamevalue\">".$pickSeedVal.". </span>" : "";

		$nextGameValue = ($roundMap[$j] < 6) ? $scoring[ $seedMap[$picks[$j]] ][ $roundMap[$j] + 1 ] : 0;
		$nextGameValueStr = " onmouseover=\"return displayNextRoundWinValue('".$nextGameValue."');\" onmouseout=\"return clearStatus();\"";

		if($master_data[$j] != NULL)
		{
			$masterDisplay = h($master_data[$j]);

			if($picks[$j] != $master_data[$j])
			{
                $correctSeedVal = isset($seedMap[$master_data[$j]]) ? $seedMap[$master_data[$j]] : '';
				$correctSeed = (!empty($correctSeedVal)) ? "<span class=\"gamevalue\">".$correctSeedVal.". </span>" : "";
				$correctValue = $scoring[ $seedMap[$master_data[$j]] ][ $roundMap[$j] ];
				$correctValueStr = " <span class=\"gamevalue\">(".$correctValue.")</span>";

				$nextCorrectGameValue = ($roundMap[$j] < 6) ? $scoring[ $seedMap[$master_data[$j]] ][ $roundMap[$j] + 1 ] : 0;
				$nextCorrectGameValueStr = " onmouseover=\"return displayNextRoundWinValue('".$nextCorrectGameValue."');\"";

				$picks[$j] = "<span class=\"strike\">".$pickSeed.$pickDisplay.$gameValueStr;
				$picks[$j] .= "</span>";
				$picks[$j] .= "<br/><span class=\"correction\"".$nextCorrectGameValueStr.">".$correctSeed.$masterDisplay.$correctValueStr;
				$picks[$j] .= "</span>";
			}

			if($picks[$j] == $master_data[$j])
			{
				$picks[$j] = "<span class=\"right\"".$nextGameValueStr.">" .$pickSeed.$pickDisplay.$gameValueStr;
				$picks[$j] .= "</span>";
			}
		}
		else if( isset($loserMap[$picks[$j]]) && $loserMap[$picks[$j]] == 1 )
		{
			$picks[$j] = "<span class=\"strike\">".$pickSeed.$pickDisplay.$gameValueStr;
			$picks[$j] .= "</span>";
		}
		else
		{
			$picks[$j] = "<span ".$nextGameValueStr.">".$pickSeed.$pickDisplay.$gameValueStr."</span>";
		}
	}

?>

<?php

include('bracket_view_module.php');

if ($showcompare) {
?>


<script>
var myPicks = <?php echo $my_picks_json; ?>;
var theirPicksRaw = <?php echo $their_picks_json; ?>;
var compareMode = false;

function toggleCompare() {
    compareMode = !compareMode;
    const btn = document.getElementById('compareBtn');
    
    if (compareMode) {
        btn.innerHTML = "❌ Stop Comparing";
        btn.style.background = "#ef4444";
        highlightDifferences();
    } else {
        btn.innerHTML = "⚔️ Compare with Me";
        btn.style.background = "var(--accent-orange)";
        clearDifferences();
    }
}

function highlightDifferences() {
    const teams = document.querySelectorAll('.team[data-gameid]');
    
    teams.forEach(el => {
        const gid = el.getAttribute('data-gameid');
        if(!gid) return;
        
        const myPick = myPicks[gid];
        const theirPick = theirPicksRaw[gid];
        
        // If picks differ
        if (myPick && theirPick && myPick !== theirPick) {
            // Highlight Conflict
            el.style.outline = "2px dashed #ef4444";
            el.style.opacity = "0.8";
            
            // Add 'My Pick' Label
            if (!el.querySelector('.my-pick-label')) {
                const label = document.createElement('div');
                label.className = 'my-pick-label';
                label.style.fontSize = "0.7em";
                label.style.color = "#ef4444";
                label.style.fontWeight = "bold";
                label.style.background = "rgba(0,0,0,0.8)";
                label.style.padding = "2px 4px";
                label.style.borderRadius = "4px";
                label.style.marginTop = "2px";
                label.innerHTML = "You: " + myPick;
                el.appendChild(label);
            }
        }
    });
}

function clearDifferences() {
    const teams = document.querySelectorAll('.team[data-gameid]');
    teams.forEach(el => {
        el.style.outline = "none";
        el.style.opacity = "1";
        const label = el.querySelector('.my-pick-label');
        if(label) label.remove();
    });
}
</script>
<?php
} ?>

<div id="main" class="full">
    <div class="content-card" style="width:98%; margin:0 auto; overflow:visible;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
            <div style="display:flex; align-items:center; gap:20px;">
                <?php
                if ($user_email) {
                     $avatar = isset($picks['avatar_url']) && $picks['avatar_url'] ? $picks['avatar_url'] : 'avatar.php?name='.urlencode(strip_tags($picks['name'])).'&background=random';
                     echo "<img src='" . htmlspecialchars($avatar) . "' style='width:50px; height:50px; border-radius:50%; border:2px solid var(--accent-orange);'>";
                     echo "<div>";
                     echo "<h2 style='margin:0; font-size:1.5rem; color:var(--text-light);'>" . htmlspecialchars(stripslashes($picks['name'])) . "</h2>";
                     if(isset($picks['person'])) echo "<div style='color:var(--text-muted); font-size:0.9rem;'>".htmlspecialchars(stripslashes($picks['person']))."</div>";
                     echo "</div>";
                } else {
                     echo "<h2 style='margin:0; font-size:1.5rem; color:var(--text-light);'>" . htmlspecialchars(stripslashes($picks['name'])) . "</h2>";
                }
                ?>
            </div>
            
            <div style="display:flex; gap:20px; align-items:center; flex-wrap:wrap;">
                <?php if($rank != "") { ?>
                <div class="stat-item" style="text-align:center;">
                    <div class="stat-label" style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Rank</div>
                    <div class="stat-value" style="font-size:1.2rem; font-weight:bold; color:var(--accent-orange);"><?php echo $rank; ?></div>
                </div>
                <?php } ?>
                 <?php if($score_data['score'] != "") { ?>
                <div class="stat-item" style="text-align:center;">
                    <div class="stat-label" style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Score</div>
                    <div class="stat-value" style="font-size:1.2rem; font-weight:bold; color:var(--text-light);"><?php echo $score_data['score']; ?></div>
                </div>
                <?php } ?>
                <div style="display:flex; gap:10px;">
                     <?php if($showcompare) { ?>
                     <button id="compareBtn" onclick="toggleCompare()" style="padding:8px 16px; background:var(--accent-orange); color:white; border:none; border-radius:6px; font-weight:bold; font-size:0.9em; cursor:pointer; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-code-compare"></i> Compare
                     </button>
                     <?php } ?>
                     <button onclick="downloadPDF(this)" style="padding:8px 16px; background:var(--accent-orange); color:var(--accent-text); border:none; border-radius:6px; font-weight:bold; font-size:0.9em; cursor:pointer; display:flex; align-items:center; gap:6px; transition:all 0.2s;" onmouseover="this.style.background='var(--accent-orange-hover)';" onmouseout="this.style.background='var(--accent-orange)';">
                        <i class="fa-solid fa-print"></i> Print
                    </button>
                    <?php 
                     if ($user_email && $picks['id']) {
                        echo "<a href='profile.php?id={$picks['id']}' style='padding:8px 16px; background:transparent; border:1px solid var(--accent-orange); color:var(--accent-orange); text-decoration:none; border-radius:6px; font-weight:bold; font-size:0.9em;'>View Profile</a>";
                     }
                    ?>
                </div>
            </div>
        </div>

        <?php
        viewBracket( $meta, $picks, $team_data, $rank, $score_data, $best_data, false, false );
        ?>
        <div style="margin-top:50px; padding-top:30px; border-top:1px solid var(--border-color);">
            <a name="comments"></a>
            <div style="margin-bottom:20px;">
    <h2 style="margin:0; color:var(--accent-orange);"><i class="fa-solid fa-comments"></i> Smack Talk</h2>
</div>
<div class="messages" style="max-height: 500px; overflow-y: auto; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px;">
    <?php
    // Fetch Current User Info First (for is_me check)
    $user = []; // Default empty
    if($user_email) {
        $query = "SELECT * FROM `brackets` WHERE `email` = :email LIMIT 0,1";
        $stmt = $db->prepare($query);
        $stmt->execute([':email' => $user_email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $posts = "SELECT c.time, c.content, c.from, c.bracket FROM `comments` c WHERE `bracket`=:id ORDER BY c.time ASC";
    $stmt = $db->prepare($posts);
    $stmt->execute([':id' => $id]);

    $has_comments = false;
    while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $has_comments = true;
        // Check if user exists before property access to avoid Warning
        $is_me = (isset($user['person']) && stripslashes($post['from']) == $user['person']); 
        
        $align = $is_me ? 'flex-end' : 'flex-start';
        $bg = $is_me ? 'var(--accent-orange)' : '#334155';
        $color = 'white';
        $metaAlign = $is_me ? 'right' : 'left';

        echo "<div style='display:flex; flex-direction:column; align-items:$align; margin-bottom:15px;'>";
        
        // Author Name
        if(!$is_me) {
            echo "<div style='font-size:0.8em; color:var(--text-muted); margin-bottom:2px; margin-left:5px;'>".h(stripslashes($post['from']))."</div>";
        }

        // Bubble
        echo "<div style='background:$bg; color:$color; padding:10px 15px; border-radius:15px; max-width:80%; font-size:0.95em; line-height:1.4; box-shadow:0 2px 4px rgba(0,0,0,0.2); position:relative;'>";
        echo h(stripslashes($post['content']));
        echo "</div>";

        // Time
        echo "<div style='font-size:0.7em; color:#64748b; margin-top:2px; margin-right:5px; text-align:$metaAlign;'>".timeBetween(strtotime($post['time']),time())."</div>";
        
        echo "</div>";
    }

    if (!$has_comments) {
        echo "<div style='text-align:center; color:#64748b; padding:20px; font-style:italic;'>No smack talk yet. Be the first!</div>";
    }
    ?>
</div>

<script>
// Auto-scroll to bottom of chat
document.addEventListener("DOMContentLoaded", function() {
    var msgContainer = document.querySelector(".messages");
    if(msgContainer) {
        msgContainer.scrollTop = msgContainer.scrollHeight;
    }
});
</script>

<br>
<h2>Add Smack Talk</h2><h3></h3>

<?php if ($user_email) { ?>
	<div id="addcomment">

	<form method="post" action="addcomment.php">
		
		
			<script type="text/javascript">
			 $(document).ready(function(){
			   //$("#from").val("<?php echo h($user['person']); ?>");
			   });
			</script>
		
			<p><div class="comment_field">From:</div>
                <!-- FIXED: Display name as text, send as hidden value -->
                <strong style="font-size:1.1em; color:var(--text-light);"><?php echo h($user['person']); ?></strong>
                <input type="hidden" name="from" id="from" value="<?php echo h($user['person']); ?>" />
            </p>
			<p><div class="comment_field">Comment:</div><textarea name="comment" id="comment" rows="12"></textarea></p>
			<input type="hidden" name="id" value="<?php echo $id ?>" />
            <?php csrf_field(); ?>
			<input type="submit" name="add" id="add" value="Submit" />
			<!--<ul id="response" /> -->
	</form>

	</div>
<?php } else { ?>
	Users must log in on the home page to post smack talk.
<?php } ?>
</div>


</body> 
</html> 
<?php

}else {

?> 
<div id="main"> 
  <div class="left_side"> 
    <h2 align="center">Sorry. That bracket does not exist.</h2> 
    <h2 align="center"><br /> 
      Please try again. </h2> 
    <p align="center"> 
      <input type=button value="Back" onClick="history.back()" /> 
    </p> 
  </div> 
  <div class="right_side"> 
    <?php include("sidebar.php"); ?> 
  </div> 
</div>
</div></div></div> 
</div> 
</body></html><?php } ?>
