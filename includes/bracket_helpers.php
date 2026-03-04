<?php

function getScoringArray($db, $type)
{
	if( $type == NULL )
	{
		$type = 'main';
	}
	$scoring_query = "SELECT * FROM `scoring` WHERE `type` = :type order by `seed`";
	$stmt = $db->prepare($scoring_query);
	$stmt->execute([':type' => $type]);
	
	$custompoints = array();
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
	{
		$custompoints[$row['seed' ]][1] =	$row['1'];
		$custompoints[$row['seed' ]][2] =	$row['2'];
		$custompoints[$row['seed' ]][3] =	$row['3'];
		$custompoints[$row['seed' ]][4] =	$row['4'];
		$custompoints[$row['seed' ]][5] =	$row['5'];
		$custompoints[$row['seed' ]][6] =	$row['6'];
	}
	
	return $custompoints;
}

function getRoundMap()
{
	$roundMap = array();
	for($j=1;$j<64;$j++)
	{
		$roundMap[$j] = 6-floor(log( 64-$j, 2));
	}
	
	return $roundMap;
}

function getSeedMap($db)
{
	$seed_query = "SELECT * FROM `master` WHERE `id`=4"; //seeds
	$stmt = $db->query($seed_query);
	$seed_data = $stmt->fetch(PDO::FETCH_ASSOC);
	
	$team_query = "SELECT * FROM `master` WHERE `id`=1"; //teams
	$stmt = $db->query($team_query);
	$stmt = $db->query($team_query);
	$team_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seed_data || !$team_data) {
        return array();
    }
	
	$seedMap = array();
	for ($k=1;$k<65;++$k)
	{
		$seedMap[ $team_data[$k] ] = $seed_data[$k];
	}
	
	return $seedMap;
}

function getHistoricalProbabilities()
{
	$probabilities[1][1][16] = 1.000;
	$probabilities[1][16][1] = 0.0;
	$probabilities[1][2][15] = 0.952;
	$probabilities[1][15][2] = 0.048;
	$probabilities[1][3][14] = 0.833;
	$probabilities[1][14][3] = 0.167;
	$probabilities[1][4][13] = 0.798;
	$probabilities[1][13][4] = 0.202;
	$probabilities[1][5][12] = 0.679;
	$probabilities[1][12][5] = 0.321;
	$probabilities[1][6][11] = 0.702;
	$probabilities[1][11][6] = 0.298;
	$probabilities[1][7][10] = 0.607;
	$probabilities[1][10][7] = 0.393;
	$probabilities[1][8][9] = 0.452;
	$probabilities[1][9][8] = 0.548;
	$probabilities[2][1][8] = 0.763;
	$probabilities[2][8][1] = 0.237;
	$probabilities[2][1][9] = 0.935;
	$probabilities[2][9][1] = 0.065;
	$probabilities[2][4][5] = 0.556;
	$probabilities[2][5][4] = 0.444;
	$probabilities[2][4][12] = 0.545;
	$probabilities[2][12][4] = 0.455;
	$probabilities[2][5][13] = 0.833;
	$probabilities[2][13][5] = 0.167;
	$probabilities[2][12][13] = 0.800;
	$probabilities[2][13][12] = 0.2;
	$probabilities[2][3][6] = 0.500;
	$probabilities[2][6][3] = 0.5;
	$probabilities[2][3][11] = 0.682;
	$probabilities[2][11][3] = 0.318;
	$probabilities[2][6][14] = 0.818;
	$probabilities[2][14][6] = 0.182;
	$probabilities[2][11][14] = 1.000;
	$probabilities[2][14][11] = 0.0;
	$probabilities[2][2][7] = 0.680;
	$probabilities[2][7][2] = 0.32;
	$probabilities[2][2][10] = 0.533;
	$probabilities[2][10][2] = 0.467;
	$probabilities[2][7][15] = 1.000;
	$probabilities[2][15][7] = 0.0;
	$probabilities[2][10][15] = 1.000;
	$probabilities[2][15][10] = 0.0;
	$probabilities[3][1][4] = 0.700;
	$probabilities[3][4][1] = 0.3;
	$probabilities[3][1][5] = 0.815;
	$probabilities[3][5][1] = 0.185;
	$probabilities[3][1][12] = 1.000;
	$probabilities[3][12][1] = 0.0;
	$probabilities[3][1][13] = 1.000;
	$probabilities[3][13][1] = 0.0;
	$probabilities[3][4][8] = 0.400;
	$probabilities[3][8][4] = 0.6;
	$probabilities[3][4][9] = 0.000;
	$probabilities[3][9][4] = 1.0;
	$probabilities[3][5][8] = 0.000;
	$probabilities[3][8][5] = 1.0;
	$probabilities[3][5][9] = 0.000;
	$probabilities[3][9][5] = 1.0;
	$probabilities[3][8][12] = 0.000;
	$probabilities[3][12][8] = 1.0;
	$probabilities[3][8][13] = 1.000;
	$probabilities[3][13][8] = 0.0;
	$probabilities[3][2][3] = 0.625;
	$probabilities[3][3][2] = 0.375;
	$probabilities[3][2][6] = 0.762;
	$probabilities[3][6][2] = 0.238;
	$probabilities[3][2][11] = 0.875;
	$probabilities[3][11][2] = 0.125;
	$probabilities[3][3][7] = 0.600;
	$probabilities[3][7][3] = 0.4;
	$probabilities[3][3][10] = 0.700;
	$probabilities[3][10][3] = 0.3;
	$probabilities[3][6][7] = 0.500;
	$probabilities[3][7][6] = 0.5;
	$probabilities[3][6][10] = 0.667;
	$probabilities[3][10][6] = 0.333;
	$probabilities[3][7][11] = 0.000;
	$probabilities[3][11][7] = 1.0;
	$probabilities[3][7][14] = 1.000;
	$probabilities[3][14][7] = 0.0;
	$probabilities[3][10][14] = 1.000;
	$probabilities[3][14][10] = 0.0;
	$probabilities[4][1][2] = 0.536;
	$probabilities[4][2][1] = 0.464;
	$probabilities[4][1][3] = 0.500;
	$probabilities[4][3][1] = 0.5;
	$probabilities[4][1][6] = 0.750;
	$probabilities[4][6][1] = 0.25;
	$probabilities[4][1][7] = 1.000;
	$probabilities[4][7][1] = 0.0;
	$probabilities[4][1][10] = 1.000;
	$probabilities[4][10][1] = 0.0;
	$probabilities[4][1][11] = 0.667;
	$probabilities[4][11][1] = 0.333;
	$probabilities[4][2][4] = 0.500;
	$probabilities[4][4][2] = 0.5;
	$probabilities[4][2][5] = 0.000;
	$probabilities[4][5][2] = 1.0;
	$probabilities[4][2][8] = 0.667;
	$probabilities[4][8][2] = 0.333;
	$probabilities[4][2][12] = 1.000;
	$probabilities[4][12][2] = 0.0;
	$probabilities[4][3][4] = 0.667;
	$probabilities[4][4][3] = 0.333;
	$probabilities[4][3][5] = 0.500;
	$probabilities[4][5][3] = 0.5;
	$probabilities[4][3][8] = 1.000;
	$probabilities[4][8][3] = 0.0;
	$probabilities[4][3][9] = 1.000;
	$probabilities[4][9][3] = 0.0;
	$probabilities[4][4][6] = 0.667;
	$probabilities[4][6][4] = 0.333;
	$probabilities[4][4][7] = 1.000;
	$probabilities[4][7][4] = 0.0;
	$probabilities[4][4][10] = 1.000;
	$probabilities[4][10][4] = 0.0;
	$probabilities[4][5][10] = 1.000;
	$probabilities[4][10][5] = 0.0;
	$probabilities[4][6][8] = 0.000;
	$probabilities[4][8][6] = 1.0;
	$probabilities[4][7][8] = 0.000;
	$probabilities[4][8][7] = 1.0;
	$probabilities[5][1][1] = 0.500;
	$probabilities[5][1][1] = 0.5;
	$probabilities[5][1][2] = 0.500;
	$probabilities[5][2][1] = 0.5;
	$probabilities[5][1][3] = 0.200;
	$probabilities[5][3][1] = 0.8;
	$probabilities[5][1][4] = 0.800;
	$probabilities[5][4][1] = 0.2;
	$probabilities[5][1][5] = 1.000;
	$probabilities[5][5][1] = 0.0;
	$probabilities[5][1][8] = 1.000;
	$probabilities[5][8][1] = 0.0;
	$probabilities[5][2][2] = 0.500;
	$probabilities[5][2][2] = 0.5;
	$probabilities[5][2][3] = 0.600;
	$probabilities[5][3][2] = 0.4;
	$probabilities[5][2][5] = 0.000;
	$probabilities[5][5][2] = 1.0;
	$probabilities[5][2][6] = 0.500;
	$probabilities[5][6][2] = 0.5;
	$probabilities[5][2][8] = 0.000;
	$probabilities[5][8][2] = 1.0;
	$probabilities[5][2][11] = 1.000;
	$probabilities[5][11][2] = 0.0;
	$probabilities[5][3][4] = 1.000;
	$probabilities[5][4][3] = 0.0;
	$probabilities[5][4][5] = 1.000;
	$probabilities[5][5][4] = 0.0;
	$probabilities[5][4][6] = 0.000;
	$probabilities[5][6][4] = 1.0;
	$probabilities[5][5][8] = 1.000;
	$probabilities[5][8][5] = 0.0;
	$probabilities[6][1][1] = 0.500;
	$probabilities[6][1][1] = 0.5;
	$probabilities[6][1][2] = 0.800;
	$probabilities[6][2][1] = 0.2;
	$probabilities[6][1][3] = 1.000;
	$probabilities[6][3][1] = 0.0;
	$probabilities[6][1][4] = 0.500;
	$probabilities[6][4][1] = 0.5;
	$probabilities[6][1][5] = 1.000;
	$probabilities[6][5][1] = 0.0;
	$probabilities[6][1][6] = 0.500;
	$probabilities[6][6][1] = 0.5;
	$probabilities[6][1][8] = 0.000;
	$probabilities[6][8][1] = 1.0;
	$probabilities[6][2][3] = 0.750;
	$probabilities[6][3][2] = 0.25;
	$probabilities[6][3][3] = 0.500;
	$probabilities[6][3][3] = 0.5;
	
	return $probabilities;
}

function getChildGraph()
{
	$childGraph = array();
	$childCounter = 0;
	
	// build a child graph for parents
	for( $i=33; $i < 64; $i++ )
	{
		$childGraph[$i][0] = ++$childCounter;
		$childGraph[$i][1] = ++$childCounter;
	}
	
	return $childGraph;
}

// Auth Helper - Session Only
function is_logged_in()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}
