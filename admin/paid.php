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
					    <td class="paidPerson" data-dir="" onclick="sortTable(0, this)" style="cursor:pointer;" title="Sort">Type <i class="fa-solid fa-sort" style="font-size:0.8em; color:#666;"></i></td>
						<td class="paidPerson" data-dir="" onclick="sortTable(1, this)" style="cursor:pointer;" title="Sort">Name <i class="fa-solid fa-sort" style="font-size:0.8em; color:#666;"></i></td>
						<td class="paidBracket" data-dir="" onclick="sortTable(2, this)" style="cursor:pointer;" title="Sort">Bracket <i class="fa-solid fa-sort" style="font-size:0.8em; color:#666;"></i></td>
						<td class="paidEmail" data-dir="" onclick="sortTable(3, this)" style="cursor:pointer;" title="Sort">Email <i class="fa-solid fa-sort" style="font-size:0.8em; color:#666;"></i></td>
						<td class="paidSelector" data-dir="" onclick="sortTable(4, this)" style="cursor:pointer;" title="Sort">Paid <i class="fa-solid fa-sort" style="font-size:0.8em; color:#666;"></i></td>
						<td class="paidSelector" data-dir="" onclick="sortTable(5, this)" style="cursor:pointer;" title="Sort">Unpaid <i class="fa-solid fa-sort" style="font-size:0.8em; color:#666;"></i></td>
						<td class="paidSelector" data-dir="" onclick="sortTable(6, this)" style="cursor:pointer;" title="Sort">Exempt <i class="fa-solid fa-sort" style="font-size:0.8em; color:#666;"></i></td>
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
function getRadioRank(row) {
  var checked = row.querySelector("input[type='radio']:checked");
  if (!checked) return 99;
  var val = parseInt(checked.value, 10);
  if (val === 1) return 1; // Paid
  if (val === 2) return 2; // Exempt
  if (val === 0) return 3; // Unpaid
  return 99;
}

function sortTable(n, headerElem) {
  var table, rows, switching, i, x, y, shouldSwitch;
  table = document.getElementById("paidTable");
  
  var currentDir = headerElem.getAttribute("data-dir");
  var dir = (currentDir === "asc") ? "desc" : "asc";
  
  var allHeaders = table.rows[0].querySelectorAll("td[onclick]");
  for (var j = 0; j < allHeaders.length; j++) {
      var th = allHeaders[j];
      th.setAttribute("data-dir", "");
      th.style.color = ""; 
      var icon = th.querySelector("i");
      if(icon) {
          icon.className = "fa-solid fa-sort";
          icon.style.color = "#666";
      }
  }

  headerElem.setAttribute("data-dir", dir);
  headerElem.style.color = "var(--accent-orange)";
  var activeIcon = headerElem.querySelector("i");
  if(activeIcon) {
      activeIcon.className = (dir === "asc") ? "fa-solid fa-sort-up" : "fa-solid fa-sort-down";
      activeIcon.style.color = "var(--accent-orange)";
  }

  switching = true;
  while (switching) {
    switching = false;
    rows = table.rows;
    for (i = 1; i < (rows.length - 2); i++) {
      shouldSwitch = false;
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      
      if (n >= 4) {
          var xRank = getRadioRank(rows[i]);
          var yRank = getRadioRank(rows[i + 1]);
          if (dir === "asc") {
              if (xRank > yRank) {
                  shouldSwitch = true;
                  break;
              }
          } else if (dir === "desc") {
              if (xRank < yRank) {
                  shouldSwitch = true;
                  break;
              }
          }
      } else {
          var xContent = (x.textContent || x.innerText).trim().toLowerCase();
          var yContent = (y.textContent || y.innerText).trim().toLowerCase();
          
          if (dir === "asc") {
            if (xContent > yContent) {
              shouldSwitch = true;
              break;
            }
          } else if (dir === "desc") {
            if (xContent < yContent) {
              shouldSwitch = true;
              break;
            }
          }
      }
    }
    if (shouldSwitch) {
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
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


