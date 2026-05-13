<?php
session_start();
include("admin/database.php");
include("admin/functions.php");

$stmt = $db->query("SELECT * FROM meta WHERE id=1");
$meta = $stmt->fetch(PDO::FETCH_ASSOC);

$tiebreaker = trim($_POST['tiebreaker']);
verify_csrf_token();
$bracketname = trim($_POST['bracketname']);  
$person = trim($_POST['name']);
$email = trim($_POST['e-mail']); 
// Security Check (Session-only)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['useremail'] ?? '';
$user_id = $_SESSION['user_id'];
$person = $_POST['name'];
$bracketType = isset($_POST['bracket_type']) ? $_POST['bracket_type'] : 'main'; 

// User data (name, email) is not updated here during bracket submission.
// The session validation above ensures the user is authenticated.

// ----------------------------------------------------
// INSERT NEW BRACKET
// ----------------------------------------------------

/////////////////////// print ////////////////////////////

if(isset($_POST['print']))
{
	include('bracket_view_module.php');
	
	$seedMap = getSeedMap($db);	

	$startIdx = 1;
	
	if( $meta['sweet16Competition'] == true )
	{
		$master_query = "SELECT * FROM `master` WHERE `id`=2"; //select winners
		$stmt = $db->query($master_query);
		$winners = $stmt->fetch(PDO::FETCH_ASSOC);
		
		for( $i=1; $i < 49; $i++ )
		{
			$picks[$i] = $seedMap[$winners[$i]].". ".$winners[$i];
		}
	
		$startIdx = 49;
	}
	
	for( $i=$startIdx ; $i < 64; $i++ )
	{
		$picks[$i.""] = $seedMap[$_POST["game".$i]].". ".$_POST["game".$i];
	}
	
	$picks['name'] = stripslashes($bracketname);
	$picks['tiebreaker'] = $tiebreaker;
	
	
	$team_query = "SELECT * FROM `master` WHERE `id`=1"; //select teams
	$stmt = $db->query($team_query);
	$team_data = $stmt->fetch(PDO::FETCH_ASSOC);
	
		
	for( $i= 1; $i<65; $i++ )
	{
		$team_data[$i] = $seedMap[$team_data[$i]].". ".$team_data[$i];
	}
?>

<link rel="stylesheet" href="images/print.css" type="text/css" />

<?php

	$rank = "";
	$score_data = ['score' => ''];
	$best_data = ['score' => ''];
	viewBracket( $meta, $picks, $team_data, $rank, $score_data, $best_data );
	exit();
}
unset($_SESSION['print']);
/////////////////////////////////////////////////////////

$subject = "Brackets";
$adminEmail = $meta['email'];


if($tiebreaker != NULL && is_numeric($tiebreaker) && $person != NULL && $email != NULL)  //validates that the form was submitted to prevent spamming
{
	$body = "Your bracket has been successfully submitted.";
	$_SESSION['success'] = $body;
}
else
{
	$body = "Error: Invalid submission. Please ensure all fields are filled out, including a numeric tiebreaker.";
	$_SESSION['errors'] = $body;
	header("Location: submit.php");
	exit();
}	

// Submit bracket (INSERT new bracket)

$paid = 0;
if( $meta['cost'] == 0 ) $paid = 2;

// Prepare data array
$values = [];

// 1. Common Data
$data_map = [
    'person' => $person, 
    'name' => $bracketname, 
    'email' => $user_email, 
    'tiebreaker' => $tiebreaker, 
    'paid' => $paid, 
    'type' => $bracketType,
    'user_id' => $user_id
];


// Handle Games (1-63)
// Determine Start Index (Sweet 16 Logic)
$startIdx = 1;
if( $meta['sweet16Competition'] == true ) {
    $master_query = "SELECT * FROM `master` WHERE `id`=2"; //select winners
    $stmt = $db->query($master_query);
    $winners = $stmt->fetch(PDO::FETCH_ASSOC);
    for( $i=1; $i < 49; $i++ ) {
        $data_map[(string)$i] = $winners[$i];
    }
    $startIdx = 49;
}

// Collect POST data for user picks
for( $i=$startIdx; $i < 63; $i++ ) {
    $val = isset($_POST["game".$i]) ? $_POST["game".$i] : "";
    if(empty($val)) {
        die("Error: Incomplete bracket. Game $i is missing.");
    }
    $data_map[(string)$i] = $val;
}
$val63 = isset($_POST["game63"]) ? $_POST["game63"] : "";
if(empty($val63)) die("Error: Incomplete bracket. Championship (Game 63) is missing.");
$data_map["63"] = $val63;

// BUILD INSERT QUERY (Updates are handled by update_bracket.php)
$col_names = [];
$placeholders_arr = [];
foreach($data_map as $col => $val) {
    $col_names[] = "`$col`";
    $placeholders_arr[] = "?";
    $values[] = $val;
}
$query = "INSERT INTO `brackets` (" . implode(',', $col_names) . ") VALUES (" . implode(',', $placeholders_arr) . ")";

$stmt = $db->prepare($query);
$stmt->execute($values);

// Award Badges (Submission Time logic)
include_once("admin/badges.php");
$bm = new BadgeManager($db);
$bracketId = $db->lastInsertId();
if($bracketId) {
    $bm->awardBadges($bracketId);
}


if($meta['mail']==1)
{ //if mail is configured
	
	// prepare administrator email body text
	$body .= "Name: ";
	$body .= $person;
	$body .= "\n";
	$body .= "Bracket Name: ";
	$body .= $bracketname;
	$body .= "\n";
	$body .= "Entrant's Email: ";
	$body .= $email;
	$body .= "\n";
	$body .= "Tiebreaker (# of points in the championship): ";
	$body .= $tiebreaker;
	$body .= "\n";
	for($i=1;$i<=63;++$i) {
		$body .= "Game $i: ";
		$body .= $_POST["game$i"];
		$body .= "\n";
	}

	
	// send email to admin
	mail($adminEmail, $subject, "A bracket has been submitted to your pool.  This email should serve as a backup copy in the event that something happens to your database.\n\n".$body, "From: $email");
	// send confirmation to the entrant
	mail($email, "I have received your bracket","This is an automated email.  If you receive this, I have your submission.  Thanks for playing! -$meta[name]\n\n".$body, "From: $adminEmail");
}
//redirects to a confirmation notice
header('Location:index.php');
exit();

?>
