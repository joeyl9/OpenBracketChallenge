<?php
include("header.php");

$query = "SELECT * FROM `meta` WHERE id=1";
$stmt = $db->query($query);
if(!($points = $stmt->fetch(PDO::FETCH_ASSOC))) {
	echo "<div style='text-align:center; padding:50px;'>Please <a href=\"admin/install_ui.php\">configure the site.</a></div>";
	exit();
}

$scoring = $db->query("SELECT seed,`1`,`2`,`3`,`4`,`5`,`6` FROM `scoring` WHERE `type` = 'main' ORDER BY `seed`");
?>

<div id="main" class="full">
	<div class="content-card" style="max-width:1400px; margin:0 auto;">
    
        <div style="border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:30px;">
             <h2 style="margin:0; color:var(--accent-orange);"><i class="fa-solid fa-scale-balanced"></i> Tournament Rules</h2>
        </div>

        <div style="margin-bottom:40px;">
    		<h3 style="color:var(--text-light); margin-bottom:15px; display:flex; align-items:center; gap:10px;"><i class="fa-solid fa-file-contract"></i> General Information</h3>
    		<div class="rules-content" style="line-height:1.8; color:var(--text-muted); font-size:1.05rem; padding-left:15px; border-left:3px solid var(--accent-orange); background:rgba(255,255,255,0.02); padding:20px; border-radius:0 8px 8px 0;">
    			<?php echo nl2br($points['rules']); ?>
    		</div>
        </div>

        <div>
    		<h3 style="color:var(--text-light); margin-bottom:5px; display:flex; align-items:center; gap:10px;"><i class="fa-solid fa-table-list"></i> Scoring Breakdown</h3>
            <p style="color:var(--text-muted); margin-bottom:20px; font-size:0.9rem; margin-left:32px;">Points awarded for a correct pick in each round based on seed.</p>
    		
    		<div style="overflow-x:auto;">
        		<table style="width:100%; border-collapse:collapse; min-width:600px;">
        			<thead>
            			<tr style="border-bottom:2px solid var(--border-color); color:var(--accent-orange);">
            				<th style="padding:15px; text-align:center;">Seed #</th>
            				<th style="padding:15px; text-align:center;">Round 1</th>
            				<th style="padding:15px; text-align:center;">Round 2</th>
            				<th style="padding:15px; text-align:center;">Round 3</th>
            				<th style="padding:15px; text-align:center;">Round 4</th>
            				<th style="padding:15px; text-align:center;">Round 5</th>
            				<th style="padding:15px; text-align:center;">Round 6</th>
            			</tr>
        			</thead>
                    <tbody>
            		<?php
            		while ($row = $scoring->fetch(PDO::FETCH_ASSOC))
            		{
            			echo "<tr style='border-bottom:1px solid rgba(255,255,255,0.05); transition:background 0.2s;' onmouseover=\"this.style.background='rgba(255,255,255,0.05)'\" onmouseout=\"this.style.background='transparent'\">
                                <td style='padding:12px; text-align:center; font-weight:bold; color:var(--text-light);'>".$row['seed']."</td>";
            			for( $i=1; $i < 7; $i++ )
            			{
                            $val = $row[$i];
                            $style = $val > 0 ? "color:var(--text-light);" : "color:var(--text-muted);";
                            if($val >= 10) $style = "color:var(--accent-orange); font-weight:bold;";
            				echo "<td style='padding:12px; text-align:center; $style'>".$val."</td>";
            			}
            			echo "</tr>";
            		}
            		?>
                    </tbody>
        		</table>
            </div>
        </div>
	</div>
<?php include("footer.php"); ?>
</div>
</body>
</html>

