<?php
include("admin/functions.php");
include("header.php");

$id = (int) $_GET['id'];
$rank = (int) $_GET['rank'];
$viewAll = isset($_GET['view_all']) ? $_GET['view_all'] : null;

$pageMode = "";
if( $id != NULL && $rank != NULL && $viewAll == NULL )
{
	$pageMode = "bracket";
}
else if( $id == NULL && $rank != NULL && $viewAll != NULL )
{
	$pageMode = "view_all";
}

$endgameIds = array();

include("endgame_view_module.php");

drawEndGames( $pageMode, $id, $rank , $endgameIds, $db);
?>				
	</div>
</body>
</html>