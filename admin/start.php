<?php
include 'functions.php';
validatecookie();
include("database.php");


// Check if initialized
$check = $db->query("SELECT * FROM `master` WHERE id=1");
$row = $check->fetch(PDO::FETCH_ASSOC);

if(!$row) { // Insert
	$cols = [];
	$vals = [];
	$params = [];
	
	// ID 1
	$cols[] = "id"; 
	$vals[] = "?";
	$params[] = 1;
	
	for($i=1; $i<=64; $i++) {
		$cols[] = "`$i`"; // Column names are numbers "1", "2"
		$vals[] = "?";
		$params[] = $_POST[$i] ?? '';
	}
	
	$sql = "INSERT INTO `master` (" . implode(",", $cols) . ") VALUES (" . implode(",", $vals) . ")";
	$stmt = $db->prepare($sql);
	if(!$stmt->execute($params)) {
		die("DB Error: " . print_r($stmt->errorInfo(), true));
	}
}
else { // Update
	$updates = [];
	$params = [];
	
	for($i=1; $i<=64; $i++) {
		$updates[] = "`$i`=?";
		$params[] = $_POST[$i] ?? '';
	}
	// Add ID for WHERE clause
	$params[] = 1;
	
	$sql = "UPDATE `master` SET " . implode(",", $updates) . " WHERE id=?";
	$stmt = $db->prepare($sql);
	if(!$stmt->execute($params)) {
		die("DB Error: " . print_r($stmt->errorInfo(), true));
	}
}

// Initialize seed data (master id=4) if it doesn't exist.
// The standard bracket order places seeds as follows per region of 16 positions:
// Position pairs: (1)vs(16), (8)vs(9), (5)vs(12), (4)vs(13), (6)vs(11), (3)vs(14), (7)vs(10), (2)vs(15)
// This matches the seed labels rendered in start_form.php's renderMatchups() calls.
$seed_check = $db->query("SELECT id FROM `master` WHERE id=4");
if(!$seed_check->fetch()) {
    $std_seeds = [1,16,8,9,5,12,4,13,6,11,3,14,7,10,2,15];

    $seed_cols = ["id"];
    $seed_vals = ["?"];
    $seed_params = [4];

    for($i=1; $i<=64; $i++) {
        $seed_cols[] = "`$i`";
        $seed_vals[] = "?";
        $seed_params[] = $std_seeds[($i - 1) % 16];
    }

    $seed_sql = "INSERT INTO `master` (" . implode(",", $seed_cols) . ") VALUES (" . implode(",", $seed_vals) . ")";
    $seed_stmt = $db->prepare($seed_sql);
    $seed_stmt->execute($seed_params);
}

header( 'Location: index.php' );
?>

