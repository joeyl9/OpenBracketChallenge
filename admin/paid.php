<?php
include("database.php");
include 'functions.php';
validatecookie();
check_admin_auth('pay'); // Pay Editor or higher
include("header.php");
$query = "SELECT id,name,paid,email,person,type FROM `brackets` ORDER BY type, name";
$stmt = $db->query($query);
?>
	<div id="main">
		<div class="full">
			<h2>Who's Paid?</h2>
            <div style="margin-bottom: 20px;">
                <a href="index.php" class="btn-outline">&larr; Back to Dashboard</a>
            </div>
			<form method="post" action="post.php?action=paid">
			<?php csrf_field(); ?>
			<div class="table-container">
			<table class="adminPaid" id="paidTable">
					<tr class="paidHeader">
					    <td class="paidPerson" onclick="sortTable(0)" style="cursor:pointer;" title="Sort">Type <i class="fa-solid fa-sort" style="font-size:0.8em; color:#666;"></i></th>
						<td class="paidPerson" onclick="sortTable(1)" style="cursor:pointer;" title="Sort">Name <i class="fa-solid fa-sort" style="font-size:0.8em; color:#666;"></i></th>
						<td class="paidBracket" onclick="sortTable(2)" style="cursor:pointer;" title="Sort">Bracket <i class="fa-solid fa-sort" style="font-size:0.8em; color:#666;"></i></th>
						<td class="paidEmail" onclick="sortTable(3)" style="cursor:pointer;" title="Sort">Email <i class="fa-solid fa-sort" style="font-size:0.8em; color:#666;"></i></th>
						<td class="paidSelector">Paid</th>
						<td class="paidSelector">Unpaid</th>
						<td class="paidSelector">Exempt</th>
					</tr>
					<?php
					$rowCount = 0;
					while($name=$stmt->fetch(PDO::FETCH_NUM)) {
						$rowCount = $rowCount + 1;
						// $name indices: 0=id, 1=name, 2=paid, 3=email, 4=person, 5=type
						if (strtolower(trim($name[5])) == 'sweet16') {
                            // Sweet 16: Reset Theme (Outline)
						    $typeLabel = '<span style="background:transparent; color:var(--accent-orange); padding:2px 8px; border-radius:4px; font-size:0.8em; font-weight:bold; border:1px solid var(--accent-orange);">Second Chance</span>';
						} else {
                            // Main: Calculate Theme (Solid)
						    $typeLabel = '<span style="background:var(--accent-orange); color:var(--accent-text); padding:3px 8px; border-radius:4px; font-size:0.8em; font-weight:bold; border:1px solid var(--accent-orange);">Main</span>';
                        }
						
						echo '<tr class="row'.fmod($rowCount,2).'">' . "\n";
						echo "<td>$typeLabel</td>";
						echo "<td>\n";
						echo h($name[4]) ."</td><td>". h($name[1]) ."</td><td>" . h($name[3]);
						echo "</td>\n";
						echo "<td>\n";
						if($name[2]==1)
							echo "<input type=\"radio\" name=\"$name[0]\" value=\"1\" checked>\n";
						else
							echo "<input type=\"radio\" name=\"$name[0]\" value=\"1\">\n";
						echo "</td>\n";
						echo "<td>\n";
						if($name[2]==0)
							echo "<input type=\"radio\" name=\"$name[0]\" value=\"0\" checked>\n";
						else
							echo "<input type=\"radio\" name=\"$name[0]\" value=\"0\">\n";
						echo "</td>\n";
						echo "<td>\n";
						if($name[2]==2)
							echo "<input type=\"radio\" name=\"$name[0]\" value=\"2\" checked>\n";
						else
							echo "<input type=\"radio\" name=\"$name[0]\" value=\"2\">\n";
						echo "</td>\n";
						echo "</tr>\n";
					}
					?>
				<tr>
					<td colspan="7" align="center" style="padding:20px;">
						<button type="submit" style="padding:8px 24px; font-size:1rem; border-radius:6px; cursor:pointer; background:var(--accent-orange); color:white; border:none; font-weight:bold; transition: background 0.2s;" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='var(--accent-orange)'">
							<i class="fa-solid fa-floppy-disk"></i> Update Payments
						</button>
					</td>
				</tr>
			</table>
			</div>
		</form>
	</div>
</div>
</div>
<script>
function sortTable(n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById("paidTable");
  switching = true;
  dir = "asc"; 
  while (switching) {
    switching = false;
    rows = table.rows;
    // Loop through all table rows (except the first, which contains table headers, and last which is submit):
    for (i = 1; i < (rows.length - 2); i++) {
      shouldSwitch = false;
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      
      // Simple text content comparison (strip tags for badge)
      var xContent = x.textContent || x.innerText;
      var yContent = y.textContent || y.innerText;
      
      if (dir == "asc") {
        if (xContent.toLowerCase() > yContent.toLowerCase()) {
          shouldSwitch = true;
          break;
        }
      } else if (dir == "desc") {
        if (xContent.toLowerCase() < yContent.toLowerCase()) {
          shouldSwitch = true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      switchcount ++;      
    } else {
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}
</script>
			</div>
		</form>
	</div>
</div>
</div>
<?php include('footer.php'); ?>
</body>
</html>


