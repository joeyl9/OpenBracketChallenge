<?php
include("admin/functions.php");
include("header.php");

$if_data = array();

if( isset($_GET['id']) && $_GET['id'] != null )
{
	$possible_endgame_query = "SELECT * FROM `end_games` e WHERE `id`=:id";
	$stmt = $db->prepare($possible_endgame_query);
	$stmt->execute([':id' => $_GET['id']]);
	$if_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
else
{
	//clean input
	for($i=49;$i<64;++$i)
	{
		if(isset($_POST['game'.$i])) {
			$if_data[$i] = $_POST['game'.$i];
		}
	}
}
?>

<div id="main">		

			<div class="left_side">

				<h2>What If? </h2>

				<h3>WHERE WOULD YOU RANK?  </h3>

				<div id="border" align="center">

	
	<table width="600" border="1" align="center" style="border-collapse: collapse;">

	<tr>

		<td width="82"><div align="center"><strong>POSITION</strong></div></td>

		<td width="314"><div align="center"> <strong>NAME</strong></div></td>

		<td width="80">							<p align="center"><strong>SCORE</strong></p></td>

	</tr>

	
	<?php
	
	// if all ARE filled in, show a ranking
	$allowedScoring = ['main', 'geometric', 'espn'];
	$rawScoring = isset($_GET['scoring_type']) ? $_GET['scoring_type'] : 'main';
	$scoringType = in_array($rawScoring, $allowedScoring) ? $rawScoring : 'main';
	
	$custompoints = getScoringArray($db, $scoringType);
	$seedMap = getSeedMap($db);
	$roundMap = getRoundMap();
	$commentMap = getCommentsMap($db);

	$stmt = $db->query("SELECT * FROM `master` WHERE `id`=2");
	$master_data = $stmt->fetch(PDO::FETCH_ASSOC);


	$stmt = $db->query("SELECT * FROM `brackets`");
	$info = array();
	$i = 0;
	
	while ($user_bracket = $stmt->fetch(PDO::FETCH_ASSOC))
	{
		$score = 0;

		for($j=1;$j<49;++$j)
		{
			if(isset($user_bracket[$j]) && isset($master_data[$j]) && $user_bracket[$j] == $master_data[$j] && $user_bracket[$j] != "" )
			{
				$seedvalue = $seedMap[ $master_data[$j] ];
				$score += $custompoints[ $seedvalue ][ $roundMap[$j] ];
			}
		}
		for($j=49;$j<64;++$j)
		{
			// Check against if_data scenario
			if(isset($user_bracket[$j]) && isset($if_data[$j]) && $user_bracket[$j] == $if_data[$j])
			{
				$seedvalue = $seedMap[ $user_bracket[$j] ];
				$score += $custompoints[ $seedvalue ][ $roundMap[$j] ];
			}
		}

		$info[$i] = array($score, $user_bracket['id'], $user_bracket['name'], $user_bracket['email']);
		$i++;
	}


	rsort($info);
	
	$rankCounter = 1;
	$rank = 1;
	$prev_score = -1;
	$top_score = -1;

	for($j=0;$j<$i;$j++)
	{
		$score = $info[$j][0];
		$id = $info[$j][1];
		$name = $info[$j][2];
		$useremail = $info[$j][3];
		
		if( $j==0 ) 
		{
			$top_score = $score;
			$prev_score = $score;
			$rankCounter = 1;
			$rank=1;
		}
		if( $score != $prev_score )
		{
			$prev_score = $score;
			$rank = $rankCounter;
		}
				
		if (isset($_SESSION['useremail']) && strtolower($useremail) == strtolower($_SESSION['useremail']) && $useremail != "")
		{
			echo '<tr class="thisuser">';
		}
		else
		{
			echo "<tr>";
		}
		// Print out the contents of each row into a table
		echo "<td align='right'>"; 

		echo $rank;

		echo "</td><td>"; 

		echo "<a href=\"view.php?id=".$id."\">".h(stripslashes($name))."</a>";
		if (isset($commentMap[$id]) && $commentMap[$id] > 0) {
			echo " <span class=\"recentComment\"><a href='view.php?id=".$id."#comments'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a></span>";
		}

		echo "</td><td>"; 

		echo $score;					

		echo "</td></tr>";
		
		$rankCounter++;

	}
	?>

				</table>

				</div>

			</div>
			
			<div class="right_side"><?php include("sidebar.php"); ?>

			</div>

		</div>

		



	</div>

</body>

</html>
