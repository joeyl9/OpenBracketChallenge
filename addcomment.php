<?php
	include("admin/database.php");
	include("admin/functions.php");
    verify_csrf_token();

    // Auth check — must be logged in to comment
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // Derive commenter name from database
    $u_stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
    $u_stmt->execute([$_SESSION['user_id']]);
    $u_row = $u_stmt->fetch(PDO::FETCH_ASSOC);
    $from = $u_row ? $u_row['name'] : 'Unknown';

	$specialChars = array('*',';','char(','=','javascript','JavaScript', '%', '&#','<','>','char(39)');

	$comment = str_replace($specialChars,"",strip_tags($_POST['comment']));
	$bracket = (int)$_POST['id'];

	/* table structure asks that subject be not null, so insert a string w a space. */
	$query = "INSERT INTO `comments` (`bracket`,`from`,`content`, `subject`) VALUES (:bracket, :from, :comment, ' ')";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':bracket' => $bracket,
        ':from' => $from,
        ':comment' => $comment
    ]);

	header('location: view.php?id=' . $bracket."#comment");
	exit();
?>