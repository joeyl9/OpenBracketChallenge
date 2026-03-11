<?php
include("database.php");
include("functions.php");
validatecookie();
check_admin_auth('super'); // Only Super Admins

$msg = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $confirm = $_POST['confirm'];

    if ($confirm === "CONFIRM-RESET") {
        
        try {
            // 1. Truncate User Tables
            $db->exec("TRUNCATE TABLE brackets");
            $db->exec("TRUNCATE TABLE scores");
            $db->exec("TRUNCATE TABLE best_scores");
            $db->exec("TRUNCATE TABLE comments");
            
            // 2. Truncate Calc Tables
            $db->exec("TRUNCATE TABLE possible_scores");
            $db->exec("TRUNCATE TABLE possible_scores_eliminated");
            $db->exec("TRUNCATE TABLE probability_of_winning");
            $db->exec("TRUNCATE TABLE end_games");
            $db->exec("TRUNCATE TABLE endgame_summary");
            
            // 3. Reset Master Bracket (Winners/Losers) but KEEP Teams (ID=1)
            // Empty ID=2 (Winners)
            //Will eventually remove winners too but its helpful with testing to keep them
            $db->exec("UPDATE `master` SET `1`=NULL, `2`=NULL, `3`=NULL, `4`=NULL, `5`=NULL, `6`=NULL, `7`=NULL, `8`=NULL, `9`=NULL, `10`=NULL, `11`=NULL, `12`=NULL, `13`=NULL, `14`=NULL, `15`=NULL, `16`=NULL, `17`=NULL, `18`=NULL, `19`=NULL, `20`=NULL, `21`=NULL, `22`=NULL, `23`=NULL, `24`=NULL, `25`=NULL, `26`=NULL, `27`=NULL, `28`=NULL, `29`=NULL, `30`=NULL, `31`=NULL, `32`=NULL, `33`=NULL, `34`=NULL, `35`=NULL, `36`=NULL, `37`=NULL, `38`=NULL, `39`=NULL, `40`=NULL, `41`=NULL, `42`=NULL, `43`=NULL, `44`=NULL, `45`=NULL, `46`=NULL, `47`=NULL, `48`=NULL, `49`=NULL, `50`=NULL, `51`=NULL, `52`=NULL, `53`=NULL, `54`=NULL, `55`=NULL, `56`=NULL, `57`=NULL, `58`=NULL, `59`=NULL, `60`=NULL, `61`=NULL, `62`=NULL, `63`=NULL WHERE `id`=2");

            // Empty ID=3 (Losers)
            $db->exec("UPDATE `master` SET `1`=NULL, `2`=NULL, `3`=NULL, `4`=NULL, `5`=NULL, `6`=NULL, `7`=NULL, `8`=NULL, `9`=NULL, `10`=NULL, `11`=NULL, `12`=NULL, `13`=NULL, `14`=NULL, `15`=NULL, `16`=NULL, `17`=NULL, `18`=NULL, `19`=NULL, `20`=NULL, `21`=NULL, `22`=NULL, `23`=NULL, `24`=NULL, `25`=NULL, `26`=NULL, `27`=NULL, `28`=NULL, `29`=NULL, `30`=NULL, `31`=NULL, `32`=NULL, `33`=NULL, `34`=NULL, `35`=NULL, `36`=NULL, `37`=NULL, `38`=NULL, `39`=NULL, `40`=NULL, `41`=NULL, `42`=NULL, `43`=NULL, `44`=NULL, `45`=NULL, `46`=NULL, `47`=NULL, `48`=NULL, `49`=NULL, `50`=NULL, `51`=NULL, `52`=NULL, `53`=NULL, `54`=NULL, `55`=NULL, `56`=NULL, `57`=NULL, `58`=NULL, `59`=NULL, `60`=NULL, `61`=NULL, `62`=NULL, `63`=NULL WHERE `id`=3");

            // 4. Cleanup Aux Tables (Orphans)
            $db->exec("TRUNCATE TABLE rivals");
            $db->exec("TRUNCATE TABLE bracket_badges");

            // 5. Reset Meta but KEEP CLOSED
            // Safety: Admin must manually open in Settings
            $db->exec("UPDATE `meta` SET `closed`=1, `sweet16`=0 WHERE `id`=1");

            $msg = "Tournament has been successfully reset! Data is wiped and submissions are Closed, open them in settings.";
            
        } catch (Exception $e) {
            $error = "Reset Failed: " . $e->getMessage();
        }
        
    } else {
        $error = "Incorrect confirmation phrase. No action taken.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Tournament</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <style>
        body { background: #0f172a; color: white; display: flex; flex-direction: column; align-items: center; min-height: 100vh; font-family: sans-serif; margin:0; padding:20px; }
        .card { background: rgba(30, 41, 59, 0.7); padding: 40px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); max-width: 600px; width:100%; box-sizing: border-box; }
        h1 { color: #f97316; margin-top:0; }
        input[type="text"] { width: 100%; padding: 10px; margin: 10px 0; border-radius: 6px; border: 1px solid #475569; background: #1e293b; color: white; }
        .btn-danger { background: #ef4444; color: white; padding: 12px 20px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; font-size: 1.1rem; }
        .btn-danger:hover { background: #dc2626; }
        .alert-warning { background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; color: #fbbf24; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        a { color: #3b82f6; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>

<div class="card">
    <h1><i class="fa-solid fa-triangle-exclamation"></i> Reset Tournament</h1>
    
    <?php if($msg) echo "<div style='color:#22c55e; background:rgba(34, 197, 94, 0.1); padding:15px; border-radius:8px; margin-bottom:20px;'><strong>Success:</strong> $msg</div>"; ?>
    <?php if($error) echo "<div style='color:#ef4444; background:rgba(239, 68, 68, 0.1); padding:15px; border-radius:8px; margin-bottom:20px;'><strong>Error:</strong> $error</div>"; ?>

    <p>This tool completely wipes the current tournament data to start a specific new season.</p>
    
    <div class="alert-warning">
        <strong><i class="fa-solid fa-skull"></i> DESTRUCTIVE ACTION</strong>
        <ul style="margin:10px 0 0 20px; padding:0;">
            <li>Deletes ALL user brackets.</li>
            <li>Deletes ALL calculated scores.</li>
            <li>Resets Master Bracket Winners/Losers (Teams are kept).</li>
            <li>Opens the tournament for new submissions.</li>
        </ul>
        <br>
        Make sure you have run the <a href="archive_season.php" style="color:white; text-decoration:underline;">Archive Season</a> tool first!
    </div>

    <form method="post">
        <label>Type <strong>CONFIRM-RESET</strong> to proceed:</label>
        <input type="text" name="confirm" required placeholder="CONFIRM-RESET" autocomplete="off">
        <br><br>
        <button type="submit" class="btn-danger"><i class="fa-solid fa-bomb"></i> WIPE & RESET</button>
    </form>
    
    <div style="text-align:center;">
        <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

</body>
</html>
