<?php
include 'functions.php';
validatecookie();
include("database.php");


verify_csrf_token();

if($_GET['action'] == "post")
{
    check_admin_auth('limited');
	$query = "INSERT INTO `blog` (title,subtitle,content) VALUES (?,?,?)";
	$stmt = $db->prepare($query);
	$stmt->execute([$_POST['title'], $_POST['subtitle'], $_POST['content']]);
    log_admin_action('BLOG_ADD', "Added blog post: " . $_POST['title']);
}

else if($_GET['action'] == "delete")
{
    check_admin_auth('limited');
    // Fetch title for logging
    $stmt = $db->prepare("SELECT title FROM blog WHERE id=?");
    $stmt->execute([$_POST['post']]);
    $title = $stmt->fetchColumn();

	$query = "DELETE FROM `blog` WHERE id=?";
	$stmt = $db->prepare($query);
	$stmt->execute([$_POST['post']]);
    log_admin_action('BLOG_DELETE', "Deleted blog post: " . ($title ?: 'ID '.$_POST['post']));
}

else if($_GET['action'] == "edit")
{
    check_admin_auth('limited');
	$query = "UPDATE `blog` SET title=?, subtitle=?, content=? WHERE id=?";
	$stmt = $db->prepare($query);
	$stmt->execute([$_POST['title'], $_POST['subtitle'], $_POST['content'], $_POST['id']]);
    log_admin_action('BLOG_EDIT', "Edited blog post: " . $_POST['title']);
}

else if($_GET['action'] == "rules")
{
    check_admin_auth('limited');
	$query = "UPDATE `meta` SET `rules`=? WHERE id=1";
	$stmt = $db->prepare($query);
	$stmt->execute([$_POST['rules']]);
    log_admin_action('UPDATE_RULES', "Updated tournament rules (Length: " . strlen($_POST['rules']) . " chars)");
}
else if($_GET['action'] == "paid")
{
  check_admin_auth('pay');
  
  $updates = [];
  foreach($_POST as $id => $val) {
      if(!is_numeric($id)) continue;
      
      // Fetch user name and current status
      $stmt = $db->prepare("SELECT person, paid FROM brackets WHERE id=?");
      $stmt->execute([$id]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if($row) {
          // If status changed, log it (or just log the final state)
          if($row['paid'] != $val) {
              $statusMap = [0 => 'Unpaid', 1 => 'Paid', 2 => 'Exempt'];
              $old = $statusMap[$row['paid']] ?? $row['paid'];
              $new = $statusMap[$val] ?? $val;
              $updates[] = "{$row['person']} ($old -> $new)";
          }
          
          // Perform Update
          $uStmt = $db->prepare("UPDATE brackets SET paid=? WHERE id=?");
          $uStmt->execute([$val, $id]);
      }
  }

  if(!empty($updates)) {
      $logMsg = "Updated payment status for: " . implode(", ", $updates);
      log_admin_action('UPDATE_PAID', $logMsg);
  }

  // Check if tournament is closed (games started). If so, we must recalc scores to update standings immediately.
  $mStmt = $db->query("SELECT closed FROM meta WHERE id=1");
  $isClosed = $mStmt->fetchColumn();

  if($isClosed) {
      header('Location: score.php');
  } else {
      header('Location: index.php');
  }
}

// Ensure no output before redirect if possible, but existing code has no output.
// Default redirect if action didn't match paid (though script structure implies exit)
if($_GET['action'] != 'paid') {
   header( 'Location: index.php' );
}
?>

