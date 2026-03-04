<?php

include("database.php");
include("functions.php");
validatecookie();

// Select master bracket to check if it exists
$stmt = $db->query("SELECT * FROM `master` WHERE id=1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row) {
    // INSERT
    // Use prepared statements manually built or loop?
    // Keys are 1..64.
    
    $placeholders = [];
    $values = [];
    $placeholders[] = '?'; // id=1
    $values[] = 1;
    
    for($i=1; $i<=64; $i++) {
        $placeholders[] = '?';
        $values[] = isset($_POST[$i]) ? $_POST[$i] : '';
    }
    
    $sql = "INSERT INTO `master` (`id`,";
    // Build column list `1`,`2`...
    $cols = [];
    for($i=1; $i<=64; $i++) $cols[] = "`$i`";
    $sql .= implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($values);
}
else {
    // UPDATE
    $set = [];
    $values = [];
    
    for($i=1; $i<=64; $i++) {
        $set[] = "`$i`=?";
        $values[] = isset($_POST[$i]) ? $_POST[$i] : '';
    }
    
    $sql = "UPDATE `master` SET " . implode(',', $set) . " WHERE `id`=1";
    $stmt = $db->prepare($sql);
    $stmt->execute($values);
}

// Initialize seed data (master id=4) if it doesn't exist.
// Standard bracket order per 16-team region: 1,16,8,9,5,12,4,13,6,11,3,14,7,10,2,15
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

