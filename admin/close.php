<?php

include("database.php");
include("functions.php");
validatecookie();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}
verify_csrf_token();

// Close bracket entry
$type = $_POST['type'] ?? 'main';

if($type == 'sweet16') {
    $query = "UPDATE `meta` SET `sweet16_closed`=1";
} else {
    $query = "UPDATE `meta` SET `closed`=1";
}

$db->exec($query);

header('Location: index.php');
exit();
?>
