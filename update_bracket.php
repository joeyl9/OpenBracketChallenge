<?php
session_start();
include("admin/database.php");
include("admin/functions.php");

// 1. Auth & Input Checks
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
verify_csrf_token();

if(!$user_id) {
    die("Not logged in.");
}

if(!isset($_POST['bracket_id']) || !is_numeric($_POST['bracket_id'])) {
    die("Invalid Bracket ID");
}
$bracket_id = $_POST['bracket_id'];

// Check Owner First
$q_owner = "SELECT user_id, email, type, name, person FROM `brackets` WHERE id=:id";
$stmt = $db->prepare($q_owner);
$stmt->execute(array(':id' => $bracket_id));
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row || $row['user_id'] != $user_id) {
    die("Access Denied.");
}

// Check Closed Status
$q_meta = "SELECT * FROM `meta` WHERE id=1";
$stmt = $db->query($q_meta);
$meta = $stmt->fetch(PDO::FETCH_ASSOC);

$is_locked = false;
$b_type = isset($row['type']) ? $row['type'] : 'main';

if($b_type == 'sweet16') {
    if($meta['sweet16_closed'] == 1) $is_locked = true;
    if(!empty($meta['sweet16_deadline']) && time() > strtotime($meta['sweet16_deadline'])) $is_locked = true;
} else {
    if($meta['closed'] == 1) $is_locked = true;
    if(!empty($meta['deadline']) && time() > strtotime($meta['deadline'])) $is_locked = true;
}

if($is_locked) {
    die("Tournament Closed.");
}

// 2. Prepare Data
if($bracket_id == 2) {
    $bracketname = $row['name']; // Fixed
    $person = $row['person'];    // Fixed
} else {
    $bracketname = $_POST['bracketname'];
    $person = $_POST['name'];
}
$tiebreaker = $_POST['tiebreaker'];
// Email and Password are NOT updated here anymore.
// They are managed in profile.php or users table.

$params = array();
$params[':name'] = $bracketname;
$params[':person'] = $person;
$params[':tiebreaker'] = $tiebreaker;
$params[':id'] = $bracket_id;
$params[':user_id'] = $user_id;

// Password Update Logic REMOVED
// Password updates are managed via profile.php and the `users` table, not here.
$password_sql = ""; 

// 3. Construct Update Query for Picks
// Columns 1..63
$picks_sql = "";
$sweet16 = (isset($meta['sweet16Competition']) && $meta['sweet16Competition'] == 1);
$startIdx = $sweet16 ? 49 : 1;

// For Sweet 16, picks 1-48 are fixed by the master bracket and should not be overwritten.
// Only games 49-63 are updated from the form.
if($sweet16) {
	$master_query = "SELECT * FROM `master` WHERE `id`=2"; // winners
	$stmt = $db->query($master_query);
	$winners = $stmt->fetch(PDO::FETCH_ASSOC);
}

for($i = $startIdx; $i <= 63; $i++) {
    $col = "`" . $i . "`";
    $val = $_POST["game".$i];
    
    // Server-side validation
    if(empty($val)) {
        die("Error: incomplete bracket. Game $i is missing.");
    }

    $picks_sql .= ", $col = :game$i";
    $params[":game$i"] = $val;
}

// 4. Execute Update
$update_sql = "UPDATE `brackets` SET 
                `name` = :name, 
                `person` = :person, 
                `tiebreaker` = :tiebreaker
                $picks_sql
               WHERE `id` = :id AND `user_id` = :user_id";

$stmt = $db->prepare($update_sql);
$stmt->execute($params);

// 5. Success
$_SESSION['success'] = "Bracket updated successfully.";
header("Location: index.php");
exit();
?>
