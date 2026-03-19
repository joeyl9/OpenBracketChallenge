<?php
include("admin/database.php");
include("admin/functions.php");
include("header.php");

// 1. Calculate Movers
// Get Latest Timestamp
$stmt = $db->query("SELECT MAX(`timestamp`) FROM rank_history");
$latest_time = $stmt->fetchColumn();

// Get Previous Timestamp (e.g. > 12 hours ago)
$stmt = $db->prepare("SELECT MAX(`timestamp`) FROM rank_history WHERE `timestamp` < DATE_SUB(?, INTERVAL 12 HOUR)");
$stmt->execute([$latest_time]);
$prev_time = $stmt->fetchColumn();

$movers = [];
if ($latest_time && $prev_time) {
    $sql = "SELECT 
                curr.bracket_id, 
                b.name, 
                b.avatar_url,
                b.person,
                curr.`rank` as current_rank, 
                prev.`rank` as prev_rank, 
                (prev.`rank` - curr.`rank`) as movement 
            FROM rank_history curr
            JOIN rank_history prev ON curr.bracket_id = prev.bracket_id
            JOIN brackets b ON curr.bracket_id = b.id
            WHERE curr.`timestamp` = ? AND prev.`timestamp` = ?
            ORDER BY movement DESC
            LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute([$latest_time, $prev_time]);
    $movers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 2. Pick Distribution (Champion)
// Game 63 is Champion
$champ_sql = "SELECT `63` as team, COUNT(*) as count FROM brackets GROUP BY `63` ORDER BY count DESC";
$champ_stats = $db->query($champ_sql)->fetchAll(PDO::FETCH_ASSOC);
$total_brackets = 0;
foreach($champ_stats as $s) $total_brackets += $s['count'];

?>

<div id="main">
    <div class="full">
        <h2>Tournament Analytics</h2>
        
        <div class="dashboard-grid">
            
            <!-- Movers & Shakers -->
            <div class="dashboard-card" style="grid-column: span 1; align-items: flex-start; text-align: left; min-height: 400px;">
                <h3 style="border-bottom: 1px solid var(--border-color); width: 100%; padding-bottom: 10px; margin-bottom: 15px;">🔥 Top Movers (Last 24h)</h3>
                
                <?php if (empty($movers)): ?>
                    <p>No movement data yet. (Waiting for scoring history)</p>
                <?php else: ?>
                    <table style="width: 100%; border: none; background: transparent;">
                        <?php foreach ($movers as $m): ?>
                        <tr style="background: transparent;">
                            <td style="border: none; padding: 10px 0;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?php echo $m['avatar_url'] ? $m['avatar_url'] : 'images/default_avatar.png'; ?>" 
                                         style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #555; object-fit: cover;"
                                         onerror="this.src='avatar.php?name=<?php echo urlencode(strip_tags($m['name'])); ?>&background=random'">
                                    <div>
                                        <div style="font-weight: bold; color: var(--text-light);"><?php echo htmlspecialchars(stripslashes($m['name'])); ?></div>
                                        <div style="font-size: 0.8em; color: var(--text-muted);">Rank <?php echo $m['prev_rank']; ?> ➔ <?php echo $m['current_rank']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="border: none; text-align: right; color: #22c55e; font-weight: bold; font-size: 1.2em;">
                                +<?php echo $m['movement']; ?>
                                <span style="font-size: 0.6em; display: block; color: var(--text-muted); font-weight: normal;">SPOT JUMP</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Champion Picks -->
            <div class="dashboard-card" style="grid-column: span 1; align-items: flex-start; text-align: left; min-height: 400px;">
                <h3 style="border-bottom: 1px solid var(--border-color); width: 100%; padding-bottom: 10px; margin-bottom: 15px;">🏆 Champion Picks</h3>
                
                <div style="width: 100%; display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($champ_stats as $stat): 
                        if (empty($stat['team'])) continue;
                        $pct = round(($stat['count'] / $total_brackets) * 100, 1);
                    ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-weight: bold; color: var(--text-light);"><?php echo htmlspecialchars($stat['team']); ?></span>
                            <span style="color: var(--text-muted);"><?php echo $pct; ?>% (<?php echo $stat['count']; ?>)</span>
                        </div>
                        <div style="width: 100%; height: 8px; background: #333; border-radius: 4px; overflow: hidden;">
                            <div style="width: <?php echo $pct; ?>%; height: 100%; background: var(--accent-orange);"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

</div>
</body>
</html>
