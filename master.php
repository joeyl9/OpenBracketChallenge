<?php
include("admin/functions.php");
include("header.php");

$stmt = $db->query("SELECT * FROM `master` WHERE `id`=1");
$team_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Get value for each game
$seedMap = getSeedMap($db);
$roundMap = getRoundMap();

$scoring = getScoringArray($db, 'main');

$stmt = $db->query("SELECT * FROM `master` WHERE `id`=2");
$picks = $stmt->fetch(PDO::FETCH_ASSOC);
if(!is_array($picks)) $picks = [];

$totalScore = 0;

for( $i=1; $i<65; $i++ )
{
	if(!isset($picks[$i])) $picks[$i] = NULL;
	
	$seed = isset($seedMap[$team_data[$i]]) ? $seedMap[$team_data[$i]] : '';
	$team_data[$i] = ($seed !== '' ? $seed.". " : "").$team_data[$i];

	if( $picks[$i] != NULL && $seed !== '' && isset($scoring[$seed]) )
	{
		$correctValue = $scoring[ $seed ][ $roundMap[$i]  ];
		$correctValueStr = " <span class=\"gamevalue\">(".$correctValue.")</span>";
		$totalScore += $correctValue;
		$picks[$i] = $picks[$i].$correctValueStr;
	}
}

$score_data['score'] = $totalScore;

$stmt = $db->query("SELECT * FROM `meta` WHERE `id`=1");
$meta = $stmt->fetch(PDO::FETCH_ASSOC);

$picks['name'] = "Master Bracket";
$rank = "";
$best_data = ['score' => ''];

include('bracket_view_module.php');
?>
<div id="main" class="full">
    <div class="content-card" style="width:98%; margin:0 auto; overflow-x:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
            <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                <h2 style="margin:0; color:var(--accent-orange);"><i class="fa-solid fa-scroll"></i> Master Bracket</h2>
                <button onclick="downloadPDF(this)" style="padding:8px 16px; background:var(--accent-orange); color:var(--accent-text); border:none; border-radius:6px; font-weight:bold; font-size:0.9em; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:6px;" onmouseover="this.style.background='var(--accent-orange-hover)';" onmouseout="this.style.background='var(--accent-orange)';">
                    <i class="fa-solid fa-print"></i> Print
                </button>
            </div>
            <a href="choose.php" style="padding:8px 16px; background:var(--accent-orange); color:var(--accent-text); border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9em; transition:background 0.2s;"><i class="fa-solid fa-arrow-left"></i> Back to Standings</a>
        </div>

        <?php
        viewBracket( $meta, $picks, $team_data, $rank, $score_data, $best_data, false, false );
        ?>
    </div>
</div>




</div>
</body>
</html>
