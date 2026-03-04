<?php

function viewBracket( $meta, $picks, $team_data, $rank, $score_data, $best_data, $showProfile = true, $showPrintButton = true )
{
?>

<script>
function displayNextRoundWinValue( val )
{
	if(val) window.status = "A win in the next round for this team is worth " + val;
	return true;
}

function clearStatus()
{
	window.status = "";
	return true;
}
</script>

<style type="text/css">
/* Additional overrides for view styling if needed */
.bracket-wrapper .matchup {
    min-height: 40px; /* Ensure space for complex HTML in picks */
}
.team span.strike {
    text-decoration: line-through;
    color: #ef4444; /* Red */
    opacity: 0.7;
}
.team span.right {
    color: #22c55e; /* Green */
    font-weight: bold;
}
.team span.gamevalue {
    font-size: 0.8em;
    color: #aaa;
    margin-left: 5px;
}
.team span.correction {
     display: block;
     font-size: 0.8em;
     color: #ef4444;
}

/* Insight Styles */
.highlight-team {
    background-color: #f59e0b !important; /* Amber */
    color: #fff !important;
    font-weight: bold;
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
    border-color: #f59e0b !important;
    position: relative;
    z-index: 10;
}

.cinderella-badge {
    float: right;
    font-size: 1.2em;
    margin-left: 5px;
    filter: drop-shadow(0 0 2px rgba(255,255,255,0.5));
}

/* Pick Stats Tooltip */
.pick-stats-tooltip {
    position: absolute;
    background: rgba(30, 41, 59, 0.95);
    color: #fff;
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid var(--accent-orange);
    font-size: 0.85rem;
    pointer-events: none;
    z-index: 1000;
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.2s;
    transform: translateY(-100%) translateX(-20%);
}
.pick-stats-tooltip span.stat-highlight {
    color: var(--accent-orange);
    font-weight: bold;
    font-size: 1.1em;
}

/* Ensure links in print mode don't show href */
@media print {
    a[href]:after { content: none !important; }
    
    /* Force Light Mode for Printing */
    body, #main, .full, .bracket-wrapper { background: #FFFFFF !important; color: #000000 !important; }
    .bracket-wrapper * { background: transparent !important; color: #000000 !important; border-color: #000 !important; box-shadow: none !important; }
    .bracket-wrapper .team { border: 1px solid #000 !important; }
    .bracket-wrapper h3, .bracket-wrapper h4 { color: #000 !important; text-shadow: none !important; }
    .bracket-wrapper .connect-line { border-color: #000 !important; }
    /* Target inline styled boxes like tiebreaker */
    .bracket-wrapper div[style*="background: #333"] { background: #FFFFFF !important; border: 1px solid #000 !important; }
    .bracket-wrapper label { color: #000 !important; }
    
    /* Hide buttons */
    .no-print, .cinderella-badge, .pick-stats-tooltip { display: none !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Path to Victory (Highlighting)
    const teams = document.querySelectorAll('.matchup .team');
    
    function cleanTeamName(text) {
        // Remove Seed "1. " and Score "(10)"
        let clean = text.replace(/^\d+\.\s+/, '').replace(/\s*\(\d+\)$/, '').trim();
        clean = clean.split(' - ')[0]; 
        return clean;
    }

    // 2. Fetch Stats (only if logged in)
    let pickStats = null;
    <?php if (isset($_SESSION['user_id'])): ?>
    fetch('api/pick_consensus.php')
        .then(res => res.json())
        .then(data => {
            pickStats = data.stats; // Map of GameID -> TeamName -> Percent
        })
        .catch(err => console.log('Stats error', err));
    <?php endif; ?>

    // Create Tooltip Element
    const tooltip = document.createElement('div');
    tooltip.className = 'pick-stats-tooltip';
    document.body.appendChild(tooltip);

    teams.forEach(team => {
        const rawText = team.textContent.trim();
        if(!rawText || rawText === 'TBD' || rawText === '&nbsp;') return;

        const name = cleanTeamName(rawText);
        
        team.addEventListener('mouseenter', (e) => {
            // Highlighting
             teams.forEach(t => {
                 if(cleanTeamName(t.textContent.trim()) === name) {
                     t.classList.add('highlight-team');
                 }
             });

             // Stats Tooltip
             const gameId = team.getAttribute('data-gameid');
             // Team names may differ slightly between the UI (which may include seeds, e.g., "1. Duke") 
             // and the Database (which may just store "Duke").
             // We attempt an exact match first, then fall back to a fuzzy substrate match.
             
             if (pickStats && gameId && pickStats[gameId]) {
                 // Try exact match
                 let pct = pickStats[gameId][rawText];
                 
                 // Fallback: Try regex matching keys if exact fails (e.g. spaces/encoding)
                 if (pct === undefined) {
                     // Try finding a key that contains the clean name
                     const clean = cleanTeamName(rawText);
                     for(let k in pickStats[gameId]) {
                         if(k.includes(clean)) {
                             pct = pickStats[gameId][k];
                             break;
                         }
                     }
                 }

                 if (pct !== undefined) {
                     tooltip.innerHTML = `<span class="stat-highlight">${pct}%</span> picked to win`;
                     tooltip.style.opacity = 1;
                     
                     // Position
                     const rect = team.getBoundingClientRect();
                     tooltip.style.top = (rect.top + window.scrollY - 30) + 'px';
                     tooltip.style.left = (rect.left + window.scrollX + (rect.width/2)) + 'px';
                 }
             }
        });

        team.addEventListener('mouseleave', () => {
             teams.forEach(t => {
                 t.classList.remove('highlight-team');
             });
             tooltip.style.opacity = 0;
        });

    });
});
</script>



<?php if($showProfile) { ?>
<div id="main" style="width:100% !important; max-width:100% !important"> 
  <div class="full"> 
     <div id="bracketheader" style="background:#222; padding:15px; color:white; border-bottom:2px solid var(--accent-orange); margin-bottom:20px;">
        <h2 style="margin:0; border:none;"><?php
     	if (isset($_SESSION['useremail']) == true)
		{
            $avatar = isset($picks['avatar_url']) && $picks['avatar_url'] ? $picks['avatar_url'] : 'avatar.php?name='.urlencode(strip_tags($picks['name'])).'&background=random';
            $err = "this.src='avatar.php?name=" . urlencode(strip_tags(stripslashes($picks['name']))) . "&background=random'";
            
            echo "<div style='display:flex; align-items:center; gap:15px;'>";
            echo "<a href='profile.php?id={$picks['id']}' style='text-decoration:none; display:flex; align-items:center; gap:15px; color:white; transition: 0.2s;' onmouseover='this.style.opacity=0.8' onmouseout='this.style.opacity=1'>";
            echo "<img src='$avatar' onerror=\"$err\" style='width:50px; height:50px; border-radius:50%; border:2px solid var(--accent-orange);'>";
			echo "<div>" . h($picks['name']) . (isset($picks['person']) ? " <span style='font-size:0.6em; color:#ccc'>(" . h($picks['person']) . ")</span>" : "");
            echo "<div style='font-size:0.6em; color:var(--accent-orange); margin-top:2px;'>View Hoops Card &rarr;</div>";
            echo "</div>";
            echo "</a>";
            echo "</div>";
		}
		else
		{
     		echo h($picks['name']);
     	}
	?></h2>
		  <div style="display:flex; gap:20px; font-size:0.9em; color:#ddd; margin-top:5px;">
			<?php if( $rank != "" ) { ?> <div>Rank: <b style="color:var(--accent-orange)"><?php echo $rank ?></b></div><?php } ?>
			<?php if( $score_data['score'] != "") { ?><div>Score: <b><?php echo $score_data['score'] ?></b></div><?php } ?>
			<?php if( $best_data['score'] != "") { ?><div>Max Possible: <b><?php echo $best_data['score'] ?></b></div><?php } ?>
			<?php if( $best_data['score'] != "") { ?><div>Points Remaining: <b><?php echo $best_data['score']- $score_data['score'] ?></b></div><?php } ?>
		 </div>
	 </div>

<?php } ?>

	 <div class="no-print" style="text-align:right; margin-bottom:10px;">
        <!-- PDF Libs (Always included so function exists) -->
        <script src="js/lib/html2canvas.js"></script>
        <script src="js/lib/jspdf.js"></script>
        
        <?php if($showPrintButton) { ?>
        <button id="pdfBtn" onclick="downloadPDF(this)" style="background:var(--accent-orange); color:var(--accent-text); padding:10px 20px; cursor:pointer; border:none; border-radius:6px; font-weight:bold; font-size:1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.3); display:inline-flex; align-items:center; gap:8px; transition:all 0.2s;" onmouseover="this.style.background='var(--accent-orange-hover)';" onmouseout="this.style.background='var(--accent-orange)';">
            <i class="fa-solid fa-print"></i> Print Bracket
        </button>
        <?php } ?>

        <script>
        window.jsPDF = window.jspdf.jsPDF;
        
        function downloadPDF(btn) {
            // Support passing the button explicitly or finding it by ID.
            // When custom UI controls trigger this function without passing the button reference,
            // the status and disabled state updates are bypassed.
            if(btn) {
                originalText = btn.innerHTML;
                btn.innerHTML = "⏳ Generating...";
                btn.disabled = true;
                btn.style.opacity = 0.7;
            }
            
            const originalElement = document.querySelector('.bracket-wrapper');
            
            // 1. Clone the bracket
            const clone = originalElement.cloneNode(true);
            
            // 2. Position off-screen but visible to renderer
            clone.style.position = 'absolute';
            clone.style.left = '-9999px';
            clone.style.top = '0px';
            clone.style.width = '1600px'; // Force width
            clone.style.padding = '40px';
            clone.style.background = '#FFFFFF';
            
            // 3. Add PDF Mode class to clone
            clone.classList.add('pdf-mode');
            
            // 4. Inject styles globally (targeting the clone class)
            // We keep the style injection but it only affects .pdf-mode which we applied to clone
            if (!document.getElementById('pdf-styles')) {
                const style = document.createElement('style');
                style.id = 'pdf-styles';
                style.innerHTML = `
                    .bracket-wrapper.pdf-mode * { background: transparent !important; color: #000000 !important; border-color: #000 !important; box-shadow: none !important; }
                    .bracket-wrapper.pdf-mode { background: #FFFFFF !important; }
                    .bracket-wrapper.pdf-mode .team { border: 1px solid #000 !important; }
                    .bracket-wrapper.pdf-mode h3, .bracket-wrapper.pdf-mode h4 { color: #000 !important; text-shadow: none !important; }
                    .bracket-wrapper.pdf-mode .connect-line { border-color: #000 !important; }
                    .bracket-wrapper.pdf-mode div[style*="background: #333"] { background: #FFFFFF !important; border: 1px solid #000 !important; }
                    .bracket-wrapper.pdf-mode label { color: #000 !important; }
                    .bracket-wrapper.pdf-mode .pdf-only { display: block !important; margin-bottom: 20px; }
                `;
                document.head.appendChild(style);
            }

            // 5. Append clone to body
            document.body.appendChild(clone);
            
            html2canvas(clone, {
                scale: 2, 
                useCORS: true, 
                backgroundColor: '#FFFFFF'
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('l', 'mm', 'a4'); 
                
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                
                const widthRatio = pageWidth / canvas.width;
                const heightRatio = pageHeight / canvas.height;
                const ratio = widthRatio > heightRatio ? heightRatio : widthRatio;
                
                const canvasWidth = canvas.width * ratio;
                const canvasHeight = canvas.height * ratio;
                
                const marginX = (pageWidth - canvasWidth) / 2;
                const marginY = (pageHeight - canvasHeight) / 2;
                
                pdf.addImage(imgData, 'PNG', marginX, marginY, canvasWidth, canvasHeight);
                pdf.save('BracketChallenge_Bracket.pdf');
                
                // Cleanup
                document.body.removeChild(clone);
                if(btn) {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.opacity = 1;
                }
            });
        }
        </script>
	  </div>

     <div class="bracket-wrapper" style="background: transparent;">
        <!-- LEFT SIDE -->
        <div class="bracket-split-left">
            <?php renderViewRegion($meta['region1'], 1, array(1,2,3,4,5,6,7,8), array(33,34,35,36), array(49,50), 57, $team_data, $picks, 61, 0); ?>
            <?php renderViewRegion($meta['region2'], 17, array(9,10,11,12,13,14,15,16), array(37,38,39,40), array(51,52), 58, $team_data, $picks, 61, 1); ?>
        </div>

        <!-- CENTER -->
        <div class="bracket-center">
            <!-- PDF/Print Only Header -->
            <div class="pdf-only" style="display:none; text-align:center; margin-bottom:10px;">
                <h1 style="margin:0; font-size:1.8em; font-weight:bold; color:#000; line-height:1.2; text-shadow:none;"><?php echo h($picks['name']); ?></h1>
                <?php if(isset($picks['person'])) { ?>
                    <div style="font-size:1.2em; color:#444; font-style:italic;">By <?php echo h($picks['person']); ?></div>
                <?php } ?>
                <div style="font-size:0.8em; color:#666; margin-top:5px;">Tournament <?php echo date('Y'); ?></div>
            </div>

            <h2 style="text-align:center; color:#fff; font-size:1.2rem;">SEMIFINALS</h2>
            
            <!-- Final Four Game 1 (61) -->
            <div class="matchup">
                <!-- Slot 0: Winner of Region 1 (Game 57 winner -> Picks[57]) -->
                <div class="team" data-gameid="61"><?php echo isset($picks[57]) ? $picks[57] : '&nbsp;'; ?></div>
                <!-- Slot 1: Winner of Region 2 (Game 58 winner -> Picks[58]) -->
                <div class="team" data-gameid="61"><?php echo isset($picks[58]) ? $picks[58] : '&nbsp;'; ?></div>
            </div>

            <!-- Final Four Game 2 (62) -->
            <div class="matchup">
                <!-- Slot 0: Winner of Region 3 (Game 59 winner -> Picks[59]) -->
                <div class="team" data-gameid="62"><?php echo isset($picks[59]) ? $picks[59] : '&nbsp;'; ?></div>
                <!-- Slot 1: Winner of Region 4 (Game 60 winner -> Picks[60]) -->
                <div class="team" data-gameid="62"><?php echo isset($picks[60]) ? $picks[60] : '&nbsp;'; ?></div>
            </div>

            <h2 style="text-align:center; color:var(--accent-orange); font-size:1.4rem;">CHAMPIONSHIP</h2>
            <!-- Championship (63) -->
            <div class="matchup" style="border: 2px solid var(--accent-orange);">
                <!-- Slot 0: Winner of Game 61 -> Picks[61] -->
                <div class="team" data-gameid="63"><?php echo isset($picks[61]) ? $picks[61] : '&nbsp;'; ?></div>
                <!-- Slot 1: Winner of Game 62 -> Picks[62] -->
                <div class="team" data-gameid="63"><?php echo isset($picks[62]) ? $picks[62] : '&nbsp;'; ?></div>
            </div>

            <div style="text-align:center; margin-top:20px;">
                <h3 style="color:#fff;"><?php echo date('Y'); ?> Champion</h3>
                <div id="champion_display" class="team" data-gameid="63" style="font-size:1.5em; font-weight:bold; color:var(--accent-orange); min-height:40px; margin-bottom: 20px;">
                    <?php echo isset($picks[63]) ? $picks[63] : '?'; ?>
                </div>
                
                <?php if( !empty($picks['tiebreaker']) ) { ?>
                <div style="background: #333; padding: 10px; border-radius: 5px; display:inline-block; border:1px solid #444;">
                    <label style="color:#aaa; font-size:0.8em;">TIEBREAKER</label><br>
                    <span style="font-size:1.2em; color:#fff; font-weight:bold;"><?php echo $picks['tiebreaker']; ?></span>
                </div>
                <?php } ?>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="bracket-split-right">
            <?php renderViewRegion($meta['region3'], 33, array(17,18,19,20,21,22,23,24), array(41,42,43,44), array(53,54), 59, $team_data, $picks, 62, 0); ?>
            <?php renderViewRegion($meta['region4'], 49, array(25,26,27,28,29,30,31,32), array(45,46,47,48), array(55,56), 60, $team_data, $picks, 62, 1); ?>
        </div>
     </div>
   </div> 
</div> 

<?php
}

function renderViewRegion($name, $startTeamIndex, $r1Games, $r2Games, $r3Games, $r4Game, $team_data, $picks, $destGameId, $destSlot) {
	echo "<div class='region-container'>";
	
	// Round 1
	echo "<div class='round'><h3>$name Round 1</h3>";
	foreach($r1Games as $gId) {
		$t1_idx = ($gId * 2) - 1;
		$t2_idx = $gId * 2;
        // In View mode, team_data has the Seed + Name string
		$t1_html = isset($team_data[$t1_idx]) ? $team_data[$t1_idx] : "TBD";
		$t2_html = isset($team_data[$t2_idx]) ? $team_data[$t2_idx] : "TBD";
		
		echo "<div class='matchup'>
				<div class='team' data-gameid='$gId'>$t1_html</div>
				<div class='team' data-gameid='$gId'>$t2_html</div>
			  </div>";
	}
	echo "</div>";

	// Round 2
	echo "<div class='round'><h3>Round 2</h3>";
	foreach($r2Games as $gId) {
        $src1 = ($gId - 32) * 2 - 1;
        $src2 = ($gId - 32) * 2;
        
		echo "<div class='matchup'>
				<div class='team' data-gameid='$gId'>".(isset($picks[$src1]) ? $picks[$src1] : '&nbsp;')."</div>
				<div class='team' data-gameid='$gId'>".(isset($picks[$src2]) ? $picks[$src2] : '&nbsp;')."</div>
			  </div>";
	}
	echo "</div>";

	// Round 3 (Sweet 16)
	echo "<div class='round'><h3>Round of 16</h3>";
	foreach($r3Games as $gId) {
        $src1 = 33 + ($gId - 49) * 2;
        $src2 = $src1 + 1;

		echo "<div class='matchup'>
				<div class='team' data-gameid='$gId'>".(isset($picks[$src1]) ? $picks[$src1] : '&nbsp;')."</div>
				<div class='team' data-gameid='$gId'>".(isset($picks[$src2]) ? $picks[$src2] : '&nbsp;')."</div>
			  </div>";
	}
	echo "</div>";

	// Round 4 (Elite 8)
	echo "<div class='round'><h3>Quarterfinals</h3>";
	$gId = $r4Game; // e.g. 57
    
    $src1 = 49 + ($gId - 57) * 2;
    $src2 = $src1 + 1;
	
	echo "<div class='matchup'>
			<div class='team' data-gameid='$gId'>".(isset($picks[$src1]) ? $picks[$src1] : '&nbsp;')."</div>
			<div class='team' data-gameid='$gId'>".(isset($picks[$src2]) ? $picks[$src2] : '&nbsp;')."</div>
		  </div>";
	echo "</div>";
	
	echo "</div>"; // End Region Container
}
?>
