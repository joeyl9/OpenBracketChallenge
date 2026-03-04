<?php
include("header.php");

$query = "SELECT closed FROM `meta` WHERE id=1 LIMIT 1";
$stmt = $db->query($query);
if(!($closed = $stmt->fetch(PDO::FETCH_NUM))) {
	echo "<div style='padding:20px; text-align:center;'>Please <a href=\"admin/install_ui.php\">configure the site.</a></div>";
	exit();
}

$query = "SELECT * FROM `master` WHERE `id`=1";
$stmt = $db->query($query);
if(!($teams = $stmt->fetch(PDO::FETCH_ASSOC))) {
     echo '<div id="main" style="padding:40px; text-align:center;">
            <div style="background:#1e293b; padding:40px; border-radius:12px; max-width:600px; margin:0 auto; border:1px solid #334155;">
                <h2 style="color:#ef4444; margin-top:0;"><i class="fa-solid fa-triangle-exclamation"></i> Tournament Not Ready</h2>
                <p style="color:var(--text-light); font-size:1.1em;">The tournament bracket has not been initialized yet.</p>
                <p style="color:var(--text-muted);">Please wait for the administrator to set up the teams.</p>
                <br>
                <a href="index.php" style="background:#334155; color:white; padding:12px 20px; text-decoration:none; border-radius:6px; font-weight:bold;">Return Home</a>
            </div>
          </div>';
    include("footer.php");
	exit();
}

$query = "SELECT sweet16 FROM `meta` WHERE id=1 LIMIT 1";
$stmt = $db->query($query);
$sweet16 = $stmt->fetch(PDO::FETCH_NUM);
?>

<div id="main" class="full">
    <div class="content-card" style="max-width:1400px; margin:0 auto; width:100%;">
        <div style="border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:20px;">
             <h2 style="margin:0; color:var(--accent-orange);"><i class="fa-solid fa-list-ul"></i> The Standings</h2>
             <p style="color:var(--text-muted); margin:5px 0 0 0;">Select a view to analyze the field.</p>
        </div>
         <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:20px; width:100%;">
            
            <!-- Always Visible -->
            <a href="master.php" class="nav-card">
                <div class="icon">📜</div>
                <h3>Master Bracket</h3>
                <p>View the official results so far.</p>
            </a>
            
            <?php if($closed[0] == 1) { ?>
                
                <a href="standings.php?type=normal" class="nav-card">
                    <div class="icon">🏆</div>
                    <h3>Current Standings</h3>
                    <p>See who is leading the pack.</p>
                </a>
                
                <a href="champ.php" class="nav-card">
                    <div class="icon">👑</div>
                    <h3>Champion Picks</h3>
                    <p>Who did everyone pick to win?</p>
                </a>
                
                <a href="scoredetail.php" class="nav-card">
                    <div class="icon">🔍</div>
                    <h3>Who Picked Whom?</h3>
                    <p>Detailed breakdown by round.</p>
                </a>
                
                <a href="standings.php?type=best" class="nav-card">
                    <div class="icon">📈</div>
                    <h3>Best Possible Scores</h3>
                    <p>Max potential points remaining.</p>
                </a>
                
                <?php if($sweet16[0] == 1) { ?>
                    <a href="endgamesummary.php" class="nav-card" style="border-left: 4px solid #8b5cf6;">
                        <div class="icon">🏁</div>
                        <h3>End Game Scenarios</h3>
                        <p>Outcomes for the final games.</p>
                    </a>
                    
                    <a href="whatif.php" class="nav-card" style="border-left: 4px solid #ec4899;">
                        <div class="icon">🔮</div>
                        <h3>Scenario Planner</h3>
                        <p>Interactive "What If" simulator.</p>
                    </a>
                <?php } ?>
                
            <?php } else { ?>
                 <div style="grid-column: 1 / -1; padding:20px; background:rgba(255,255,255,0.05); border-radius:8px; text-align:center;">
                    🔒 Standings are hidden until submissions close.
                 </div>
            <?php } ?>
            
        </div>
    </div>
</div>

<style>
.nav-card {
    display: block;
    background: rgba(255, 255, 255, 0.03);
    padding: 25px;
    border-radius: 12px;
    text-decoration: none;
    color: var(--text-light);
    border: 1px solid var(--border-color);
    transition: transform 0.2s, background 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}
.nav-card:hover {
    transform: translateY(-5px);
    background: var(--primary-blue);
    box-shadow: 0 10px 20px rgba(0,0,0,0.3);
    border-color: var(--accent-orange);
}
.nav-card .icon {
    font-size: 2rem;
    margin-bottom: 15px;
}
.nav-card h3 {
    margin: 0 0 10px 0;
    color: var(--accent-orange);
    font-size: 1.25rem;
}
.nav-card p {
    margin: 0;
    color: var(--text-muted);
    font-size: 0.95rem;
    line-height: 1.5;
}
</style>

<?php include("footer.php"); ?>
</body>
</html>

