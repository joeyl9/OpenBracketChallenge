<?php
include("header.php");

// Helper to render a modern list card
function renderPaymentCard($rows, $title, $icon, $colorClass, $isGrid = false) {
    // $rows passed directly
    $count = count($rows);
    
    // Color mapping
    $borderColor = '#334155'; // Default slate
    $headerColor = '#e2e8f0';
    $iconColor = 'var(--text-muted)';
    
    if($colorClass == 'red') { $borderColor = '#ef4444'; $iconColor = '#ef4444'; }
    if($colorClass == 'green') { $borderColor = '#22c55e'; $iconColor = '#22c55e'; }
    if($colorClass == 'blue') { $borderColor = '#3b82f6'; $iconColor = '#3b82f6'; }
    
    echo '<div style="background: rgba(255, 255, 255, 0.03); border: 1px solid '.$borderColor.'; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; height:100%;">';
    
    // Header
    echo '<div style="padding: 15px 20px; background: rgba(0,0,0,0.2); border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">';
    echo '<h3 style="margin: 0; color: '.$headerColor.'; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">';
    echo '<span style="font-size: 1.2rem;">'.$icon.'</span> '.$title;
    echo '</h3>';
    echo '<span style="background: rgba(0,0,0,0.3); padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; color: var(--text-light);">'.$count.'</span>';
    echo '</div>'; // End Header
    

    $gridStyle = ($isGrid && $count > 0) ? "display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:10px; padding:15px;" : "padding:0; display:flex; align-items:center; justify-content:center; min-height:100px;";
    
    echo '<div class="custom-scrollbar" style="'.$gridStyle.' max-height: 600px; overflow-y: auto;">';
    
    if($count > 0) {
        foreach ($rows as $row) {
             // row['type'] is available for logic, but we no longer need the badge since we use Tabs
             $typeTag = "";

             
             // FIX: Use Associative Array Keys
             $displayName = h(stripslashes($row['name']));
             $ownerName = h(stripslashes($row['person'])); // Or 'email' if you prefer, usually 'person' is the contact name
             
             // Item Styles
             if ($isGrid) {
                 // Tile Style
                 echo '<div style="padding: 10px; border: 1px solid rgba(255,255,255,0.05); background: rgba(255,255,255,0.02); border-radius: 6px; display: flex; flex-direction: column; gap:2px; transition: background 0.2s;" onmouseover="this.style.background=\'rgba(255,255,255,0.05)\'" onmouseout="this.style.background=\'rgba(255,255,255,0.02)\'">';
                 echo '<div style="font-weight: 500; color: #f1f5f9; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="'.$displayName.'">'.$displayName.$typeTag.'</div>';
                 if(isset($_SESSION['useremail'])) {
                    echo '<div style="font-size: 0.75rem; color: var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">'.$ownerName.'</div>';
                 }
                 echo '</div>';
             } else {
                 // List Style
                 echo '<div style="padding: 12px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; transition: background 0.2s;">';
                 echo '<div>';
                 echo '<div style="font-weight: 500; color: #f1f5f9;">'.$displayName.$typeTag.'</div>';
                 if(isset($_SESSION['useremail'])) {
                    echo '<div style="font-size: 0.8rem; color: var(--text-muted);">'.$ownerName.'</div>';
                 }
                 echo '</div>';
                 echo '</div>';
             }
        }
    } else {
    echo '<div style="padding: 20px; text-align: center; color: var(--text-muted); font-style: italic;">List is empty</div>';
    }
    echo '</div>'; // End List
    echo '</div>'; // End Card
}

$closed_query = "SELECT closed FROM `meta` WHERE id=1 LIMIT 1";
$stmt = $db->query($closed_query);
if(!($closed = $stmt->fetch(PDO::FETCH_NUM))) {
	echo "Please <a href=\"admin/install_ui.php\">configure the site.</a>\n";
	exit();
}

    // Calculate Pots
    $cost = $meta['cost'] ?? 0;
    $cut = $meta['cut'] ?? 0;
    
    // FETCH ALL DATA FIRST
    $stmt_unpaid = $db->query("SELECT name,person,type,email FROM `brackets` WHERE paid=0 ORDER BY type, name");
    $unpaid_all = $stmt_unpaid->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_paid = $db->query("SELECT name,person,type,email FROM `brackets` WHERE paid=1 ORDER BY type, name");
    $paid_all = $stmt_paid->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_exempt = $db->query("SELECT name,person,type,email FROM `brackets` WHERE paid=2 ORDER BY type, name");
    $exempt_all = $stmt_exempt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Counts & Pots (Global)
    $main_paid_count = 0;
    $s16_paid_count = 0;
    
    foreach($paid_all as $row) {
        $rType = isset($row['type']) ? strtolower(trim($row['type'])) : 'main';
        if($rType == 'sweet16') $s16_paid_count++;
        else $main_paid_count++;
    }
    
    // Main Pot
    $main_total = $main_paid_count * $cost;
    $main_house = ($meta['cutType'] == 1) ? ($main_total * ($cut/100)) : $cut;
    $main_pot = $main_total - $main_house;
    
    // Sweet 16 Pot
    $s16_total = $s16_paid_count * ($meta['sweet16_cost'] ?? 0);
    $s16_house = (($meta['sweet16_cutType'] ?? 0) == 1) ? ($s16_total * (($meta['sweet16_cut'] ?? 0)/100)) : ($meta['sweet16_cut'] ?? 0);
    $s16_pot = $s16_total - $s16_house;
    
    // FILTER logic for Display Lists
    $view = isset($_GET['view']) ? $_GET['view'] : 'main'; // main or sweet16
    
    $unpaid_rows = [];
    $paid_rows = [];
    $exempt_rows = [];
    
    // Filter Helper
    function filterRows($rows, $targetType) {
        $out = [];
        foreach($rows as $r) {
            $rType = isset($r['type']) ? strtolower(trim($r['type'])) : 'main';
            if($rType == 'sweet16' && $targetType == 'sweet16') $out[] = $r;
            else if($rType != 'sweet16' && $targetType == 'main') $out[] = $r;
        }
        return $out;
    }
    
    $unpaid_rows = filterRows($unpaid_all, $view);
    $paid_rows = filterRows($paid_all, $view);
    $exempt_rows = filterRows($exempt_all, $view);
?>
 
    <style>
    .payment-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        width: 100%;
    }
    @media (min-width: 900px) {
        .payment-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.2);
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.2);
        border-radius: 3px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(255,255,255,0.3);
    }
    
    /* Updated Pot Card Style */
    .pot-card {
        background: var(--secondary-blue); 
        border: 1px solid var(--border-color); 
        border-radius: 12px; 
        padding: 20px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        margin-bottom: 30px;
        flex: 1;
        text-align: center;
    }
    .pot-val { font-size: 2em; font-weight: bold; color: #22c55e; margin: 10px 0; }
    .pot-label { color: var(--text-muted); font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; }
    .pot-detail { font-size: 0.85em; color: var(--text-muted); margin-top:5px; }
</style>

<div id="main" class="full">
    <div class="content-card" style="width:98%; margin:0 auto; padding-top:20px;">
    
    <div style="border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:30px; text-align:center;">
        <h2 style="color: var(--accent-orange); margin:0;"><i class="fa-solid fa-money-bill-wave"></i> Payment Status</h2>
        <p style="color: var(--text-muted); margin-top:5px;">Track who's in and who still needs to pay up!</p>
    </div>

    <!-- Pot Size Cards -->
    <div style="display:flex; flex-wrap:wrap; gap:20px; justify-content:center; margin-bottom:30px;">
        <?php if($view == 'main') { ?>
        <!-- Main Pot -->
        <div class="pot-card" style="border-color: var(--primary-blue);">
            <div class="pot-label">Main Tournament Pot</div>
            <div class="pot-val">$<?php echo number_format($main_pot, 2); ?></div>
            <div class="pot-detail">
                <?php echo $main_paid_count; ?> entries @ $<?php echo $meta['cost']; ?>
                <?php if($main_house > 0) echo " (-$".number_format($main_house,2)." house)"; ?>
            </div>
        </div>
        <?php } ?>

        <?php if($view == 'sweet16' && (!empty($meta['sweet16Competition']) || !empty($meta['sweet16_cost']))) { ?>
        <!-- Second Chance Pot -->
        <div class="pot-card" style="border-color: var(--accent-orange);">
             <div class="pot-label" style="color: var(--accent-orange);">Second Chance (Round of 16)</div>
            <div class="pot-val">$<?php echo number_format($s16_pot, 2); ?></div>
            <div class="pot-detail">
                <?php echo $s16_paid_count; ?> entries @ $<?php echo $meta['sweet16_cost']; ?>
                <?php if($s16_house > 0) echo " (-$".number_format($s16_house,2)." house)"; ?>
            </div>
        </div>
        <?php } ?>
    </div>


    <!-- Payment Info & QR Codes -->
    <div style="display:flex; flex-wrap:wrap; gap:30px; justify-content:center; margin-bottom:40px;">
        
        <?php if($view == 'main' && !empty($meta['qr_code_type'])) { ?>
        <div style="flex:1; min-width:300px; max-width: 500px; background: rgba(30, 41, 59, 0.4); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; text-align: center;">
            <h3 style="margin-top: 0; color: #fff; margin-bottom: 20px;">Main Entry Fee: <span style="color: #22c55e;">$<?php echo $meta['cost']; ?></span></h3>
            <div style="background: white; padding: 10px; border-radius: 8px; display: inline-block; margin-bottom: 15px;">
                <img src='view_image.php?type=main&t=<?php echo time(); ?>' style='width: 180px; height: 180px; object-fit: contain; display: block;'>
            </div>
            <p style="margin: 0; color:var(--text-muted); font-size:0.9em;">Scan for Main Tournament</p>
        </div>
        <?php } ?>

        <?php if($view == 'sweet16' && !empty($meta['sweet16Competition']) && !empty($meta['sweet16_qr_type'])) { ?>
        <div style="flex:1; min-width:300px; max-width: 500px; background: rgba(30, 41, 59, 0.4); border: 1px solid var(--accent-orange); border-radius: 12px; padding: 25px; text-align: center;">
            <h3 style="margin-top: 0; color: #fff; margin-bottom: 20px;">Second Chance Fee: <span style="color: var(--accent-orange);">$<?php echo $meta['sweet16_cost']; ?></span></h3>
            <div style="background: white; padding: 10px; border-radius: 8px; display: inline-block; margin-bottom: 15px;">
                <img src='view_image.php?type=sweet16&t=<?php echo time(); ?>' style='width: 180px; height: 180px; object-fit: contain; display: block;'>
            </div>
            <p style="margin: 0; color:var(--text-muted); font-size:0.9em;">Scan for Second Chance</p>
        </div>
        <?php } ?>
        
    </div>
    


    <!-- TOGGLE TABS -->
    <?php if(!empty($meta['sweet16Competition'])) { ?>
    <div style="display:flex; justify-content:center; margin-bottom:25px;">
        <div style="background:rgba(255,255,255,0.05); padding:5px; border-radius:30px; display:flex; gap:5px;">
            <a href="?view=main" style="padding:8px 20px; border-radius:25px; text-decoration:none; font-weight:bold; transition:all 0.2s; <?php echo $view=='main' ? 'background:var(--accent-orange); color:white;' : 'color:var(--text-muted); hover:text-white'; ?>">Main Tournament</a>
            <a href="?view=sweet16" style="padding:8px 20px; border-radius:25px; text-decoration:none; font-weight:bold; transition:all 0.2s; <?php echo $view=='sweet16' ? 'background:var(--accent-orange); color:white;' : 'color:var(--text-muted); hover:text-white'; ?>">Second Chance</a>
        </div>
    </div>
    <?php } ?>

    <!-- Responsive Grid Layout -->
    <div class="payment-grid">
        
        <!-- Unpaid Column -->
        <div style="min-width: 0;">
            <?php renderPaymentCard($unpaid_rows, "Unpaid", "<i class='fa-solid fa-hourglass-half'></i>", "red"); ?>
        </div>
        
        <!-- Paid Column -->
        <div style="min-width: 0;">
            <?php renderPaymentCard($paid_rows, "Paid", "<i class='fa-solid fa-check'></i>", "green", true); ?>
        </div>
        
        <!-- Exempt Column -->
        <div style="min-width: 0;">
            <?php renderPaymentCard($exempt_rows, "Exempt", "<i class='fa-solid fa-shield-halved'></i>", "blue"); ?>
        </div>
        
    </div>

</div>

</body>
</html>

