<?php
include("functions.php");
validatecookie();
include("header.php");

// Determine if editing is allowed based on close state
$main_closed = !empty($meta['closed']);
$s16_enabled = !empty($meta['sweet16Competition']);
$s16_closed  = !empty($meta['sweet16_closed']);

// Editing is only available when at least one bracket type is still open
if ($main_closed && (!$s16_enabled || $s16_closed)) {
    echo '<div id="main"><div class="full">';
    echo '<h2>Edit User Bracket</h2>';
    echo '<p>All brackets are currently closed. Editing is not available.</p>';
    echo '<a href="index.php" class="btn-outline">&larr; Back to Admin</a>';
    echo '</div></div></div>';
    include('footer.php');
    echo '</body>
</html>';
    exit;
}

// Build query based on which brackets are still open
if (!$main_closed) {
    // Main bracket still open — show main brackets
    $query = "SELECT id, name FROM `brackets` WHERE type IS NULL OR type != 'sweet16' ORDER BY name ASC";
} else {
    // Main closed, second chance still open — show only second chance brackets
    $query = "SELECT id, name FROM `brackets` WHERE type = 'sweet16' ORDER BY name ASC";
}
$stmt = $db->query($query);
?>
	<div id="main">
		<div class="full">
			<form method="post" action="bracket.php">
			<h2>Select a User</h2>
			<table>
				<tr>
					<td>
						<select name="id" class="forms" id="id">
						<?php
						while($user = $stmt->fetch(PDO::FETCH_NUM)) {
							echo '<option value="' . (int)$user[0] . '">' . h($user[1]) . '</option>' . "\n";
						}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<td align="center"><input type="submit" value="Submit!"/></td>
				</tr>
			</table>
			</form>
		</div>
	</div>
</div>
<?php include('footer.php'); ?>
</body>
</html>


