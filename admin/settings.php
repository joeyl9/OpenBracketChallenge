<?php
include("database.php");
include("functions.php");
validatecookie();

// Check for admin
check_admin_auth('super');

$msg = "";
$error = "";

// Save Settings
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $cost = $_POST['cost'];
    $cut = $_POST['cut'];
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;


            
    $use_live = isset($_POST['use_live_scoring']) ? 1 : 0;
    $update_check = isset($_POST['update_check_enabled']) ? 1 : 0;
    $p1 = $_POST['payout_1'];
    $p2 = $_POST['payout_2'];
    $p3 = $_POST['payout_3'];
    $refundLast = isset($_POST['refund_last']) ? 1 : 0;
    $cutType = $_POST['cutType'];
    
    // New Reg Controls
    $reg_mode = $_POST['reg_mode'];
    $reg_password = $_POST['reg_password'];
    $reg_token = $_POST['reg_token'];
    
    // Auto-generate token if in token mode but empty
    if($reg_mode == 2 && empty($reg_token)) {
        $reg_token = bin2hex(random_bytes(16));
    }

    // Handle QR Upload (DB Storage)
    $qr_data = null;
    $qr_mime = null;
    if(isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] == 0) {
        $check = getimagesize($_FILES["qr_code"]["tmp_name"]);
        if($check !== false) {
             $qr_data = file_get_contents($_FILES["qr_code"]["tmp_name"]);
             // Secure MIME check
             $finfo = finfo_open(FILEINFO_MIME_TYPE);
             $qr_mime = finfo_file($finfo, $_FILES["qr_code"]["tmp_name"]);
             finfo_close($finfo);
             
             // Validate allowed types
             if(!in_array($qr_mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                 $error .= " Invalid file type: " . $qr_mime;
                 $qr_data = null;
             } else {
                 $msg .= " QR Code uploaded.";
             }
        } else {
            $error .= " Main QR File is not an image.";
        }
    }

    // Second Chance Params
    $sweet16 = isset($_POST['sweet16_competition']) ? 1 : 0;
    $s16_closed = isset($_POST['sweet16_closed']) ? 1 : 0;
    $s16_deadline = !empty($_POST['sweet16_deadline']) ? $_POST['sweet16_deadline'] : null;
    $s16_cost = $_POST['sweet16_cost'];
    $s16_cut = $_POST['sweet16_cut'];
    $s16_cutType = $_POST['sweet16_cutType'];
    $s16_p1 = $_POST['sweet16_payout_1'];
    $s16_p2 = $_POST['sweet16_payout_2'];
    $s16_p3 = $_POST['sweet16_payout_3'];
    $s16_refund = isset($_POST['sweet16_refund_last']) ? 1 : 0;
    
    $s16_reg_mode = $_POST['sweet16_reg_mode'];
    $s16_reg_pass = $_POST['sweet16_reg_password'];
    $s16_reg_token = $_POST['sweet16_reg_token'];

    // Auto-generate S16 token
    if($s16_reg_mode == 2 && empty($s16_reg_token)) {
        $s16_reg_token = bin2hex(random_bytes(16));
    }
    
    // Handle Second Chance QR Upload (DB Storage)
    $s16_qr_data = null;
    $s16_qr_mime = null;
    if(isset($_FILES['sweet16_qr_code']) && $_FILES['sweet16_qr_code']['error'] == 0) {
        $check = getimagesize($_FILES["sweet16_qr_code"]["tmp_name"]);
        if($check !== false) {
             $s16_qr_data = file_get_contents($_FILES["sweet16_qr_code"]["tmp_name"]);
             $finfo = finfo_open(FILEINFO_MIME_TYPE);
             $s16_qr_mime = finfo_file($finfo, $_FILES["sweet16_qr_code"]["tmp_name"]);
             finfo_close($finfo);
             if(!in_array($s16_qr_mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                 $error .= " Invalid file type for Second Chance QR: " . $s16_qr_mime;
                 $s16_qr_data = null;
             } else {
                 $msg .= " Second Chance QR uploaded.";
             }
        } else {
             $error .= " Second Chance QR File is not an image.";
        }
    }

    // Dynamic SQL Construction
    $sql = "UPDATE meta SET 
            title = ?, 
            sweet16Competition = ?,
            subtitle = ?, 
            cost = ?, 
            cut = ?,
            cutType = ?,
            deadline = ?,
            use_live_scoring = ?,
            update_check_enabled = ?,
            payout_1 = ?,
            payout_2 = ?,
            payout_3 = ?,
            reg_mode = ?,
            reg_password = ?,
            reg_token = ?,
            refund_last = ?,
            max_brackets = ?,
            sweet16_closed = ?,
            sweet16_deadline = ?,
            sweet16_cost = ?,
            sweet16_cut = ?,
            sweet16_cutType = ?,
            sweet16_payout_1 = ?,
            sweet16_payout_2 = ?,
            sweet16_payout_3 = ?,
            sweet16_reg_mode = ?,
            sweet16_reg_password = ?,
            sweet16_reg_token = ?,
            sweet16_refund_last = ?,
            max_sweet16_brackets = ?";

    $params = [
        $title, $sweet16, $subtitle, $cost, $cut, $cutType, $deadline,
        $use_live, $update_check, $p1, $p2, $p3, $reg_mode, $reg_password, $reg_token, $refundLast, $_POST['max_brackets'],
        $s16_closed, $s16_deadline, $s16_cost, $s16_cut, $s16_cutType, $s16_p1, $s16_p2, $s16_p3,
        $s16_reg_mode, $s16_reg_pass, $s16_reg_token, $s16_refund, $_POST['max_sweet16_brackets']
    ];

    if($qr_data !== null) {
        $sql .= ", qr_code_data = ?, qr_code_type = ?";
        $params[] = $qr_data;
        $params[] = $qr_mime;
    }
    if($s16_qr_data !== null) {
        $sql .= ", sweet16_qr_data = ?, sweet16_qr_type = ?";
        $params[] = $s16_qr_data;
        $params[] = $s16_qr_mime;
    }

    $sql .= " WHERE id = 1";
            
    $stmt = $db->prepare($sql);
    if($stmt->execute($params)) {
        $msg = "Settings updated successfully! " . $msg;
    } else {
        $error = "Failed to update settings.";
    }
}
?>

<?php include("header.php"); ?>

<!-- Flatpickr Assets (Local) -->
<link rel="stylesheet" href="../css/flatpickr.min.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/flatpickr-dark.css?v=<?php echo time(); ?>">
<script src="../js/flatpickr.min.js?v=<?php echo time(); ?>"></script>

<!-- Custom Overrides -->
<style>
/* Flatpickr Theme Overrides to match Admin Theme */
.flatpickr-calendar {
    background: var(--primary-blue, #111) !important;
    border: 1px solid var(--border-color, #444) !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5) !important;
}
/* Main Headers (Month/Year & Weekdays) */
.flatpickr-months .flatpickr-month, 
.flatpickr-weekdays, 
.flatpickr-weekday,
.flatpickr-current-month .flatpickr-monthDropdown-months {
    background: var(--primary-blue, #111) !important;
    color: var(--text-light, #fff) !important;
    fill: var(--text-light, #fff) !important;
}
.flatpickr-day {
    color: var(--text-light, #fff) !important;
}
.flatpickr-day.prevMonthDay, 
.flatpickr-day.nextMonthDay {
    color: var(--text-muted, #ccc) !important;
    opacity: 0.5;
}
.flatpickr-day:hover, 
.flatpickr-day:focus {
    background: rgba(255,255,255,0.1) !important;
    border-color: rgba(255,255,255,0.1) !important;
    color: var(--text-light, #fff) !important;
}
.flatpickr-day.selected, 
.flatpickr-day.startRange, 
.flatpickr-day.endRange, 
.flatpickr-day.selected:hover,
.flatpickr-day.selected:focus {
    background: var(--accent-orange, #f59e0b) !important;
    border-color: var(--accent-orange, #f59e0b) !important;
    color: var(--accent-text, #fff) !important;
}
.flatpickr-time {
    background: var(--primary-blue, #111) !important; 
    border-top: 1px solid var(--border-color, #444) !important;
}
.flatpickr-time input:hover, 
.flatpickr-time .flatpickr-am-pm:hover, 
.flatpickr-time input:focus, 
.flatpickr-time .flatpickr-am-pm:focus {
    background: rgba(255,255,255,0.1) !important;
    color: var(--text-light, #fff) !important;
}
.flatpickr-time .numInputWrapper span.arrowUp:after {
    border-bottom-color: var(--text-light, #fff) !important;
}
.flatpickr-time .numInputWrapper span.arrowDown:after {
    border-top-color: var(--text-light, #fff) !important;
}
/* Ensure today matches theme if not selected */
.flatpickr-day.today {
    border-color: var(--accent-orange, #f59e0b) !important;
    color: var(--text-light, #fff) !important; /* Ensure readable */
}
.flatpickr-day.today:hover {
    background: rgba(255,255,255,0.1) !important;
    color: var(--text-light, #fff) !important;
}
/* Dropdown arrow fix */
.flatpickr-current-month .flatpickr-monthDropdown-months .flatpickr-monthDropdown-month {
    background-color: var(--primary-blue, #111) !important;
    color: var(--text-light) !important;
}
/* Navigation Arrows - Month */
.flatpickr-months .flatpickr-prev-month svg, 
.flatpickr-months .flatpickr-next-month svg {
    fill: var(--text-light, #fff) !important;
}
.flatpickr-months .flatpickr-prev-month:hover svg, 
.flatpickr-months .flatpickr-next-month:hover svg {
    fill: var(--accent-orange, #f59e0b) !important;
}
/* Time Separator */
.flatpickr-time .flatpickr-time-separator {
    color: var(--text-light, #fff) !important;
}
</style>

<div id="main">
    <div class="full">
        <h2>Tournament Settings</h2>
        <div style="margin-bottom: 20px;">
            <a href="index.php" class="btn-outline">&larr; Back to Dashboard</a>
        </div>
        
        <?php if($msg) echo "<div class='success'>$msg</div>"; ?>
        <?php if($error) echo "<div class='error'>$error</div>"; ?>
        
        <form method="post" action="settings.php" enctype="multipart/form-data">
        <?php csrf_field(); ?>
        <style>
            .settings-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 20px;
            }
            @media (min-width: 768px) {
                .settings-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            .form-group {
                background: rgba(255,255,255,0.05);
                padding: 15px;
                border-radius: 6px;
                border: 1px solid var(--border-color);
            }
            .form-group label {
                display: block;
                color: var(--text-light);
                margin-bottom: 8px;
                font-weight: bold;
            }
            .form-group input[type="text"], 
            .form-group input[type="number"], 
            .form-group input[type="file"],
            .form-group input[type="datetime-local"] {
                width: 100%;
                padding: 10px;
                border-radius: 4px;
                border: 1px solid #444;
                background: #111;
                color: white;
                box-sizing: border-box;
            }
            .section-header {
                grid-column: 1 / -1; 
                margin-top:20px; 
                border-top:1px solid #444; 
                padding-top:20px;
                color: var(--accent-orange);
            }
            .helper-text {
                color: #aaa; /* Fixed light color instead of slate text-muted */
                font-size: 0.9em;
                margin-top: 5px;
                display: block;
            }
        </style>

        <div class="settings-grid">
            
            <!-- Basic Info -->
            <div class="form-group">
                <label>Site Title:</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($meta['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Site Subtitle:</label>
                <input type="text" name="subtitle" value="<?php echo htmlspecialchars($meta['subtitle']); ?>">
            </div>
            
            <div class="form-group">
                <label>Main Entry Fee ($):</label>
                <input type="text" name="cost" value="<?php echo (float)$meta['cost']; ?>" required>
            </div>

            <div class="form-group">
                <label>Main Pot Cut:</label>
                <div style="display:flex; align-items:center; gap:10px;">
                    <input type="text" name="cut" value="<?php echo (float)$meta['cut']; ?>" required style="max-width:100px;">
                    <label style="display:inline; margin:0; font-weight:normal;">
                        <input type="radio" name="cutType" value="0" <?php if($meta['cutType'] == 0) echo 'checked'; ?>> $
                    </label>
                    <label style="display:inline; margin:0; font-weight:normal;">
                        <input type="radio" name="cutType" value="1" <?php if($meta['cutType'] == 1) echo 'checked'; ?>> %
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Main Deadline:</label>
                <?php 
                    // Format for flatpickr: YYYY-MM-DD HH:MM:SS
                    $dlVal = $meta['deadline'] ? date('Y-m-d H:i:S', strtotime($meta['deadline'])) : '';
                ?>
                <input type="text" class="flatpickr" name="deadline" value="<?php echo $dlVal; ?>" placeholder="Select Date & Time...">
                <small class="helper-text">Leave blank for open-ended.</small>
            </div>
            
            <!-- Submission Limits -->
            <div class="form-group">
                <label>Max Brackets Per User (Main):</label>
                <input type="number" name="max_brackets" value="<?php echo (int)($meta['max_brackets'] ?? 1); ?>" min="1" required>
            </div>

            <!-- Registration Settings (Main) -->
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Main Tournament Registration:</label>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <label style="font-weight:normal; cursor:pointer;">
                        <input type="radio" name="reg_mode" value="0" <?php if($meta['reg_mode'] == 0) echo 'checked'; ?> onclick="toggleRegOptions(0)">
                        <strong>Open:</strong> Anyone can register.
                    </label>
                    <label style="font-weight:normal; cursor:pointer;">
                        <input type="radio" name="reg_mode" value="1" <?php if($meta['reg_mode'] == 1) echo 'checked'; ?> onclick="toggleRegOptions(1)">
                        <strong>Password Restricted:</strong> Must enter a specific password to register.
                    </label>
                    <div id="reg_pass_input" style="margin-left:25px; display:<?php echo ($meta['reg_mode'] == 1) ? 'block' : 'none'; ?>;">
                        <input type="text" name="reg_password" value="<?php echo htmlspecialchars($meta['reg_password']); ?>" placeholder="Set Registration Password...">
                    </div>
                    
                    <label style="font-weight:normal; cursor:pointer;">
                        <input type="radio" name="reg_mode" value="2" <?php if($meta['reg_mode'] == 2) echo 'checked'; ?> onclick="toggleRegOptions(2)">
                        <strong>Link Only:</strong> Users need a unique URL to access the form.
                    </label>
                    <div id="reg_token_input" style="margin-left:25px; display:<?php echo ($meta['reg_mode'] == 2) ? 'block' : 'none'; ?>;">
                        <div style="display:flex; gap:10px;">
                            <input type="text" name="reg_token" id="token_field" value="<?php echo htmlspecialchars($meta['reg_token']); ?>" placeholder="Token">
                            <button type="button" onclick="generateToken()" class="btn-sm">Generate</button>
                            <button type="button" onclick="copyRegLink()" class="btn-sm btn-accent">Copy Link</button>
                        </div>
                        <small id="reg_link_display" style="color:var(--accent-orange); word-break:break-all;"></small>
                    </div>
                </div>
            </div>
            
            <!-- Payment QR -->
            <!-- Payment QR -->
            <div class="form-group">
                <label>Main Payment QR Code:</label>
                
                <!-- Hidden Input -->
                <input type="file" name="qr_code" id="qr_code_input" accept="image/*" style="display:none;" onchange="updateFileName('qr_code_input', 'qr_file_name', 'qr_preview_main')">
                
                <!-- Styled Button -->
                <div style="display:flex; align-items:center; gap:15px; margin-bottom:10px;">
                    <label for="qr_code_input" class="btn-sm" style="display:inline-block; padding:8px 16px; background:#f97316; color:white; border-radius:4px; cursor:pointer; text-align:center;">
                        Choose Image
                    </label>
                    <span id="qr_file_name" style="color:#aaa; font-style:italic;">No file selected</span>
                </div>

                <div style="margin-top:10px; text-align:center;">
                    <img id="qr_preview_main" src="../view_image.php?type=main&t=<?php echo time(); ?>" style="height:150px; border:1px solid #444; border-radius:4px; max-width:100%; object-fit:contain;" onerror="this.style.display='none'">
                </div>
            </div>

            <!-- Payouts -->
            <div class="form-group">
                <label>Payout Structure (%):</label>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <div style="flex:1;">
                        <span style="font-size:0.8em; display:block;">1st Place</span>
                        <input type="text" name="payout_1" value="<?php echo (int)($meta['payout_1'] ?? 60); ?>">
                    </div>
                    <div style="flex:1;">
                        <span style="font-size:0.8em; display:block;">2nd Place</span>
                        <input type="text" name="payout_2" value="<?php echo (int)($meta['payout_2'] ?? 30); ?>">
                    </div>
                    <div style="flex:1;">
                        <span style="font-size:0.8em; display:block;">3rd Place</span>
                        <input type="text" name="payout_3" value="<?php echo (int)($meta['payout_3'] ?? 10); ?>">
                    </div>
                </div>
                <div style="margin-top:10px; border-top:1px solid #444; padding-top:10px;">
                    <label style="font-weight:normal; cursor:pointer; display:flex; align-items:center;">
                        <input type="checkbox" name="refund_last" value="1" <?php if(!empty($meta['refund_last'])) echo "checked"; ?> style="width:auto; margin-right:10px;">
                        Refund Last Place (taken from pot first)
                    </label>
                </div>
            </div>

            <!-- Second Chance Settings -->
            <h3 class="section-header">Second Chance Tournament</h3>
            
            <div class="form-group" style="grid-column: 1 / -1;">
                 <label>Enable Second Chance Mode:</label>
                 <div style="background: rgba(249, 115, 22, 0.1); padding: 10px; border: 1px solid #f97316; border-radius: 4px;">
                    <label style="font-weight:normal; display:flex; align-items:center; cursor:pointer; color: var(--text-light); margin:0;">
                        <input type="checkbox" id="sweet16_toggle" name="sweet16_competition" value="1" <?php if(!empty($meta['sweet16Competition'])) echo "checked"; ?> style="width:auto; margin-right:10px;" onclick="toggleSweet16Section()">
                        <strong>Enable Second Chance Mode</strong>
                    </label>
                    <small class="helper-text">
                        Allows users to submit new brackets starting from the Sweet 16. Requires Games 1-48 to be decided.
                    </small>
                    <label style="font-weight:normal; display:flex; align-items:center; cursor:pointer; color: #ef4444; margin-top:10px;">
                        <input type="checkbox" name="sweet16_closed" value="1" <?php if(!empty($meta['sweet16_closed'])) echo "checked"; ?> style="width:auto; margin-right:10px;">
                        <strong>Close Second Chance Submissions</strong>
                    </label>
                </div>
            </div>

            <!-- Hidden Container for Second Chance Settings -->
            <div id="sweet16_settings_container" style="display:contents;">
                
                <div class="form-group">
                    <label>Second Chance Entry Fee ($):</label>
                    <input type="text" name="sweet16_cost" value="<?php echo (float)($meta['sweet16_cost'] ?? 0); ?>">
                </div>

                <div class="form-group">
                    <label>Second Chance Pot Cut:</label>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="text" name="sweet16_cut" value="<?php echo (float)($meta['sweet16_cut'] ?? 0); ?>" style="max-width:100px;">
                        <label style="display:inline; margin:0; font-weight:normal;">
                            <input type="radio" name="sweet16_cutType" value="0" <?php if(($meta['sweet16_cutType'] ?? 0) == 0) echo 'checked'; ?>> $
                        </label>
                        <label style="display:inline; margin:0; font-weight:normal;">
                            <input type="radio" name="sweet16_cutType" value="1" <?php if(($meta['sweet16_cutType'] ?? 0) == 1) echo 'checked'; ?>> %
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Second Chance Deadline:</label>
                    <?php 
                        $s16dlVal = $meta['sweet16_deadline'] ? date('Y-m-d H:i:S', strtotime($meta['sweet16_deadline'])) : '';
                    ?>
                    <input type="text" class="flatpickr" name="sweet16_deadline" value="<?php echo $s16dlVal; ?>" placeholder="Select Date & Time...">
                </div>
                
                <div class="form-group">
                    <label>Max Second Chance Brackets Per User:</label>
                    <!-- Verify 'max_sweet16_brackets' exists in meta or default to 1 -->
                    <input type="number" name="max_sweet16_brackets" value="<?php echo (int)($meta['max_sweet16_brackets'] ?? 1); ?>" min="1">
                </div>

                <div class="form-group">
                    <label>Second Chance Payment QR Code:</label>
                    <!-- Hidden Input -->
                    <input type="file" name="sweet16_qr_code" id="s16_qr_input" accept="image/*" style="display:none;" onchange="updateFileName('s16_qr_input', 's16_file_name', 'qr_preview_s16')">
                    
                    <!-- Styled Button -->
                    <div style="display:flex; align-items:center; gap:15px; margin-bottom:10px;">
                        <label for="s16_qr_input" class="btn-sm" style="display:inline-block; padding:8px 16px; background:#f97316; color:white; border-radius:4px; cursor:pointer; text-align:center;">
                            Choose Image
                        </label>
                        <span id="s16_file_name" style="color:#aaa; font-style:italic;">No file selected</span>
                    </div>

                    <div style="margin-top:10px; text-align:center;">
                        <img id="qr_preview_s16" src="../view_image.php?type=sweet16&t=<?php echo time(); ?>" style="height:150px; border:1px solid #444; border-radius:4px; max-width:100%; object-fit:contain;" onerror="this.style.display='none'">
                    </div>
                </div>

                <div class="form-group">
                    <label>Second Chance Payout Structure (%):</label>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <div style="flex:1;">
                            <span style="font-size:0.8em; display:block;">1st</span>
                            <input type="text" name="sweet16_payout_1" value="<?php echo (int)($meta['sweet16_payout_1'] ?? 60); ?>">
                        </div>
                        <div style="flex:1;">
                            <span style="font-size:0.8em; display:block;">2nd</span>
                            <input type="text" name="sweet16_payout_2" value="<?php echo (int)($meta['sweet16_payout_2'] ?? 30); ?>">
                        </div>
                        <div style="flex:1;">
                            <span style="font-size:0.8em; display:block;">3rd</span>
                            <input type="text" name="sweet16_payout_3" value="<?php echo (int)($meta['sweet16_payout_3'] ?? 10); ?>">
                        </div>
                    </div>
                     <div style="margin-top:10px; border-top:1px solid #444; padding-top:10px;">
                        <label style="font-weight:normal; cursor:pointer; display:flex; align-items:center;">
                            <input type="checkbox" name="sweet16_refund_last" value="1" <?php if(!empty($meta['sweet16_refund_last'])) echo "checked"; ?> style="width:auto; margin-right:10px;">
                            Refund Last (Second Chance)
                        </label>
                    </div>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Second Chance Registration Access:</label>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <label style="font-weight:normal; cursor:pointer;">
                            <input type="radio" name="sweet16_reg_mode" value="0" <?php if(($meta['sweet16_reg_mode'] ?? 0) == 0) echo 'checked'; ?> onclick="toggleS16RegOptions(0)">
                            <strong>Open</strong>
                        </label>
                        <label style="font-weight:normal; cursor:pointer;">
                            <input type="radio" name="sweet16_reg_mode" value="1" <?php if(($meta['sweet16_reg_mode'] ?? 0) == 1) echo 'checked'; ?> onclick="toggleS16RegOptions(1)">
                            <strong>Password</strong>
                        </label>
                        <div id="sweet16_reg_pass_input" style="margin-left:25px; display:<?php echo (($meta['sweet16_reg_mode'] ?? 0) == 1) ? 'block' : 'none'; ?>;">
                            <input type="text" name="sweet16_reg_password" value="<?php echo htmlspecialchars($meta['sweet16_reg_password'] ?? ''); ?>" placeholder="Second Chance Password...">
                        </div>
                        
                        <label style="font-weight:normal; cursor:pointer;">
                            <input type="radio" name="sweet16_reg_mode" value="2" <?php if(($meta['sweet16_reg_mode'] ?? 0) == 2) echo 'checked'; ?> onclick="toggleS16RegOptions(2)">
                            <strong>Link Only</strong>
                        </label>
                        <div id="sweet16_reg_token_input" style="margin-left:25px; display:<?php echo (($meta['sweet16_reg_mode'] ?? 0) == 2) ? 'block' : 'none'; ?>;">
                            <div style="display:flex; gap:10px;">
                                <input type="text" name="sweet16_reg_token" id="sweet16_token_field" value="<?php echo htmlspecialchars($meta['sweet16_reg_token'] ?? ''); ?>" placeholder="Token">
                                <button type="button" onclick="generateS16Token()" class="btn-sm">Generate</button>
                                 <button type="button" onclick="copyS16RegLink()" class="btn-sm btn-accent">Copy Link</button>
                            </div>
                             <small id="s16_reg_link_display" style="color:var(--accent-orange); word-break:break-all;"></small>
                        </div>
                    </div>
                </div>

            </div> <!-- End Second Chance Container -->

            <!-- Misc -->
            <h3 class="section-header">Advanced Settings</h3>

             <div class="form-group" style="grid-column: 1 / -1;">
                <label>Live Scoring:</label>
                <div style="background: rgba(56, 189, 248, 0.1); padding: 10px; border: 1px solid #0369a1; border-radius: 4px;">
                    <label style="font-weight:normal; display:flex; align-items:center; cursor:pointer; color: var(--text-light); margin:0;">
                        <input type="checkbox" name="use_live_scoring" value="1" <?php if(!empty($meta['use_live_scoring'])) echo "checked"; ?> style="width:auto; margin-right:10px;">
                        Enable Auto-Fetch & Update Scores (External Feed)
                    </label>
                </div>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Updates:</label>
                <div style="background: rgba(249, 115, 22, 0.1); padding: 10px; border: 1px solid var(--accent-orange); border-radius: 4px;">
                    <label style="font-weight:normal; display:flex; align-items:center; cursor:pointer; color: var(--text-light); margin:0;">
                        <input type="checkbox" name="update_check_enabled" value="1" <?php if(!empty($meta['update_check_enabled'])) echo "checked"; ?> style="width:auto; margin-right:10px;">
                        Check for Updates
                    </label>
                    <small class="helper-text">
                        Off by default. When enabled, the admin dashboard will check GitHub to see if a newer version of the project is available.
                    </small>
                </div>
            </div>

        </div> <!-- End Grid -->

        <div style="margin-top:30px; text-align:center;">
            <input type="submit" value="Save Settings" class="btn" style="padding:15px 40px; font-size:1.2rem; cursor:pointer;">
        </div>
        
        </form>
    </div>
</div>

<script>
    // Init Flatpickr
    flatpickr(".flatpickr", {
        enableTime: true,
        dateFormat: "Y-m-d H:i:S",
        theme: "dark"
    });

    // Main Reg Logic
    function toggleRegOptions(mode) {
        document.getElementById('reg_pass_input').style.display = (mode == 1) ? 'block' : 'none';
        document.getElementById('reg_token_input').style.display = (mode == 2) ? 'block' : 'none';
    }
    
    function generateToken() {
        const array = new Uint8Array(16);
        window.crypto.getRandomValues(array);
        const token = Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
        document.getElementById('token_field').value = token;
        updateLinkDisplay(token);
    }

    function updateLinkDisplay(token) {
        if(!token) {
            document.getElementById('reg_link_display').innerText = "";
            return;
        }
        var url = window.location.origin + window.location.pathname.replace('admin/settings.php', 'submit.php?token=' + token);
        document.getElementById('reg_link_display').innerText = "Share this: " + url;
    }

    function copyRegLink() {
        var token = document.getElementById('token_field').value;
        if(!token) { alert('Generate or enter a token first.'); return; }
        
        var url = window.location.origin + window.location.pathname.replace('admin/settings.php', 'submit.php?token=' + token);
        navigator.clipboard.writeText(url).then(function() {
            alert('Link Copied!');
        });
    }

    // S16 Reg Logic
    function toggleS16RegOptions(mode) {
        document.getElementById('sweet16_reg_pass_input').style.display = (mode == 1) ? 'block' : 'none';
        document.getElementById('sweet16_reg_token_input').style.display = (mode == 2) ? 'block' : 'none';
    }
    function generateS16Token() {
        const array = new Uint8Array(16);
        window.crypto.getRandomValues(array);
        const token = Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
        document.getElementById('sweet16_token_field').value = token;
        updateS16LinkDisplay(token);
    }
     function updateS16LinkDisplay(token) {
        if(!token) {
            document.getElementById('s16_reg_link_display').innerText = "";
            return;
        }
        var url = window.location.origin + window.location.pathname.replace('admin/settings.php', 'submit_second_chance.php?token=' + token);
        document.getElementById('s16_reg_link_display').innerText = "Share this: " + url;
    }
    function copyS16RegLink() {
         var token = document.getElementById('sweet16_token_field').value;
        if(!token) { alert('Generate or enter a token first.'); return; }
         var url = window.location.origin + window.location.pathname.replace('admin/settings.php', 'submit_second_chance.php?token=' + token);
        navigator.clipboard.writeText(url).then(function() {
            alert('Link Copied!');
        });
    }
    
    // Toggle S16 Section
    function toggleSweet16Section() {
        var checked = document.getElementById('sweet16_toggle').checked;
        document.getElementById('sweet16_settings_container').style.display = checked ? 'contents' : 'none';
    }

    // Init Logic on Load
    if(document.getElementById('token_field').value) {
         updateLinkDisplay(document.getElementById('token_field').value);
    }
    if(document.getElementById('sweet16_token_field').value) {
         updateS16LinkDisplay(document.getElementById('sweet16_token_field').value);
    }
    toggleSweet16Section(); // Set initial state

    function updateFileName(inputId, spanId, imgId) {
        var input = document.getElementById(inputId);
        var span = document.getElementById(spanId);
        var img = document.getElementById(imgId);
        
        if (input.files && input.files.length > 0) {
            span.innerText = input.files[0].name;
            span.style.color = "var(--text-light)";
            
            // Preview
            var reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                img.style.display = 'inline-block';
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            span.innerText = "No file selected";
            span.style.color = "#aaa";
        }
    }
</script>
<?php include('footer.php'); ?>
</body>
</html>
