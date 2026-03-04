<?php
include("database.php");
include("functions.php");
validatecookie();

if(!is_admin()) die("Access Denied");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}
verify_csrf_token();

// Flip the current state
$db->exec("UPDATE meta SET use_live_scoring = NOT use_live_scoring WHERE id=1");

// Read new state for feedback
$stmt = $db->query("SELECT use_live_scoring FROM meta WHERE id=1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$label = $row['use_live_scoring'] ? 'enabled' : 'disabled';
$_SESSION['msg'] = "Live scoring $label.";

header("Location: index.php");
exit();

