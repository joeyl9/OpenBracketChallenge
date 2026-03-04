<?php
include("admin/database.php");
include("admin/functions.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;


if (!$id && $user_id) {
    // Try to find ANY bracket for this user if none specified
    // Removed strict 'main' check to catch any submission
    $stmt = $db->prepare("SELECT id FROM brackets WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $res = $stmt->fetch();
    if($res) {
        $id = $res['id'];
        // Continue to load this id...
    } else {
        // No bracket found. Fall through to display the 'Create Profile' view.
    }
}

// Redirect guests only
if (!$id && !$user_id) {
    header("Location: index.php");
    exit;
}

// Handle Profile Update
if (isset($_POST['update_profile']) && $user_id) {
    verify_csrf_token();
    $bio = sanitizeInput($_POST['bio']);
    $fav = sanitizeInput($_POST['fav_team']);
    
    // Check ownership
    $stmt = $db->prepare("SELECT user_id FROM brackets WHERE id = ?");
    $stmt->execute([$id]);
    $check = $stmt->fetch();
    
    if ($check && $check['user_id'] == $user_id) {
        // Theme Update
        $themeSql = "";
        $params = [$bio, $fav];
        
        if(isset($_POST['theme'])) {
            $new_theme = sanitizeInput($_POST['theme']);
            $themes = getThemes();
            if(array_key_exists($new_theme, $themes)) {
                $themeSql = ", theme = ?";
                $params[] = $new_theme;
            }
        }
        
        $params[] = $user_id; // WHERE Clause (Targeting User now)
        
        // UPDATE USERS TABLE
        $up = $db->prepare("UPDATE users SET bio = ?, fav_team = ? $themeSql WHERE id = ?");
        $up->execute($params);
        
        // Sync Session/Cookie
        if(isset($new_theme)) {
            $_SESSION['theme'] = $new_theme;
            if(isset($_COOKIE['theme'])) setcookie("theme", $new_theme, time()+86400*30, "/");
        }
        
        $msg = "Profile updated!";
    }
}

// If still no ID (and we are logged in), show "Create Profile" state
if (!$id) {
    include("header.php");
    echo '<div id="main" class="full" style="padding:50px; text-align:center;">
            <div style="background:var(--secondary-blue); padding:40px; border-radius:12px; display:inline-block; border:1px solid var(--border-color); max-width:600px;">
                <i class="fa-solid fa-user-astronaut" style="font-size:4rem; color:var(--text-muted); margin-bottom:20px;"></i>
                <h2 style="color:var(--text-light);">Profile Not Found</h2>
                <p style="color:var(--text-muted); font-size:1.1rem;">You need to create a bracket to set up your profile, avatar, and themes.</p>
                <div style="margin-top:30px;">
                    <a href="submit.php" style="background:#22c55e; color:white; padding:12px 30px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:1.1rem;">Create My Bracket</a>
                </div>
            </div>
          </div>';
    include("footer.php");
    exit;
}

// Fetch Bracket Data
$stmt = $db->prepare("SELECT * FROM brackets WHERE id = ?");
$stmt->execute([$id]);
$bracket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bracket) {
    include("header.php");
    echo "<div id='main' class='full'><div class='error'>User not found.</div></div>";
    exit;
}

// Check if Owner (Session-only — cookies are forgeable)
$is_owner = ($user_id > 0 && $bracket['user_id'] == $user_id);
$meta = $db->query("SELECT * FROM meta WHERE id=1")->fetch();

// Handle Avatar Upload
if ($is_owner && isset($_POST['upload_avatar']) && isset($_FILES['avatar'])) {
    verify_csrf_token();
    if(!isset($msg)) $msg = "";
    $file = $_FILES['avatar'];
    
    // Check for successful upload (Error 0)
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            if($file['size'] < 5000000) { // 5MB
                 $filename = "avatars/" . $id . "_" . time() . "." . $ext;
                 if(!is_dir("avatars")) mkdir("avatars");
                 
                 // Read file content
                 $imgData = file_get_contents($file['tmp_name']);
                 $imgType = $file['type'];
                 // Validate real mime type
                 $finfo = new finfo(FILEINFO_MIME_TYPE);
                 $realMime = $finfo->file($file['tmp_name']);
                 
                 $validMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                 
                 if(in_array($realMime, $validMimes)) {
                     $src = imagecreatefromstring($imgData);
                     if ($src !== false) {
                         ob_start();
                         if ($realMime == 'image/jpeg') imagejpeg($src, null, 85);
                         elseif ($realMime == 'image/png') imagepng($src);
                         elseif ($realMime == 'image/gif') imagegif($src); // Note: Loses animation
                         elseif ($realMime == 'image/webp') imagewebp($src);
                         $imgData = ob_get_clean();
                         imagedestroy($src);

                         // 1. Update Users Table (BLOB)
                         $stmt = $db->prepare("UPDATE users SET avatar_data = ?, avatar_type = ? WHERE id = ?");
                         $stmt->execute([$imgData, $realMime, $user_id]);
                         
                         // 2. Update Brackets Table
                         $newUrl = "avatar.php?user_id=" . $user_id . "&v=" . time();
                         $stmt = $db->prepare("UPDATE brackets SET avatar_url = ? WHERE id = ?");
                         $stmt->execute([$newUrl, $id]);
                         
                         $bracket['avatar_url'] = $newUrl; 
                         $msg .= ($msg ? " " : "") . "Avatar updated (Securely re-encoded)!";
                     } else {
                         $msg .= ($msg ? " " : "") . "Image processing failed.";
                     }
                 } else {
                     $msg .= ($msg ? " " : "") . "Invalid file content (MIME mismatch).";
                 }
            } else {
                 $msg .= ($msg ? " " : "") . "File too large (Max 5MB).";
            }
        } else {
            $msg .= ($msg ? " " : "") . "Invalid format. Allowed: " . implode(", ", $allowed);
        }
    }
}

include("header.php");

// Fetch Badges
include_once("admin/badges.php");
$badges = [];
if(class_exists('BadgeManager')) {
    $bm = new BadgeManager($db);
    $badges = $bm->getBadges($id);
}

// Fetch Final Four Picks for Share Card
// Games: 61, 62 (Semis), 63 (Final)
// Fetch Final Four Picks for Share Card ($bracket is queried above)

// Champion is Game 63
$champ_pick = isset($bracket['63']) ? $bracket['63'] : '???';

// FETCH USER PROFILE DATA (Bio, Fav Team, Theme)
// We merge this into $bracket for display compatibility
$u_stmt = $db->prepare("SELECT bio, fav_team, theme FROM users WHERE id = ?");
$u_stmt->execute([$bracket['user_id']]);
$user_data = $u_stmt->fetch(PDO::FETCH_ASSOC);

if($user_data) {
    $bracket['bio'] = $user_data['bio'];
    $bracket['fav_team'] = $user_data['fav_team'];
    if(!empty($user_data['theme'])) {
        $bracket['theme'] = $user_data['theme'];
    }
}

// Final Four Participants are the winners of the Elite 8 games (57, 58, 59, 60)
// Game 61 is winner(57) vs winner(58)
// Game 62 is winner(59) vs winner(60)
$r4_picks = [];
$fff_games = [57, 58, 59, 60];
foreach($fff_games as $g) {
    $r4_picks[$g] = isset($bracket[(string)$g]) ? $bracket[(string)$g] : '???';
}

// Fetch Rank/Score
$scoreQ = $db->prepare("SELECT * FROM scores WHERE id = ? AND scoring_type='main'");
$scoreQ->execute([$id]);
$scoreData = $scoreQ->fetch();
$score = $scoreData['score'] ?? 0;
// Note: Rank calculation is resource-intensive for large datasets.
$rankQ = $db->prepare("SELECT count(*)+1 FROM scores WHERE scoring_type='main' AND score > ?");
$rankQ->execute([$score]);
$rank = $rankQ->fetchColumn();

// Avatar
$avatar = $bracket['avatar_url'] ? $bracket['avatar_url'] : 'avatar.php?name='.urlencode(strip_tags($bracket['name'])).'&background=random';
$param = urlencode(strip_tags($bracket['name']));
$err = "this.src='avatar.php?name=$param&background=random'";

?>

<div id="main" class="full">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 40px;
            padding: 40px;
            width: 100%;
            box-sizing: border-box;
        }
        @media (max-width: 768px) {
            .profile-container {
                display: flex;
                flex-direction: column;
            }
            .profile-card {
                width: 100% !important;
                max-width: 100% !important;
                flex: none !important;
            }
        }
    </style>
    <div class="profile-container">
        
        <!-- Sidebar: Card -->
        <div class="profile-card" id="hoops-card" style="background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:12px; overflow:hidden; position:relative;">
            <div style="background:linear-gradient(135deg, var(--accent-orange), #ea580c); height:100px;"></div>
                <div style="text-align:center; margin-top:-50px; padding-bottom:20px;">
                <div style="position:relative; display:inline-block;">
                    <img src="<?php echo htmlspecialchars($avatar); ?>" onerror="<?php echo htmlspecialchars($err, ENT_QUOTES); ?>" style="width:100px; height:100px; border-radius:50%; border:4px solid var(--bg-secondary); background:var(--bg-secondary); object-fit:cover;">
                    
                    <?php if($is_owner): ?>
                    <form method="post" enctype="multipart/form-data" id="avatarForm" style="display:none;">
                         <?php csrf_field(); ?>
                         <input type="file" name="avatar" id="avatarInput" onchange="document.getElementById('avatarForm').submit();">
                         <input type="hidden" name="upload_avatar" value="1">
                    </form>
                    <button onclick="document.getElementById('avatarInput').click()" style="position:absolute; bottom:0; right:0; background:var(--accent-orange); color:white; border:none; border-radius:50%; width:30px; height:30px; cursor:pointer; box-shadow:0 2px 5px rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center;" title="Change Avatar">
                        <i class="fa-solid fa-camera" style="font-size:0.8rem;"></i>
                    </button>
                    <?php endif; ?>
                </div>
                    <h2 style="margin:10px 0 5px 0;"><?php echo htmlspecialchars(stripslashes($bracket['name'])); ?></h2>
                    <div style="color:var(--text-muted); font-size:0.9rem;"><?php echo htmlspecialchars(stripslashes($bracket['person'])); ?></div>
                    
                    <?php if($bracket['fav_team']) { ?>
                     <div style="margin-top:10px; display:inline-block; background:rgba(255,255,255,0.1); padding:4px 10px; border-radius:15px; font-size:0.8rem;">
                        <i class="fa-solid fa-heart" style="color: #ef4444;"></i> <?php echo htmlspecialchars($bracket['fav_team']); ?>
                     </div>
                    <?php } ?>
                
                <div style="margin-top:20px; display:flex; justify-content:space-around; border-top:1px solid var(--border-color); padding-top:15px;">
                    <div>
                        <div style="font-size:1.2rem; font-weight:bold; color:var(--accent-orange);">#<?php echo $rank; ?></div>
                        <div style="font-size:0.8rem; color:var(--text-muted);">Rank</div>
                    </div>
                    <div>
                        <div style="font-size:1.2rem; font-weight:bold; color:#fff;"><?php echo $score; ?></div>
                        <div style="font-size:0.8rem; color:var(--text-muted);">Score</div>
                    </div>
                    <div>
                        <div style="font-size:1.2rem; font-weight:bold; color:#fff;"><?php echo count($badges); ?></div>
                        <div style="font-size:0.8rem; color:var(--text-muted);">Badges</div>
                    </div>
                </div>
            </div>
            
            <?php if($is_owner) { ?>
            <div style="padding:15px; border-top:1px solid var(--border-color);">
                <button onclick="document.getElementById('edit-profile').style.display='block'" style="width:100%; padding:8px; background:transparent !important; border:1px solid var(--border-color); color:var(--text-light) !important; border-radius:6px; cursor:pointer;"><i class="fa-solid fa-pen-to-square"></i> Edit Profile</button>
            </div>
            <?php } ?>
            
            <div style="padding:15px; text-align:center;">
                <button id="shareBtn" onclick="shareCard()" style="width:100%; padding:10px; background:var(--accent-orange); border:none; color:white; border-radius:6px; font-weight:bold; cursor:pointer;"><i class="fa-solid fa-share-from-square"></i> Share Hoops Card</button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="profile-content" style="background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:12px; padding:30px;">
            <!-- Bio -->
            <div class="section" style="margin-bottom:30px;">
                <h3 style="border-bottom:1px solid var(--border-color); padding-bottom:10px;">About</h3>
                <?php if($bracket['bio']) { ?>
                    <p style="color:var(--text-light); line-height:1.6;"><?php echo nl2br(htmlspecialchars($bracket['bio'])); ?></p>
                <?php } else { ?>
                    <p style="color:var(--text-muted); font-style:italic;">No bio yet. <?php echo $is_owner ? "Write something!" : "Mystery player."; ?></p>
                <?php } ?>
            </div>

            <div class="section">
                <h3 style="border-bottom:1px solid var(--border-color); padding-bottom:10px;"><i class="fa-solid fa-trophy"></i> Trophy Case</h3>
                <div style="display:flex; flex-wrap:wrap; gap:15px;">
                    <?php 
                    if(count($badges) > 0) {
                        foreach($badges as $b) {
                           $dateStr = (isset($b['awarded_at']) && $b['awarded_at'] && $b['awarded_at'] != '0000-00-00 00:00:00') ? date('M Y', strtotime($b['awarded_at'])) : '';
                           echo "<div style='background:rgba(255,255,255,0.05); padding:10px; border-radius:8px; display:flex; align-items:center; gap:10px; border:1px solid #444;' title='".h($b['description'])."'>
                                    <div style='font-size:1.5rem;'>".h($b['emoji'])."</div>
                                    <div>
                                        <div style='font-weight:bold; font-size:0.9rem;'>".h($b['name'])."</div>
                                        <div style='font-size:0.7rem; color:var(--text-muted);'>$dateStr</div>
                                    </div>
                                 </div>";
                        }
                    } else {
                        echo "<p style='color:var(--text-muted);'>No metal on the shelf... yet.</p>";
                    }
                    ?>
                </div>
            </div>
            
             <!-- Link to Bracket -->
             <div style="margin-top:40px;">
                <a href="view.php?id=<?php echo $id; ?>" style="display:inline-block; padding:10px 20px; background:var(--bg-secondary); border:1px solid var(--accent-orange); color:var(--accent-orange); text-decoration:none; border-radius:6px; font-weight:bold;">View Full Bracket <i class="fa-solid fa-arrow-right"></i></a>
             </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <?php if($is_owner) { ?>
    <div id="edit-profile" style="display:<?php echo (isset($_GET['edit']) ? 'block' : 'none'); ?>; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.8); z-index:999; backdrop-filter:blur(5px);">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:var(--bg-secondary); padding:30px; border-radius:12px; width:400px; border:1px solid var(--border-color); box-shadow:0 20px 50px rgba(0,0,0,0.5);">
            <h3 style="margin-top:0; color:var(--text-light);">Edit Profile</h3>
            <form method="post" enctype="multipart/form-data">
                <?php csrf_field(); ?>
                <input type="hidden" name="upload_avatar" value="1">
                
                <div style="margin-bottom:20px; text-align:center; border-bottom:1px solid var(--border-color); padding-bottom:20px;">
                    <div style="margin-bottom:10px;">
                        <img id="edit_avatar_preview" src="<?php echo $avatar; ?>" style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:2px solid var(--accent-orange);">
                    </div>
                    <label for="modal_avatar_input" style="color:var(--text-muted); cursor:pointer; background:rgba(255,255,255,0.1); padding:8px 15px; border-radius:4px; display:inline-block; font-size:0.9rem; transition:background 0.2s;">
                        <i class="fa-solid fa-camera"></i> Change Photo
                    </label>
                    <input type="file" name="avatar" id="modal_avatar_input" style="display:none;" onchange="if(this.files && this.files[0]) document.getElementById('edit_avatar_preview').src = window.URL.createObjectURL(this.files[0]);">
                </div>

                <div style="margin-bottom:15px;">
                    <label style="color:var(--text-muted); display:block; margin-bottom:5px;">Favorite Team</label>
                    <input type="text" name="fav_team" value="<?php echo htmlspecialchars($bracket['fav_team'] ?? ''); ?>" style="width:100%; padding:8px; border-radius:4px; border:1px solid var(--border-color); background:rgba(0,0,0,0.2); color:var(--text-light); outline:none;">
                </div>
                <div style="margin-bottom:20px;">
                    <label style="color:var(--text-muted); display:block; margin-bottom:5px;">Bio</label>
                    <textarea name="bio" rows="4" style="width:100%; padding:8px; border-radius:4px; border:1px solid var(--border-color); background:rgba(0,0,0,0.2); color:var(--text-light); outline:none;"><?php echo htmlspecialchars($bracket['bio'] ?? ''); ?></textarea>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="color:var(--text-muted); display:block; margin-bottom:10px;">Select Theme</label>
                    <input type="hidden" name="theme" id="selected_theme_input" value="<?php echo htmlspecialchars($bracket['theme'] ?? 'default'); ?>">
                    
                    <div id="theme-browser" style="max-height:300px; overflow-y:auto; border:1px solid var(--border-color); border-radius:6px; background:rgba(0,0,0,0.2); padding:10px;">
                        <?php 
                        $themes = getThemes();
                        $current_theme = $bracket['theme'] ?? 'default';
                        
                        // Grouping Logic
                        $grouped = [];
                        foreach($themes as $key => $t) {
                            $g = $t['group'] ?? 'Others';
                            $grouped[$g][] = array_merge($t, ['key' => $key]);
                        }
                        
                        // Render Groups
                        foreach($grouped as $groupName => $items) {
                            // System default is always open, others collapsed
                            $open = ($groupName == 'System') ? 'open' : '';
                            
                            // Check if current selection is in this group to auto-open
                            foreach($items as $i) {
                                if($i['key'] == $current_theme) $open = 'open';
                            }
                            
                            echo "<details $open style='margin-bottom:8px; border-bottom:1px solid var(--border-color); padding-bottom:8px;'>";
                            echo "<summary style='cursor:pointer; font-weight:bold; color:var(--text-light); padding:5px; list-style:none; display:flex; justify-content:space-between;'><span>$groupName</span> <span style='font-size:0.8em; color:var(--text-muted);'>&#9660;</span></summary>";
                            echo "<div style='display:grid; grid-template-columns:1fr; gap:5px; margin-top:5px;'>";
                            
                            foreach($items as $item) {
                                $k = $item['key'];
                                $n = $item['name'];
                                $acc = $item['accent'];
                                $bg = $item['bg1'];
                                
                                $isSelected = ($k == $current_theme);
                                $border = $isSelected ? "2px solid var(--accent-orange)" : "1px solid #333";
                                $bgStyle = $isSelected ? "background:rgba(255,255,255,0.1);" : "background:transparent;";
                                
                                echo "<div onclick=\"selectTheme('$k', this)\" class='theme-option' data-value='$k' style='cursor:pointer; padding:8px; border-radius:4px; border:$border; $bgStyle display:flex; align-items:center; gap:10px;'>";
                                // Swatch
                                echo "<div style='width:24px; height:24px; border-radius:50%; background:$bg; border:2px solid $acc;'></div>";
                                echo "<div style='color:var(--text-light); font-size:0.9rem;'>$n</div>";
                                if($isSelected) echo "<div style='margin-left:auto; color:var(--accent-orange);'><i class='fa-solid fa-check'></i></div>";
                                echo "</div>";
                            }
                            
                            echo "</div>";
                            echo "</details>";
                        }
                        ?>
                    </div>
                </div>
                
                <script>
                function selectTheme(val, el) {
                    // Update field
                    document.getElementById('selected_theme_input').value = val;
                    
                    // Visual update
                    document.querySelectorAll('.theme-option').forEach(d => {
                        d.style.borderColor = '#333';
                        d.style.background = 'transparent';
                        if(d.querySelector('.fa-check')) d.querySelector('.fa-check').remove();
                    });
                    
                    el.style.borderColor = 'var(--accent-orange)';
                    el.style.borderWidth = '2px';
                    el.style.background = 'rgba(255,255,255,0.1)';
                    // Add check
                    let check = document.createElement('div');
                    check.style.marginLeft = 'auto';
                    check.style.color = 'var(--accent-orange)';
                    check.innerHTML = "<i class='fa-solid fa-check'></i>";
                    el.appendChild(check);
                }
                </script>
                <div style="text-align:right; gap:10px; display:flex; justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('edit-profile').style.display='none'" style="padding:8px 15px; background:transparent; border:1px solid var(--text-muted); color:var(--text-muted); border-radius:4px; cursor:pointer;">Cancel</button>
                    <button type="submit" name="update_profile" style="padding:8px 15px; background:var(--accent-orange); border:none; color:white; border-radius:4px; cursor:pointer;">Save Changes</button>
                </div>
            </form>
        </div> <?php } ?>
    </div>

</div>


<!-- NEW Share Node (Hidden) -->
<div id="share-node-v2" style="position:fixed; left:-9999px; top:0; width:600px; height:900px; background:#0f172a; font-family:'Inter', sans-serif; color:white; overflow:hidden;">
    <!-- Background Accents -->
    <div style="position:absolute; top:0; left:0; width:100%; height:100%; background:radial-gradient(circle at 50% 30%, #1e293b 0%, #0f172a 70%); z-index:0;"></div>
    
    <div style="position:relative; z-index:10; padding:40px; text-align:center; display:flex; flex-direction:column; height:100%; box-sizing:border-box;">
        
        <!-- Header: User Info -->
        <div style="display:flex; flex-direction:column; align-items:center; gap:15px; margin-bottom:40px; margin-top:20px;">
            <img src="<?php echo htmlspecialchars($avatar); ?>" onerror="<?php echo htmlspecialchars($err, ENT_QUOTES); ?>" style="width:120px; height:120px; border-radius:50%; border:4px solid var(--accent-orange); box-shadow:0 10px 30px rgba(0,0,0,0.5); object-fit:cover;">
            <div style="text-align:center;">
                <h2 style="margin:0; font-size:2.2rem; font-weight:800; letter-spacing:-1px; color:#fff; text-shadow:0 2px 10px rgba(0,0,0,0.5);"><?php echo htmlspecialchars(stripslashes($bracket['name'])); ?></h2>
                <div style="color:var(--accent-orange); font-size:1rem; font-weight:700; text-transform:uppercase; letter-spacing:2px; margin-top:5px;">My 2026 Bracket</div>
            </div>
        </div>

        <!-- The Semifinals -->
        <div style="margin-bottom:20px; flex-grow:1;">
            <div style="font-size:0.9rem; text-transform:uppercase; letter-spacing:2px; color:var(--text-muted); margin-bottom:20px; border-bottom:1px solid #334155; display:inline-block; padding-bottom:5px;">My Semifinal Picks</div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <?php 
                $fff = [57,58,59,60]; 
                foreach($fff as $gid) {
                    $name = (isset($r4_picks[$gid]) && $r4_picks[$gid]) ? h($r4_picks[$gid]) : '???';
                    echo "<div style='background:linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03)); border:1px solid rgba(255,255,255,0.1); padding:20px 10px; border-radius:12px; font-weight:700; font-size:1.1rem; display:flex; align-items:center; justify-content:center; min-height:60px; box-shadow:0 4px 6px rgba(0,0,0,0.1);'>
                            $name
                          </div>";
                }
                ?>
            </div>
        </div>

        <!-- The Champion -->
        <div style="margin-bottom:40px;">
             <div style="font-size:0.9rem; text-transform:uppercase; letter-spacing:3px; color:var(--accent-orange); margin-bottom:15px;">Projected Champion</div>
             <div style="background:linear-gradient(135deg, rgba(249, 115, 22, 0.1), rgba(0,0,0,0.2)); border:2px solid var(--accent-orange); padding:20px 40px; border-radius:20px; box-shadow:0 0 40px rgba(249, 115, 22, 0.15); display:inline-block; min-width:80%;">
                <div style="font-size:2.2rem; font-weight:900; line-height:1.2; text-shadow:0 4px 10px rgba(0,0,0,0.5); word-break:break-word;">
                    <?php echo h($champ_pick); ?>
                </div>
             </div>
        </div>

        <!-- Footer -->
        <div style="margin-top:auto; padding-top:20px; border-top:1px solid #334155; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:0.9rem; color:#64748b;">Tournament '26</div>
            <?php
            // Check if Final Four is set in Master bracket (games 57,58,59,60 decided)
            $mCheck = $db->query("SELECT * FROM master WHERE id=2")->fetch();
            $show_rank = ($mCheck && $mCheck['57'] && $mCheck['58'] && $mCheck['59'] && $mCheck['60']);
            
            if($show_rank) {
                echo '<div style="font-weight:bold; color:white;">#'.$rank.' Overall</div>';
            }
            ?>
        </div>
    </div>
</div>

<!-- Share Generator Script -->
<script src="js/lib/html2canvas.js"></script>
<script>
function shareCard() {
    const btn = document.getElementById('shareBtn');
    btn.innerHTML = "📸 Generatiing...";
    
    // Target the new specialized node
    const element = document.getElementById('share-node-v2');
    
    html2canvas(element, {
        backgroundColor: '#0f172a',
        scale: 2 // High Res
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'MyBracketPicks.png';
        link.href = canvas.toDataURL();
        link.click();
        
        btn.innerHTML = "📤 Share Graphic";
    });
}
</script>


</body>
</html>
