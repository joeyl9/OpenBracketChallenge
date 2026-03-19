<?php

// Custom Scrollbar Styles
echo '
<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1); 
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #475569; 
        border-radius: 3px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #64748b; 
    }
</style>
';

function createSummaryTable( $db, $rank, $rankName, $viewAll, $totalScenarios, $sort, $view="main" )
{	
	// Theme colors
	$cardBg = "rgba(0, 0, 0, 0.25)"; 
	$innerBg = "rgba(0, 0, 0, 0.2)"; 
	$borderColor = "rgba(255, 255, 255, 0.05)"; 
	$accentColor = "#f97316"; 
	$textColor = "#f1f5f9"; 
	$mutedColor = "var(--text-muted)"; 
	$hoverBg = "rgba(255,255,255,0.05)";
	
	// 1. Prepare SQL Components
    $view = in_array($view, ['main', 'sweet16']) ? $view : 'main'; // Double-safety
    $safeSort = in_array($sort, ['paths', 'pwin']) ? $sort : 'paths';

    $orderByTop  = ($safeSort == 'pwin') ? "pWin DESC, num_paths DESC, b.id ASC" : "num_paths DESC, pWin DESC, b.id ASC";
    $orderByLast = ($safeSort == 'pwin') ? "pWin ASC, num_paths ASC, b.id ASC"  : "num_paths ASC, pWin ASC, b.id ASC";

    $baseSql = "SELECT
                  b.id, b.name, b.email,
                  es.p_win as pWin,
                  es.num_paths
                FROM endgame_summary es
                JOIN brackets b ON b.id = es.bracket_id
                WHERE es.`rank`=:rank2
                  AND b.type=:view
                  AND b.paid<>'0'
                GROUP BY b.id, b.name, b.email";

    // 2. Query Helper
    $fetchData = function($order, $limit) use ($db, $baseSql, $rank, $view) {
        $sql = "$baseSql ORDER BY $order";
        if ($limit > 0) $sql .= " LIMIT " . (int)$limit;
        
        try {
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':rank2', $rank);
            $stmt->bindValue(':view', $view);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Check for "Gone Away" (2006) or "Lock Wait" (1205)
            // Return empty array to gracefully show 0 results instead of crashing
            error_log("Endgame Summary DB Error: " . $e->getMessage());
            return [];
        }
    };

    // 3. Execute Queries
    $limit = $viewAll ? 0 : 50;
    $sortedBrackets = $fetchData($orderByTop, $limit);

    if (!$viewAll) {
        $lastRow = $fetchData($orderByLast, 1);
        if (!empty($lastRow)) {
            // Deduplicate
            $existingIds = array_column($sortedBrackets, 'id');
            if (!in_array($lastRow[0]['id'], $existingIds)) {
                $sortedBrackets[] = $lastRow[0];
            }
        }
    }

	$numBrackets = count($sortedBrackets);
	
	$viewAllLink = "";
	if( $viewAll == true ) {
		$viewAllLink = "<div style='margin-top:5px;'><a href='endgame.php?view_all=true&rank=".$rank."' style='color:$accentColor; font-size:0.8rem; text-decoration:none;'>View All &rarr;</a></div>";
	}
	
	// Card Container
	echo "<div class='dashboard-card' style='background:$cardBg; border:1px solid $borderColor; border-radius:16px; display:flex; flex-direction:column; align-items:stretch; width:100%; height:100%; box-sizing:border-box; box-shadow:0 10px 15px -3px rgba(0, 0, 0, 0.1); padding:16px;'>";
	
	// Detached Header
	echo "<div style='text-align:center; margin-bottom:20px;'>";
	echo "<h3 style='margin:0; font-size:1.25rem; color:$accentColor; font-weight:700; letter-spacing:0.05em; text-transform:uppercase;'>$rankName</h3>";
	echo $viewAllLink;
	echo "</div>";
	
	// Table Container (Inner Card)
	echo "<div class='table-container custom-scrollbar' style='flex:1; overflow-y:auto; max-height:450px; background:$innerBg; border-radius:8px; border:1px solid $borderColor;'>";
	echo "<table style='width:100%; table-layout:fixed; border-collapse:collapse; font-size:0.95rem;'>";
	echo "<thead style='position:sticky; top:0; background:#0f172a; z-index:10; box-shadow:0 2px 4px rgba(0,0,0,0.1);'>"; 
	echo "<tr style='color:$mutedColor; text-transform:uppercase; font-size:0.75rem; letter-spacing:0.05em;'>";
	echo "<th style='width:50%; padding:14px 4px; font-weight:600; text-align:left;'>Bracket</th>";
	echo "<th style='width:25%; padding:14px 4px; font-weight:600; text-align:center;'>Paths</th>";
	echo "<th style='width:25%; padding:14px 4px; font-weight:600; text-align:right;'>Win %</th>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";

	if ($numBrackets == 0) {
		echo "<tr><td colspan='3' style='padding:30px; text-align:center; color:$mutedColor; font-style:italic;'>No scenarios found.</td></tr>";
	}
	
	for( $i=0; $i < $numBrackets; $i++ )
	{
		$row = $sortedBrackets[$i];
		$bID = $row['id'];
		$bName = stripslashes($row['name']);
		$useremail = $row['email'];
		$numPaths = $row['num_paths'];
		$pWin = $row['pWin'];
		
		$pWinDisplay = number_format(100*$pWin, 1) . "%";
		$cappedPaths = min($numPaths, max(1, $totalScenarios));
		$pathsPct = ($totalScenarios > 0) ? number_format(100*($cappedPaths/$totalScenarios),0) . "%" : "0%";
		
		$isMe = (isset($_SESSION['useremail']) && $useremail == $_SESSION['useremail'] && $useremail != "");
		
		// Row Styling
		$rowStyle = "border-top:1px solid $borderColor; transition:background 0.2s;";
		$bg = $isMe ? "rgba(249, 115, 22, 0.1)" : "transparent"; // Subtler orange
		
		echo "<tr style='$rowStyle background:$bg;' onmouseover=\"this.style.background='$hoverBg'\" onmouseout=\"this.style.background='$bg'\">";
		
		// Name
		echo "<td style='padding:14px 4px;'>";
		echo "<div style='display:flex; align-items:center;'>";
		$nameColor = $isMe ? "#fb923c" : "#fff"; 
		$weight = $isMe ? "700" : "500";
		echo "<a href='view.php?id=$bID' style='color:$nameColor; font-weight:$weight; text-decoration:none; font-size:1rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; display:block;'>".h($bName)."</a>";
		
        // Comment icon logic was intentionally removed here to optimize the query payload.
        // It can be restored later by reintroducing getCommentsMap($db) if needed in this view.
		
		echo "</div>";
		echo "</td>";
		
		// Paths
		echo "<td style='padding:14px 4px; text-align:center;'>";
		echo "<div style='display:flex; flex-direction:column; align-items:center;'>";
		echo "<a href='endgame.php?id=$bID&rank=$rank' style='text-decoration:none; display:flex; flex-direction:column; align-items:center; transition:opacity 0.2s;' onmouseover=\"this.style.opacity='0.7'\" onmouseout=\"this.style.opacity='1'\">";
		echo "<span style='color:$textColor; font-weight:600; text-decoration:underline; text-decoration-color:$mutedColor;'>$numPaths</span>";
		echo "<span style='color:$mutedColor; font-size:0.75rem;'>$pathsPct</span>";
		echo "</a>";
		echo "</div>";
		echo "</td>";
		
		// Probability Pill
		echo "<td style='padding:14px 4px; text-align:right;'>";
		
		// Color based on Rank in List (Medals)
		$pillColor = "#334155"; // Default Slate
		$pillText = "var(--text-light)";
		
		if ($i == 0) {
			$pillColor = "#a16207"; // Gold
			$pillText = "#fef08a";
		} elseif ($i == 1) {
			$pillColor = "#52525b"; // Silver (Zinc-600)
			$pillText = "#f4f4f5";
		} elseif ($i == 2) {
			$pillColor = "#7c2d12"; // Bronze
			$pillText = "#fed7aa";
		}
		
		echo "<span style='display:inline-block; padding:4px 10px; border-radius:9999px; background:$pillColor; color:$pillText; font-size:0.85rem; font-weight:700; font-family:monospace; border:1px solid rgba(255,255,255,0.1);'>$pWinDisplay</span>";
		echo "</td>";
		
		echo "</tr>";
	}
	echo "</tbody>";
	echo "</table>";
	echo "</div>"; // End content
	echo "</div>"; // End card
}
?>