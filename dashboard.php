<?php
session_start();
include_once("admin/database.php");
include_once("admin/functions.php");

// 1. Auth Check
require_once __DIR__ . '/includes/require_login.php';
$user_id = $auth_user_id;
$user_email = $auth_user_email;


// We fetch all brackets for this user.
$stmt = $db->prepare("SELECT * FROM brackets WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$brackets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Primary bracket (for avatar/stats display)
$selected_id = isset($_GET['view_bracket']) ? (int)$_GET['view_bracket'] : 0;
$bracket = false;

if (count($brackets) > 0) {
    if ($selected_id > 0) {
        // Find specific
        foreach ($brackets as $b) {
            if ($b['id'] == $selected_id) {
                $bracket = $b;
                break;
            }
        }
    }
    // Default to first if not found or no selection
    if (!$bracket) {
        $bracket = $brackets[0];
    }
}

if (!$bracket) {
    // User registered but has no bracket. 
    // Do NOT redirect. Show "Create Bracket" state.
    $has_bracket = false;
    $bracket_id = 0;
    // For dropdown label when no bracket exists
    $bracket_name = "No Bracket";
} else {
    $has_bracket = true;
    $bracket_id = (int)$bracket['id']; // Cast for safety
    $bracket_name = stripslashes($bracket['name']);
}

// User Name for Welcome Message
// Fetch explicitly from DB to avoid session/bracket name confusion
$u_stmt = $db->prepare("SELECT name FROM users WHERE id=?");
$u_stmt->execute([$user_id]);
$u_res = $u_stmt->fetch(PDO::FETCH_ASSOC);
$display_user_name = ($u_res && !empty($u_res['name'])) ? $u_res['name'] : ((isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "User"));

// Theme update logic moved to profile.php
$msg = "";
// 8. Handle Add Rival
if (isset($_POST['add_rival'])) {
    $target_id = isset($_POST['rival_target_id']) ? intval($_POST['rival_target_id']) : 0;
    
    if ($target_id > 0) {
        // Direct ID lookup (from autocomplete)
        $stmt = $db->prepare("SELECT id, name FROM brackets WHERE id = ? AND id != ? LIMIT 1");
        $stmt->execute([$target_id, $bracket_id]);
    } else {
        // Fallback text search
        $search = trim($_POST['rival_name']);
        $stmt = $db->prepare("SELECT id, name FROM brackets WHERE (name = ? OR email = ?) AND id != ? LIMIT 1");
        $stmt->execute([$search, $search, $bracket_id]);
    }
    
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($match) {
        // Check duplicate
        $check = $db->prepare("SELECT id FROM rivals WHERE user_id = ? AND rival_id = ?");
        $check->execute([$bracket_id, $match['id']]);
        if (!$check->fetch()) {
            $add = $db->prepare("INSERT INTO rivals (user_id, rival_id) VALUES (?, ?)");
            $add->execute([$bracket_id, $match['id']]);
            $msg = "<div class='success'>Rival added: " . htmlspecialchars($match['name']) . "</div>";
        } else {
            $msg = "<div class='error'>Already listed as a rival.</div>";
        }
    } else {
        $msg = "<div class='error'>User not found. Try exact Bracket Name or Email.</div>";
    }
}
if (isset($_GET['remove_rival'])) {
    $rid = intval($_GET['remove_rival']);
    $del = $db->prepare("DELETE FROM rivals WHERE user_id = ? AND rival_id = ?");
    $del->execute([$bracket_id, $rid]);
    $msg = "<div class='success'>Rival removed.</div>";
}

$avatarPayload = isset($bracket['avatar_url']) && $bracket['avatar_url'] ? $bracket['avatar_url'] : 'images/default_avatar.png';

include("header.php");
// End Early Logic
$query = "SELECT * FROM meta WHERE id=1 LIMIT 1";
$stmt = $db->query($query);
$meta = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine Lock Status based on Bracket Type
$current_type = isset($bracket['type']) ? $bracket['type'] : 'main'; 

if($current_type == 'sweet16') {
    $is_closed = ($meta['sweet16_closed'] == 1);
    if(!$is_closed && !empty($meta['sweet16_deadline']) && time() > strtotime($meta['sweet16_deadline'])) {
        $is_closed = true;
    }
} else {
    $is_closed = ($meta['closed'] == 1);
    // Main deadline check
    if(!$is_closed && !empty($meta['deadline']) && time() > strtotime($meta['deadline'])) {
        $is_closed = true;
    }
}

// 4. Calculate Stats (Rank, Score, Best)
// Replicate sorting logic but FILTER by the current bracket's type (Main vs Sweet 16)
$current_type = isset($bracket['type']) ? $bracket['type'] : 'main'; 

$query = "SELECT s.id, s.score, bs.score as best_score, b.name 
          FROM scores s 
          JOIN best_scores bs ON s.id = bs.id AND s.scoring_type = bs.scoring_type
          JOIN brackets b ON s.id = b.id
          WHERE s.scoring_type = 'main' AND b.type = '$current_type'
          ORDER BY s.score DESC, bs.score DESC";
$stmt = $db->query($query);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$my_rank = "N/A";
$my_score = 0;
$my_best = 0;
$rank = 0;
$prev_score = -1;
$rank_counter = 1;

foreach ($rows as $row) {
    if ($prev_score != $row['score']) {
        $rank = $rank_counter;
        $prev_score = $row['score'];
    }
    
    if ($row['id'] == $bracket_id) {
        $my_rank = $rank;
        $my_score = $row['score'];
        $my_best = $row['best_score'];
        break;
    }
    $rank_counter++;

}

// Format Rank
if ($my_rank === "N/A") {
    $rank_display = "-";
} else {
    $suffix = "th";
    if ($my_rank % 10 == 1 && $my_rank % 100 != 11) $suffix = "st";
    if ($my_rank % 10 == 2 && $my_rank % 100 != 12) $suffix = "nd";
    if ($my_rank % 10 == 3 && $my_rank % 100 != 13) $suffix = "rd";
    $rank_display = $my_rank . $suffix;
}

// 5. Get Recent Activity
$stmt = $db->query("SELECT * FROM comments ORDER BY time DESC LIMIT 3");
$recent_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Logic moved to top for auto-refresh
?>

<div id="main" class="full">
    <style>
        .dashboard-card { background: rgba(255,255,255,0.03) !important; border-color: rgba(255,255,255,0.1) !important; }
        .dashboard-card:hover { background: rgba(255,255,255,0.06) !important; border-color: var(--accent-orange) !important; }
    </style>
    <div class="content-card" style="width:98%; margin:0 auto;">
        <!-- Standard Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 20px;">
                 <!-- Avatar Display -->
                 <div style="position: relative; text-align: center;">
                    <div style="width: 80px; height: 80px; margin: 0 auto;">
                        <img src="<?php echo htmlspecialchars($avatarPayload); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 2px solid var(--accent-orange); background: #333;" onerror="this.src='avatar.php?name=<?php echo urlencode(strip_tags($bracket_name)); ?>&background=random'">
                    </div>
                </div>
                <div style="text-align: center;">
                    <a href="profile.php?id=<?php echo $bracket_id; ?>&edit=1" style="display:inline-block; background: var(--secondary-blue); border: 1px solid var(--accent-orange); color: var(--accent-orange); border-radius: 4px; padding: 5px 10px; cursor: pointer; margin-top: 5px; font-weight:bold; text-decoration:none;">Edit Profile</a>
                 </div>

                <div>
                    <h2>Dashboard</h2>
                    <h3 style="margin: 0; color: var(--text-muted);">Welcome back, <?php echo htmlspecialchars($display_user_name); ?></h3>
                </div>
            </div>
            
        </div>
        
        <?php echo $msg; ?>

        <!-- Stats Cards -->
        <div class="dashboard-grid">
            <!-- Rank Card -->
            <a href="standings.php?type=normal" class="dashboard-card">
                <div style="height: 3rem; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;">
                    <i class="fa-solid fa-trophy" style="font-size: 2.5rem; line-height: 1; color: var(--accent-orange);"></i>
                </div>
                <h3>Current Rank</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--text-light); margin: 0.5rem 0;"><?php echo $rank_display; ?></div>
                <p>View Standings</p>
            </a>

            <!-- Score Card -->
            <a href="standings.php?type=normal" class="dashboard-card">
                <!-- CSS Scoreboard Icon -->
                <div style="height: 3rem; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;">
                    <div style="background: #1a1a1a; border: 2px solid #333; border-radius: 6px; padding: 4px 12px; display: inline-flex; gap: 4px; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.5);">
                        <div style="font-family: 'Courier New', monospace; font-weight: bold; color: #ef4444; font-size: 1.2rem; line-height: 1; letter-spacing: 1px; text-shadow: 0 0 5px rgba(239, 68, 68, 0.5);">69</div>
                        <div style="color: #666; font-size: 0.8rem; margin-top: -2px;">:</div>
                        <div style="font-family: 'Courier New', monospace; font-weight: bold; color: #ef4444; font-size: 1.2rem; line-height: 1; letter-spacing: 1px; text-shadow: 0 0 5px rgba(239, 68, 68, 0.5);">72</div>
                    </div>
                </div>
                <h3>Total Score</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--text-light); margin: 0.5rem 0;"><?php echo $my_score; ?></div>
                <p>Points</p>
            </a>

            <!-- Potential Card -->
            <a href="standings.php?type=best" class="dashboard-card">
                <div style="height: 3rem; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;">
                    <i class="fa-solid fa-chart-line" style="font-size: 2.5rem; line-height: 1; color: #10b981;"></i>
                </div>
                <h3>Max Potential</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--text-light); margin: 0.5rem 0;"><?php echo $my_best; ?></div>
                <p>Possible Points</p>
            </a>
        </div>

        <div class="dashboard-grid">
            <!-- Bracket Status -->
            <div class="dashboard-card" style="grid-column: span 2; align-items: flex-start; text-align: left; position: relative; z-index: 20;">
                <h3 style="width: 100%; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;">My Bracket</h3>
                <div style="display: flex; gap: 2rem; align-items: center; width: 100%;">
                    <?php if ($has_bracket): ?>
                        <div style="flex: 1;">
                             <p style="font-size: 1.1rem; color: var(--text-light);">
                                Status: 
                                <?php if ($is_closed): ?>
                                    <strong style="color: var(--accent-orange);">LOCKED</strong>
                                <?php else: ?>
                                    <strong style="color: #a7f3d0;">OPEN</strong>
                                <?php endif; ?>
                            </p>
                            <p>
                                <?php if ($is_closed): ?>
                                    The tournament is underway! track your prowess in the standings.
                                <?php else: ?>
                                    Selections are still open. Make sure to finalize your picks before the first game!
                                <?php endif; ?>
                            </p>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:10px; min-width:250px;">
                            <?php 
                                // Fetch Limit
                                $limit = isset($meta['max_brackets']) ? $limit = $meta['max_brackets'] : 1;
                                $count = count($brackets);
                            
                                if($count > 1) {
                                    // Custom Dropdown UI (My Account Style)
                                    echo '<div style="position:relative;">';
                                    
                                    // Main Toggle Button
                                    echo '<button onclick="toggleBracketMenu(event)" style="width:100%; padding:15px; background:rgba(0,0,0,0.3); border:1px solid var(--border-color); color:white; border-radius:6px; cursor:pointer; display:flex; justify-content:space-between; align-items:center; text-align:left;">';
                                    echo '<span><span style="color:#aaa; font-size:0.8em;">Current:</span> <span style="font-weight:bold; color:var(--accent-orange);">' . htmlspecialchars($bracket_name) . '</span></span>';
                                    echo '<i class="fa-solid fa-chevron-down"></i>';
                                    echo '</button>';

                                    // Dropdown Menu
                                    echo '<div id="bracketDropdown" style="display:none; position:absolute; top:100%; left:0; width:100%; background:var(--secondary-blue); border:1px solid var(--accent-orange); border-radius:6px; margin-top:5px; z-index:1000; box-shadow:0 10px 25px rgba(0,0,0,0.5); overflow:hidden; pointer-events: auto !important;">';
                                    
                                    foreach($brackets as $b) {
                                        $is_active = ($b['id'] == $bracket_id);
                                        $bg = $is_active ? 'rgba(255,255,255,0.1)' : 'transparent';
                                        
                                        // Standard HREF - Z-Index 1000 should handle layering
                                        echo '<a href="dashboard.php?view_bracket='.$b['id'].'" style="display:block; padding:12px 15px; color:var(--text-light); text-decoration:none; border-bottom:1px solid rgba(255,255,255,0.05); background:'.$bg.'; transition:background 0.2s; cursor:pointer; position: relative; z-index: 1001;">';
                                        echo h(stripslashes($b['name']));
                                        if($is_active) echo ' <span style="color:var(--accent-orange); float:right;"><i class="fa-solid fa-check"></i></span>';
                                        echo '</a>';
                                    }
                                    
                                    // Create New Option inside dropdown
                                    if($count < $limit && !$is_closed) {
                                        echo '<a href="submit.php" style="display:block; padding:12px 15px; color:#22c55e; text-decoration:none; font-weight:bold; background:rgba(34, 197, 94, 0.1); cursor:pointer; position: relative; z-index: 1001;">';
                                        echo '<i class="fa-solid fa-plus"></i> Create New Bracket';
                                        echo '</a>';
                                    }
                                    
                                    echo '</div>'; // End Dropdown
                                    echo '</div>'; // End Relative Wrapper

                                    // Scripts
                                    echo '<script>
                                    function toggleBracketMenu(e) {
                                        e.preventDefault();
                                        e.stopPropagation(); // Prevent immediate window click trigger
                                        var d = document.getElementById("bracketDropdown");
                                        d.style.display = (d.style.display === "block") ? "none" : "block";
                                    }
                                    // Close on outside click
                                    document.addEventListener("click", function(e) {
                                        // If click is NOT inside dropdown and NOT the toggle button
                                        if (!e.target.closest("#bracketDropdown")) {
                                            var d = document.getElementById("bracketDropdown");
                                            if(d) d.style.display = "none";
                                        }
                                    });
                                    </script>';
                                    
                                    // Primary Action Button (Edit/View Current)
                                    $actionUrl = $is_closed ? 'view.php?id='.$bracket_id : 'edit.php?id='.$bracket_id;
                                    echo '<button onclick="window.location.href=\''.$actionUrl.'\'" style="margin-top:10px; font-size: 1.1rem; padding: 1rem 2rem; width:100%; border-radius:6px;">
                                            '.($is_closed ? "View Current Pick" : "Edit Current Pick").'
                                          </button>';

                                } else {
                                    // ... Single Bracket Logic (Keep logic simple)
                                    $actionUrl = $is_closed ? 'view.php?id='.$bracket_id : 'edit.php?id='.$bracket_id;
                                    echo '<button onclick="window.location.href=\''.$actionUrl.'\'" style="font-size: 1.1rem; padding: 1rem 2rem; width:100%;">
                                            '.($is_closed ? "View My Picks" : "Edit My Picks").'
                                          </button>';
                                    
                                    // Create New (if limit allowed but count is 1)
                                     if($count < $limit && !$is_closed) {
                                        echo '<button onclick="window.location.href=\'submit.php\'" style="font-size: 0.9rem; padding: 10px; width:100%; background: #22c55e; margin-top:5px;">
                                                + Create Another Bracket
                                              </button>';
                                    }
                                }
                            ?>
                        </div>
                    <?php else: ?>
                        <div style="flex: 1;">
                             <p style="font-size: 1.1rem; color: #facc15;">
                                Status: <strong>NOT SUBMITTED</strong>
                            </p>
                            <p>
                                You haven't created a bracket yet! Get in the game before the deadline.
                            </p>
                        </div>
                        <div>
                            <button onclick="window.location.href='submit.php'" style="font-size: 1.1rem; padding: 1rem 2rem; background: #22c55e;">
                                Create Bracket
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rivalry Watch -->
            <div class="dashboard-card" style="grid-column: span 3; align-items: flex-start; text-align: left;">
                 <div style="display:flex; justify-content:space-between; align-items:center; width:100%; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;">
                    <h3 style="margin:0;"><i class="fa-solid fa-users"></i> Rivalry Watch</h3>
                    <form method="post" style="display:flex; gap:10px; position:relative;">
                        <input type="text" id="rival_search" name="rival_name" placeholder="Search Bracket, Name, or Email..." style="padding:6px; font-size:0.8rem; margin:0; width:220px;" autocomplete="off" required>
                        <input type="hidden" name="rival_target_id" id="rival_target_id" value="">
                        <button type="submit" name="add_rival" id="add_rival_btn" style="padding:6px 12px; font-size:0.8rem;">Add</button>
                        
                        <!-- Suggestions Dropdown -->
                        <div id="rival_suggestions" style="position: absolute; top: 100%; left: 0; width: 220px; background: #1e293b; border: 1px solid var(--accent-orange); border-radius: 4px; z-index: 1000; display: none; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.3);"></div>
                    </form>
                    
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const input = document.getElementById('rival_search');
                        const targetId = document.getElementById('rival_target_id');
                        const suggestions = document.getElementById('rival_suggestions');
                        let debounceTimer;

                        input.addEventListener('input', function() {
                            const val = this.value.trim();
                            targetId.value = ''; // Reset ID on manual type
                            
                            clearTimeout(debounceTimer);
                            if (val.length < 2) {
                                suggestions.style.display = 'none';
                                return;
                            }

                            debounceTimer = setTimeout(() => {
                                fetch('api/search_users.php?q=' + encodeURIComponent(val))
                                    .then(res => res.json())
                                    .then(data => {
                                        suggestions.innerHTML = '';
                                        if (data.length > 0) {
                                            data.forEach(user => {
                                                const div = document.createElement('div');
                                                div.style.padding = '8px';
                                                div.style.cursor = 'pointer';
                                                div.style.color = '#fff';
                                                div.style.borderBottom = '1px solid rgba(255,255,255,0.1)';
                                                div.style.fontSize = '0.8rem';
                                                
                                                
                                                const strong = document.createElement('strong');
                                                strong.textContent = user.name;
                                                const span = document.createElement('span');
                                                span.style.cssText = 'color:#aaa; font-size:0.7em';
                                                span.textContent = ' (' + user.person + ')';
                                                div.appendChild(strong);
                                                div.appendChild(span);
                                                
                                                div.onmouseover = () => { div.style.background = 'var(--accent-orange)'; div.style.color = '#000'; };
                                                div.onmouseout = () => { div.style.background = 'transparent'; div.style.color = '#fff'; };
                                                
                                                div.onclick = () => {
                                                    input.value = user.name;
                                                    targetId.value = user.id;
                                                    suggestions.style.display = 'none';
                                            
                                                };
                                                suggestions.appendChild(div);
                                            });
                                            suggestions.style.display = 'block';
                                        } else {
                                            suggestions.style.display = 'none';
                                        }
                                    })
                                    .catch(err => console.error('Error fetching users:', err));
                            }, 300);
                        });

                        // Close on click outside
                        document.addEventListener('click', function(e) {
                            if (!suggestions.contains(e.target) && e.target !== input) {
                                suggestions.style.display = 'none';
                            }
                        });
                    });
                    </script>
                 </div>
                 
                 <div class="table-container">
                 <table style="width:100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid #444; color: #aaa;">
                            <th style="text-align:left; padding: 5px;">Rank</th>
                            <th style="text-align:left; padding: 5px;">Bracket</th>
                            <th style="text-align:center; padding: 5px;">Score</th>
                            <th style="text-align:right; padding: 5px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        // Get Rival IDs
                        $rivals_stmt = $db->prepare("SELECT rival_id FROM rivals WHERE user_id = ?");
                        $rivals_stmt->execute([$bracket_id]);
                        $rival_ids = $rivals_stmt->fetchAll(PDO::FETCH_COLUMN);
                        $rival_ids[] = $bracket_id; // Add self

                        // Filter Leaderboard
                        $count = 0;
                        $rank_itr = 1;
                        $p_score = -1;
                        $display_r = 1;

                        foreach ($rows as $r) {
                            if ($p_score != $r['score']) {
                                $display_r = $rank_itr;
                                $p_score = $r['score'];
                            }
                            
                            if (in_array($r['id'], $rival_ids)) {
                                $is_me = ($r['id'] == $bracket_id);
                                $row_style = $is_me ? "background:rgba(249, 115, 22, 0.1); font-weight:bold;" : "";
                                $name_color = $is_me ? "var(--accent-orange)" : "var(--text-light)";
                                
                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05); $row_style'>
                                        <td style='padding: 8px 5px;'>{$display_r}</td>
                                        <td style='padding: 8px 5px; color:$name_color;'>".h(stripslashes($r['name']))."</td>
                                        <td style='padding: 8px 5px; text-align:center;'>{$r['score']}</td>
                                        <td style='padding: 8px 5px; text-align:right;'>";
                                if (!$is_me) {
                                    echo "<a href='dashboard.php?remove_rival={$r['id']}' style='color:#ef4444; text-decoration:none; font-size:0.8rem;'>&times; Remove</a>";
                                } else {
                                    echo "<span style='color:#aaa; font-size:0.8rem;'>You</span>";
                                }
                                echo "</td></tr>";
                                $count++;
                            }
                            $rank_itr++;
                        }
                        
                        if (count($rival_ids) <= 1) { // Only self
                             echo '<tr><td colspan="4" style="padding: 15px; text-align:center; color: #666; font-style: italic;">No rivals added. Add a friend to compare scores!</td></tr>';
                        }
                    ?>
                    </tbody>
                 </table>
                 </div>
            </div>

            <!-- Activity Feed -->
            <div class="dashboard-card" style="grid-column: span 1; align-items: flex-start; text-align: left; overflow: hidden;">
                <h3 style="width: 100%; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;"><i class="fa-solid fa-comments"></i> Live Smack Talk</h3>
                
                <!-- Chat Window -->
                <div id="chat-window" style="flex: 1; width: 100%; overflow-y: auto; background: rgba(0,0,0,0.2); border-radius: 6px; padding: 10px; margin-bottom: 10px; height: 300px; scroll-behavior: smooth;">
                    <div style="text-align:center; color:#666; padding-top:130px;">Loading chat...</div>
                </div>
                
                <!-- Chat Input -->
                <form id="chat-form" style="width: 100%; display: flex; gap: 10px;">
                    <input type="text" id="chat-input" placeholder="Say something..." style="flex: 1; padding: 8px; border-radius: 4px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.05); color: #fff;" autocomplete="off">
                    <button type="submit" style="padding: 8px 15px; border-radius: 4px; border: none; background: var(--accent-orange); color: #fff; cursor: pointer;">Send</button>
                </form>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const chatWindow = document.getElementById('chat-window');
                    const chatForm = document.getElementById('chat-form');
                    const chatInput = document.getElementById('chat-input');
                    let lastId = 0;
                    let isScrolledToBottom = true;

                    // Detect scroll position
                    chatWindow.addEventListener('scroll', () => {
                        const threshold = 50;
                        const position = chatWindow.scrollTop + chatWindow.clientHeight;
                        const height = chatWindow.scrollHeight;
                        isScrolledToBottom = (position > height - threshold);
                    });

                    function formatTime(sqlTime) {
                        const d = new Date(sqlTime.replace(' ', 'T')); // Fix for some browsers/SQL formats
                        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    }

                    function loadChat() {
                        fetch('api/get_comments.php')
                            .then(res => res.json())
                            .then(data => {
                                if (!data || data.length === 0) {
                                    chatWindow.innerHTML = '<div style="text-align:center; color:#666; margin-top:20px;">No smack talk yet. Start the fire!</div>';
                                    return;
                                }

                                
                                function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

                                const fragment = document.createDocumentFragment();
                                data.forEach(msg => {
                                    const avatar = msg.avatar_url ? msg.avatar_url : 'images/default_avatar.png';
                                    const row = document.createElement('div');
                                    row.style.cssText = 'margin-bottom:12px; display:flex; gap:10px; align-items:flex-start;';

                                    const img = document.createElement('img');
                                    img.src = avatar;
                                    img.style.cssText = 'width:30px; height:30px; border-radius:50%; border:1px solid var(--accent-orange);';
                                    img.onerror = function(){ this.src='avatar.php?name='+encodeURIComponent(msg.from.replace(/<[^>]*>/g,''))+'&background=random'; };
                                    row.appendChild(img);

                                    const bubble = document.createElement('div');
                                    bubble.style.cssText = 'background:rgba(255,255,255,0.05); padding:8px 12px; border-radius:8px; flex:1;';

                                    const hdr = document.createElement('div');
                                    hdr.style.cssText = 'display:flex; justify-content:space-between; margin-bottom:4px;';
                                    const nameEl = document.createElement('strong');
                                    nameEl.style.cssText = 'font-size:0.85rem; color:var(--accent-orange);';
                                    nameEl.textContent = msg.from;
                                    const timeEl = document.createElement('span');
                                    timeEl.style.cssText = 'font-size:0.7rem; color:#64748b;';
                                    timeEl.textContent = formatTime(msg.time);
                                    hdr.appendChild(nameEl);
                                    hdr.appendChild(timeEl);
                                    bubble.appendChild(hdr);

                                    const body = document.createElement('div');
                                    body.style.cssText = 'font-size:0.9rem; color:var(--text-light); word-break:break-word;';
                                    body.textContent = msg.content;
                                    bubble.appendChild(body);

                                    row.appendChild(bubble);
                                    fragment.appendChild(row);
                                });

                                // Replace content safely
                                chatWindow.innerHTML = '';
                                chatWindow.appendChild(fragment);
                                if (isScrolledToBottom) {
                                    chatWindow.scrollTop = chatWindow.scrollHeight;
                                }
                            })
                            .catch(err => console.error('Chat poll error:', err));
                    }

                    // Initial Load
                    loadChat();

                    // Poll every 5 seconds
                    setInterval(loadChat, 5000);

                    // Send
                    chatForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        const msg = chatInput.value.trim();
                        if (!msg) return;

                        chatInput.value = ''; // Clear early for responsiveness
                        chatInput.disabled = true;

                        fetch('api/post_comment.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ message: msg })
                        })
                        .then(res => res.json())
                        .then(data => {
                            chatInput.disabled = false;
                            chatInput.focus();
                            if(data.success) {
                                loadChat(); // Instant refresh
                                isScrolledToBottom = true; // Force scroll on own message
                            } else {
                                alert(data.error || 'Error sending message');
                            }
                        })
                        .catch(err => {
                            chatInput.disabled = false;
                            alert('Network error');
                        });
                    });
                });
                </script>
            </div>
            <!-- Trophy Case -->
            <div class="dashboard-card" style="grid-column: span 3; align-items: flex-start; text-align: left;">
                 <h3 style="width: 100%; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;"><i class="fa-solid fa-award"></i> Trophy Case</h3>
                 <div style="display:flex; gap:15px; flex-wrap:wrap;">
                    <?php
                    // Fetch Badges
                    include_once("admin/badges.php");
                    if(class_exists('BadgeManager')) {
                        $bm = new BadgeManager($db);
                        $myBadges = $bm->getBadges($bracket_id);
                        
                        if(count($myBadges) > 0) {
                            foreach($myBadges as $badge) {
                                // Define colors based on name logic or DB color
                                $colorMap = [
                                    'emerald' => '#10b981',
                                    'purple' => '#8b5cf6',
                                    'stone' => '#78716c',
                                    'orange' => '#f97316',
                                    'red' => '#ef4444', 
                                    'gold' => '#eab308',
                                    'pink' => '#ec4899',
                                    'blue' => '#3b82f6',
                                    'green' => '#22c55e',
                                    'teal' => '#14b8a6',
                                    'indigo' => '#6366f1',
                                    'gray' => '#9ca3af'
                                ];
                                $bg = isset($colorMap[$badge['color']]) ? $colorMap[$badge['color']] : '#3b82f6';
                                
                                echo '<div style="background: rgba(255,255,255,0.05); padding: 10px 15px; border-radius: 8px; border: 1px solid '.$bg.'; display: flex; align-items: center; gap: 10px;" title="'.h($badge['description']).'">
                                        <div style="font-size: 1.5rem;">'.h($badge['emoji']).'</div>
                                        <div>
                                            <div style="font-weight: bold; color: '.$bg.'; font-size: 0.9rem;">'.h($badge['name']).'</div>
                                            <div style="font-size: 0.7rem; color: #aaa;">'.h($badge['description']).'</div>
                                        </div>
                                      </div>';
                            }
                        } else {
                            echo '<div style="color: #666; font-style: italic;">No badges earned yet... Go make some history!</div>';
                        }
                    } else {
                        echo '<div style="color: #666;">Badges system initializing...</div>';
                    }
                    ?>
                 </div>
            </div>

            <!-- My History -->
            <div class="dashboard-card" style="grid-column: span 3; align-items: flex-start; text-align: left;">
                 <h3 style="width: 100%; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;"><i class="fa-solid fa-clock-rotate-left"></i> My History</h3>
                 <div class="table-container">
                 <table style="width:100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid #444; color: #aaa;">
                            <th style="text-align:left; padding: 5px;">Year</th>
                            <th style="text-align:left; padding: 5px;">Bracket Name</th>
                            <th style="text-align:center; padding: 5px;">Rank</th>
                            <th style="text-align:right; padding: 5px;">Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $histStmt = $db->prepare("SELECT * FROM historical_results WHERE email = ? ORDER BY year DESC");
                        $histStmt->execute([$user_email]);
                        $history = $histStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if(count($history) > 0) {
                            foreach($history as $h) {
                                echo '<tr>
                                        <td style="padding: 8px 5px; color: var(--accent-orange);">'.$h['year'].'</td>
                                        <td style="padding: 8px 5px;">'.htmlspecialchars($h['bracket_name']).'</td>
                                        <td style="padding: 8px 5px; text-align:center;">'.$h['rank'].'</td>
                                        <td style="padding: 8px 5px; text-align:right; color:#10b981;">';
                                if($h['earnings'] > 0) echo '$'.number_format($h['earnings'], 2); else echo '-';
                                echo '</td></tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" style="padding: 15px; text-align:center; color: #666; font-style: italic;">No history recorded yet.</td></tr>';
                        }
                    ?>
                    </tbody>
                 </table>
                 </div>
            </div>

        </div>

    </div>
<?php include("footer.php"); ?>
</div>
</body>
</html>

