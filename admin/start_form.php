<?php
include 'functions.php';
validatecookie();
include("header.php");

$stmt = $db->query("SELECT region1, region2, region3, region4 FROM meta WHERE id=1");
$meta_regions = $stmt->fetch(PDO::FETCH_ASSOC);

$r1 = $meta_regions['region1'] ?: "Region 1";
$r2 = $meta_regions['region2'] ?: "Region 2";
$r3 = $meta_regions['region3'] ?: "Region 3";
$r4 = $meta_regions['region4'] ?: "Region 4";

$existing = null;
$checkStmt = $db->query("SELECT * FROM `master` WHERE id=1");
$existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
/* Header */
.sf-header {
    text-align: center;
    margin-bottom: 1.25rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.sf-header h2 { margin: 0 0 .25rem; font-size: 1.5rem; color: var(--accent-orange); border: none; }
.sf-header p { color: var(--text-muted); font-size: .85rem; margin: 0; opacity: .6; }
.sf-counter {
    display: inline-flex; align-items: center; gap: .4rem;
    margin-top: .6rem; font-size: .8rem; color: var(--text-muted);
    background: rgba(255,255,255,0.04); padding: 3px 12px; border-radius: 20px;
}
.sf-counter b { color: var(--accent-orange); font-size: .9rem; }

/* 3-column bracket layout */
.sf-bracket {
    display: flex;
    justify-content: space-between;
    align-items: stretch;
    gap: 1.25rem;
    margin-top: .5rem;
}
.sf-col-left, .sf-col-right {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.sf-col-center {
    width: 200px;
    min-width: 160px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    position: relative;
}

/* Region block */
.sf-region {
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 8px;
    overflow: hidden;
    background: rgba(255,255,255,0.015);
}
.sf-region:focus-within { border-color: var(--accent-orange); }
.sf-region-title {
    padding: 8px 14px;
    background: rgba(255,255,255,0.04);
    border-bottom: 1px solid rgba(255,255,255,0.06);
    font-size: .95rem;
    font-weight: 700;
    color: var(--accent-orange);
    text-align: center;
}

/* Matchup */
.sf-matchup {
    padding: 5px 12px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.sf-matchup:last-child { border-bottom: none; }

.sf-team {
    display: flex;
    align-items: center;
}
.sf-seed {
    width: 24px;
    min-width: 24px;
    font-size: .75rem;
    font-weight: 700;
    color: var(--text-muted);
    text-align: right;
    padding-right: 8px;
}

.sf-team input {
    flex: 1;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 4px;
    color: var(--text-light);
    padding: 5px 8px;
    font-size: .85rem;
    font-family: inherit;
    outline: none;
    transition: border-color .15s, background .15s;
}
.sf-team input:focus {
    border-color: var(--accent-orange);
    background: rgba(255,255,255,0.06);
}
.sf-team input::placeholder { color: rgba(255,255,255,0.15); }

/* Center graphic */
.sf-center-lines {
    position: absolute;
    top: 8%; bottom: 8%;
    left: 0; right: 0;
    pointer-events: none;
}
.sf-cline {
    position: absolute;
    border: 1px solid rgba(255,255,255,0.08);
}
.sf-cl-lt { top: 5%; left: 0; width: 40%; height: 35%; border-width: 0 1px 1px 0; border-bottom-right-radius: 8px; }
.sf-cl-lb { bottom: 5%; left: 0; width: 40%; height: 35%; border-width: 1px 1px 0 0; border-top-right-radius: 8px; }
.sf-cl-rt { top: 5%; right: 0; width: 40%; height: 35%; border-width: 0 0 1px 1px; border-bottom-left-radius: 8px; }
.sf-cl-rb { bottom: 5%; right: 0; width: 40%; height: 35%; border-width: 1px 0 0 1px; border-top-left-radius: 8px; }
.sf-cl-bridge {
    position: absolute;
    top: 50%; left: 38%; right: 38%;
    height: 1px;
    background: rgba(255,255,255,0.08);
    transform: translateY(-50%);
}

.sf-champ-box {
    text-align: center;
    padding: 12px 16px;
    border: 2px solid var(--accent-orange);
    border-radius: 8px;
    background: rgba(255,255,255,0.02);
    z-index: 5;
}
.sf-champ-box .sub { font-size: .6rem; text-transform: uppercase; letter-spacing: 2px; color: var(--text-muted); margin-bottom: 2px; }
.sf-champ-box .main { font-size: 1.1rem; font-weight: 800; color: var(--accent-orange); }

/* Submit */
.sf-submit {
    text-align: center;
    padding: 1.5rem 0 .5rem;
}
.sf-submit button {
    padding: 12px 40px; font-size: 1rem; font-weight: 700; cursor: pointer;
    background: var(--accent-orange); color: var(--accent-text); border: none; border-radius: 6px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.3); display: inline-flex; align-items: center; gap: 8px;
    transition: all .2s;
}
.sf-submit button:hover { transform: translateY(-1px); filter: brightness(1.1); }

@media(max-width:900px) {
    .sf-bracket { flex-direction: column; }
    .sf-col-center { display: none; }
}
</style>

<div id="main">
<div class="full">
<div class="content-card" style="width:98%; margin:0 auto;">

    <div class="sf-header">
        <h2><i class="fa-solid fa-basketball"></i> Initialize Bracket</h2>
        <p>Enter the 64 first-round teams. Seeds follow standard bracket order.</p>
        <div class="sf-counter"><i class="fa-solid fa-users"></i> <b id="teamCount">0</b> / 64</div>
    </div>

    <form method="post" action="start.php" id="setupForm">

        <div class="sf-bracket">
            <!-- Left Column -->
            <div class="sf-col-left">
                <?php renderRegion($r1, 1, $existing); ?>
                <?php renderRegion($r2, 17, $existing); ?>
            </div>

            <!-- Center -->
            <div class="sf-col-center">
                <div class="sf-center-lines">
                    <div class="sf-cline sf-cl-lt"></div>
                    <div class="sf-cline sf-cl-lb"></div>
                    <div class="sf-cline sf-cl-rt"></div>
                    <div class="sf-cline sf-cl-rb"></div>
                    <div class="sf-cl-bridge"></div>
                </div>
                <div class="sf-champ-box">
                    <div class="sub">Semifinals</div>
                    <div class="main"><i class="fa-solid fa-trophy"></i> CHAMPIONSHIP</div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="sf-col-right">
                <?php renderRegion($r3, 33, $existing); ?>
                <?php renderRegion($r4, 49, $existing); ?>
            </div>
        </div>

        <div class="sf-submit">
            <button type="submit"><i class="fa-solid fa-check-circle"></i> Finish Setup</button>
        </div>

    </form>

</div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var ii = document.querySelectorAll('#setupForm input[type="text"]');
    var ct = document.getElementById('teamCount');
    function u() {
        var n=0; ii.forEach(function(i){if(i.value.trim())n++;});
        ct.textContent=n; ct.style.color=n===64?'#22c55e':'';
    }
    ii.forEach(function(i){i.addEventListener('input',u);}); u();
});
</script>

</div><?php include('footer.php'); ?>
</body>
</html>

<?php
function renderRegion($name, $startId, $existing) {
    $seeds = [1,16,8,9,5,12,4,13,6,11,3,14,7,10,2,15];
    
    

    echo "<div class='sf-region'>";
    echo "<div class='sf-region-title'>" . htmlspecialchars($name) . "</div>";

    $id = $startId;
    for ($i = 0; $i < 8; $i++) {
        $s1 = $seeds[$i*2]; $s2 = $seeds[$i*2+1];
        $id1 = $id++; $id2 = $id++;
        $v1 = ($existing && isset($existing[$id1])) ? htmlspecialchars($existing[$id1]) : '';
        $v2 = ($existing && isset($existing[$id2])) ? htmlspecialchars($existing[$id2]) : '';
        $h1 = $s1 <= 4 ? ' hi' : '';
        $h2 = $s2 <= 4 ? ' hi' : '';

        echo "<div class='sf-matchup'>";
        echo "<div class='sf-team'><div class='sf-seed$h1'>$s1.</div><input type='text' name='$id1' value='$v1' placeholder='Team Name' autocomplete='off'></div>";
        echo "<div class='sf-team'><div class='sf-seed$h2'>$s2.</div><input type='text' name='$id2' value='$v2' placeholder='Team Name' autocomplete='off'></div>";
        echo "</div>";
    }

    echo "</div>";
}
?>



