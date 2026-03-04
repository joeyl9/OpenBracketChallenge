<?php
session_start();
include("database.php");
include 'functions.php';
validatecookie();

$brackets = "SELECT person, email, id, paid FROM `brackets`";
$stmt = $db->query($brackets);

$meta = "SELECT email, name, mail,cut FROM `meta` WHERE id=1";
$stmt2 = $db->query($meta);
$meta = $stmt2->fetch(PDO::FETCH_ASSOC);
$adminEmail = $meta['email'];

$subject = "Tournament Has Begun";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

	$userEmail = $row['email'];
	// prepare administrator email body text
	$body = "The tournament has begun.  Thank you for taking part in this year's pool.";
    $link = $tourneyURL . "view.php?id=" . $row['id'];
    $body .= "  You can view your bracket or print it out at " . $link;
    $body .= " and you can watch the standings update as the tourney progresses at " . $tourneyURL . "choose.php\n";
	if ($row['paid'] < 1) {
		$body .= "\n\nNOTE:\nThis bracket has not been paid for.  Please pay " . $meta[name] . " as soon as possible.  Your bracket will not show up in the standings until payment is received.";
	}


	mail($userEmail, $subject, $body, "From: $adminEmail");

	}
	
	//redirects to a confirmation notice
	$_SESSION['success'] = "Emails have been sent";
	header('location:../index.php');
	exit();
	
	?>
