<?php
@include_once("admin/database.php");
include_once("admin/functions.php");

// Install Check: If $db is not initialized, we are not installed.
if(!isset($db) || !$db) {
    if(file_exists(__DIR__ . "/admin/install.php")) {
        header("Location: admin/install.php");
        exit();
    } else {
        die("System Error: Configuration missing and installer not found.");
    }
}

try {
    $query = "SELECT * FROM `meta` WHERE id=1";
    $stmt = $db->query($query);
    $meta = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If DB is locked or gone away (e.g. during Admin Update), show Maintenance Mode
    // Check for 2006 (Gone Away) or 1205 (Lock Wait)
    http_response_code(503);
    die("
    <div style='font-family:sans-serif; text-align:center; padding:50px; background:#0f172a; color:white; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>
        <h1 style='color:#f97316;'>Tournament Updating...</h1>
        <p>The system is currently recalculating scenarios.</p>
        <p>Please refresh in a few seconds.</p>
        <button onclick='location.reload()' style='padding:10px 20px; background:#f97316; color:white; border:none; border-radius:5px; cursor:pointer; font-weight:bold; margin-top:20px;'>Refresh Now</button>
    </div>
    ");
}

header("Expires: ".gmdate("D, d M Y H:i:s")." GMT"); // Always expired
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");// always modified 
header("Cache-Control: no-cache, must-revalidate");// HTTP/1.1 
header("Pragma: nocache");// HTTP/1.0


if (!function_exists('getCommentsMap')) {
function getCommentsMap($db)
{
	$commentCount =  "SELECT COUNT(*) count, bracket FROM `comments` WHERE UNIX_TIMESTAMP(`time`)>" . (time()-86400) . " GROUP BY `bracket`";
	$commentCountList = $db->query($commentCount);
	$commentMap = array();
	
	while( $commentCount = $commentCountList->fetch(PDO::FETCH_ASSOC) )
	{
		$commentMap[$commentCount['bracket']] = $commentCount['count'];
	}
	
	return $commentMap;
}
}


// Theme Logic
$current_theme_key = 'default';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if ($user_id) {
    // Fetch the user's preferred theme from the users table
    $t_stmt = $db->prepare("SELECT theme FROM users WHERE id = ?");
    $t_stmt->execute([$user_id]);
    $t_res = $t_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($t_res && !empty($t_res['theme'])) {
        $current_theme_key = $t_res['theme'];
    }
}

$all_themes = getThemes();
// Fallback if theme invalid
if (!array_key_exists($current_theme_key, $all_themes)) {
    $current_theme_key = 'default';
}
$active_theme = $all_themes[$current_theme_key];
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Bracket Challenge <?php echo date('Y'); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<meta name="robots" content="noarchive" />
	<link rel="stylesheet" href="css/all.min.css">
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" href="images/style.css?v=<?php echo filemtime(__DIR__ . '/images/style.css'); ?>&bust=1" media="all" />
	<link rel="shortcut icon" href="images/favicon.ico">
    <?php
    // Calculate Text Contrast based on Brightness
    $hex = $active_theme['accent'];
    $hex = ltrim($hex, '#');
    // Handle shorthand hex if necessary (though functions.php uses full 6)
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    // Threshold: 128 is standard, 160 takes care of mid-tones like orange
    $accent_text = ($brightness > 150) ? '#000000' : '#FFFFFF';
    ?>
    <style>
    :root {
        /* Theme Core Colors */
        --accent-orange: <?php echo $active_theme['accent']; ?>;
        --accent-orange-hover: <?php echo $active_theme['accent']; ?>;
        --accent-rgb: <?php echo "$r, $g, $b"; ?>;
        --accent-highlight: rgba(<?php echo "$r, $g, $b"; ?>, 0.90);
        --accent-text: <?php echo $accent_text; ?>;
        
        /* Full Site Theming Overrides (Using dedicated background palette) */
        --primary-blue: <?php echo isset($active_theme['bg1']) ? $active_theme['bg1'] : $active_theme['header2']; ?> !important;
        --secondary-blue: <?php echo isset($active_theme['bg2']) ? $active_theme['bg2'] : $active_theme['header1']; ?> !important;
        
        /* Alias for Components */
        --bg-secondary: var(--secondary-blue);
        --bg-primary: var(--primary-blue);
        --text-light: #f8fafc;
        --text-muted: #f8fafc;

        --border-color: rgba(255,255,255,0.1) !important;
    }
    
    /* Header Override (Keep original gradients for the banner) */
    #header {
        background: linear-gradient(135deg, <?php echo $active_theme['header1']; ?>, <?php echo $active_theme['header2']; ?>) !important;
        border-bottom-color: var(--accent-orange) !important;
    }
    
    /* Specific Element Overrides */
    h2, h3, .dashboard-card i, .matchup .team.selected {
        color: var(--accent-orange) !important;
    }
    
    /* Dynamic Text Contrast for Selected/Buttons */
    .matchup .team.selected, input[type="submit"], button, .btn {
        color: var(--accent-text) !important;
    }

    /* Keep background correct */
    .matchup .team.selected {
        background: var(--accent-orange) !important;
    }
    input[type="submit"]:hover, button:hover {
        opacity: 0.9;
    }
    
    /* Table Headers to match theme */
    th {
        background: var(--secondary-blue) !important;
        color: var(--accent-orange) !important;
    }
    
    /* Sidebar/Navigation matching */
    #subheader {
        background: var(--primary-blue) !important;
        border-bottom-color: var(--border-color) !important;
    }
    </style>
	<link rel="manifest" href="manifest.json">
	<meta name="theme-color" content="#0f172a">
	<link rel="apple-touch-icon" href="images/scoreboard_icon.png">
	<script>
	if ('serviceWorker' in navigator) {
		window.addEventListener('load', () => {
			navigator.serviceWorker.register('service-worker.js')
				.then(reg => console.log('SW registered'))
				.catch(err => console.log('SW registration failed: ', err));
		});
	}
	</script>
	<link rel="stylesheet" type="text/css" href="images/jquery.dataTables.min.css" />
	<script type="text/javascript" src="js/jquery-3.7.1.min.js"></script>
	<script type="text/javascript" src="js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" src="js/script.js?v=<?php echo filemtime(__DIR__ . '/js/script.js'); ?>&v=B" defer></script>
	<script type="text/javascript" src="js/emailall.js?v=<?php echo filemtime(__DIR__ . '/js/emailall.js'); ?>&v=B" defer></script>

<?php
//if this is the submit or what-if page, include the necessary javascript
if(strpos($_SERVER['PHP_SELF'],"submitSweet16.php") !== FALSE || strpos($_SERVER['PHP_SELF'],"submit.php") !== FALSE || strpos($_SERVER['PHP_SELF'],"whatif.php") !== FALSE) {
?>
<script type="text/javascript">
// The key to this array is the game number and the value is the parent node
parents = new Array(-1, 
	33, 33, 34, 34, 35, 35, 36, 36, 
	37, 37, 38, 38, 39, 39, 40, 40, 
	41, 41, 42, 42, 43, 43, 44, 44, 
	45, 45, 46, 46, 47, 47, 48, 48, 
	49, 49, 50, 50, 51, 51, 52, 52, 
	53, 53, 54, 54, 55, 55, 56, 56, 
	57, 57, 58, 58, 59, 59, 60, 60, 
	61, 61, 62, 62, 63, 63, -1 );


function update(childGameId,target, index) 
{
	var childSel = document.getElementById(childGameId);
	var parentSel = document.getElementById(target);
	if( childSel.options.length > 1 )
	{
		var deselectedChildVal = childSel.options[(childSel.selectedIndex + 1) % 2].value;
		deleteTeam( parentSel, deselectedChildVal );	
	}

	var selectedValue = childSel.options[childSel.selectedIndex].value;
	var selectedText = childSel.options[childSel.selectedIndex].text;
	parentSel.options[index] = new Option(selectedText,selectedValue);
}

function deleteTeam( rootNode, teamToDelete )
{	
	//alert( rootNode.id + " " + teamToDelete + " " + childGameNum + " " + parentGameNum);

	var childGameNum = parseInt( rootNode.id.substring(4) );	
	
	for( i =0; i < rootNode.options.length; i++ )
	{
		if( rootNode.options[i].value == teamToDelete )
		{
			rootNode.options[i] = new Option("","");
		}
	}
	
	
	var parentGameNum = parents[childGameNum];	

	if( parentGameNum != -1 )
	{
		var parentGameId = "game" + parentGameNum;
		var parentSel = document.getElementById( parentGameId );

		deleteTeam( parentSel, teamToDelete);
	}		
}

function resetBracket( startId )
{
	if( startId == null )
	{
		startId = 1;
	}
	var resetBracket = window.confirm('Are you sure that you want to reset this bracket?');
	if( resetBracket )
	{
		for( i = startId; i < parents.length -1; i++ )
		{
			var selectBox = document.getElementById( "game" + parents[i] );
			while (selectBox.options.length > 0) {
				selectBox.options[0] = null;
			}
		}
		return true;
	}
	else
	{
		return false;
	}
}
</script>
<?php } ?>

</head>

<body>
	<div class="content">
		<div id="header">
			<div class="header-inner">
				<div class="brand">
					<div class="brand-text">
						<h1><?php echo h($meta['title']); ?></h1>
						<h2><?php echo h($meta['subtitle']); ?></h2>
					</div>
				</div>

				<div class="version-badge">
					<!-- Current Project Link -->
					<a href="https://github.com/joeyl9/OpenBracketChallenge" target="_blank" class="current-project" title="Current Maintained Project">
						<i class="fa-brands fa-github"></i> &copy; <?php echo date("Y"); ?> OpenBracketChallenge
					</a>
				</div>
                
                <!-- Mobile Trigger -->
                <button id="mobile-menu-btn" onclick="console.log('Button Clicked Check');" aria-label="Open menu" aria-controls="mobile-drawer" aria-expanded="false">
                    <span class="hamburger"><span></span><span></span><span></span></span>
                </button>
				
			</div>
		</div>
	
		<div id="subheader">
			<div id="menu">
				<!-- Mobile button moved to top header -->
			  	<ul>
					<li><a href="index.php">HOME</a></li>
					<?php if(is_logged_in()) { ?>
					<li><a href="dashboard.php">DASHBOARD</a></li>
					<?php } ?>
					<?php 
                        // Show "Create Bracket" for Main if open
                        if( $meta['closed'] == 0 ) { 
                            $sched = true;
                            if($meta['deadline']) {
                                if(time() > strtotime($meta['deadline'])) $sched = false;
                            }
                        // Hide if Admin user (Break Glass)
                        if(!isset($_SESSION['user_id']) && is_admin()) {
                            $sched = false;
                        }

                        if($sched) {
                    ?>
						<li><a href="submit.php">CREATE BRACKET</a></li>
					<?php 
                            }
                        } 
                    ?>

                    <?php 
                        // Show "Create Second Chance" if open
                        if( !empty($meta['sweet16Competition']) && empty($meta['sweet16_closed']) ) { 
                             $s16_open = true;
                             if(!empty($meta['sweet16_deadline'])) {
                                 if(time() > strtotime($meta['sweet16_deadline'])) $s16_open = false;
                             }
                             if($s16_open) {
                    ?>
							<li><a href="submit_second_chance.php">CREATE SECOND CHANCE</a></li>
					<?php 
                             }
                        } 
                    ?>
					<li><a href="rules.php">RULES</a></li>
					<li><a href="choose.php">STANDINGS</a></li>
					<li><a href="hall_of_fame.php">HALL OF FAME</a></li>
					<?php if($meta['sweet16'] == 1) { ?>
					<li><a href="whatif.php">SIMULATOR</a></li>
					<?php } ?>
					<?php if($meta['cost'] != 0) { ?>
					<li><a href="paid.php">PAYMENT TRACKER</a></li>
					<?php } ?>

					
					<!-- Register Button -->
					<?php if(!$user_id) { ?>
					<li style="margin-left:10px; float:right;">
						<a href="register.php" style="background:#334155; color:white;">REGISTER</a>
					</li>
					<?php } ?>
                    
                    <!-- Admin Link -->
                    <?php if(is_admin()) { ?>
                    <li class="nav-item-admin" style="margin-left:10px; float:right;">
						<a href="admin/index.php" style="background:#dc2626; color:white;">ADMIN</a>
					</li>
                    <?php } ?>

					<!-- Login Dropdown -->
					<li class="nav-item-account" style="margin-left:10px; position:relative; float:right;">
						<a href="#" onclick="toggleLogin(event)" style="background:var(--accent-orange); color:var(--accent-text);">
							<?php echo $user_id ? 'MY ACCOUNT' : 'LOGIN'; ?>
						</a>
						<div id="loginDropdown" style="display:none; position:absolute; right:0; top:100%; background:var(--secondary-blue); padding:15px; border:1px solid var(--border-color); z-index:1000; width:280px; box-shadow: 0 4px 6px rgba(0,0,0,0.5);">
							<?php
							if(!$user_id) {
							?>
								<form action="login_check.php" method="post" style="margin:0;">
                                    <?php csrf_field(); ?>
									<label style="color:var(--text-light); display:block; margin-bottom:5px; font-size:0.9em;">Email:</label>
									<input type="text" name="useremail" style="width:100%; margin-bottom:10px; padding:8px; border-radius:4px; border:1px solid var(--border-color); background:var(--primary-blue); color:var(--text-light);">
									<label style="color:var(--text-light); display:block; margin-bottom:5px; font-size:0.9em;">Password:</label>
									<input type="password" name="password" style="width:100%; margin-bottom:15px; padding:8px; border-radius:4px; border:1px solid var(--border-color); background:var(--primary-blue); color:var(--text-light);">
									<div style="display:flex; justify-content:space-between; align-items:center;">
                                        <input type="submit" value="LOGIN" style="background:var(--accent-orange); border:none; color:white; padding:8px 15px; cursor:pointer; font-weight:bold; border-radius:4px;">
                                        <a href="forgot_password.php" style="color:#94a3b8; font-size:0.8em; text-decoration:none;">Forgot Password?</a>
                                    </div>
								</form>
							<?php
							} else {
								// Get User details
								$u_stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
								$u_stmt->execute([$user_id]);
								$u_row = $u_stmt->fetch(PDO::FETCH_ASSOC);
								$u_name = $u_row ? $u_row['name'] : 'User';
								
								echo "<div style='color:var(--text-light); margin-bottom:10px; font-weight:bold;'>Hello, ".htmlspecialchars($u_name)."</div>";
								if (is_admin()) {
								echo '<a href="admin/index.php" style="display:block; color:var(--accent-orange); margin-bottom:5px;">[Admin Area]</a>';
								}
                                
                                echo "<div style='margin-bottom:5px;'><a href='profile.php' style='color:var(--accent-orange); text-decoration:none;'>👤 My Profile</a></div>";
								
								echo '<hr style="border-color:var(--border-color); margin:10px 0;">';
								
								// Fetch brackets (linked by user_id)
								$b_query = "SELECT id, name FROM `brackets` WHERE user_id=:uid";
								$b_stmt = $db->prepare($b_query);
								$b_stmt->execute([':uid' => $user_id]);
								
								echo "<div style='color:var(--text-muted); font-size:0.8em; margin-bottom:5px; text-transform:uppercase;'>Your Brackets:</div>";
								while($b = $b_stmt->fetch(PDO::FETCH_ASSOC)) {
									echo "<div style='display:flex; justify-content:space-between; align-items:center;'>";
									echo "<a href='view.php?id=$b[id]' style='display:block; padding:5px 0; color:var(--text-light); text-decoration:none; font-weight:bold;'>".h(stripslashes($b['name']))."</a>";
									
									// Edit Link if NOT closed
									if( $meta['closed'] == 0 ) {
										echo "<a href='edit.php?id=$b[id]' style='font-size:0.8em; color:var(--accent-orange); text-decoration:none;'>[Edit]</a>";
									}
									echo "</div>";
								}
								
								echo '<hr style="border-color:var(--border-color); margin:10px 0;">';
								echo '<a href="login_check.php?type=logout" style="display:block; color:#fecaca; text-align:center;">Logout</a>';
							}
							?>
						</div>
					</li>
					
      			</ul>
				<script>
				function toggleLogin(e) {
					e.preventDefault();
					var d = document.getElementById('loginDropdown');
					if(d.style.display === 'none') {
						d.style.display = 'block';
					} else {
						d.style.display = 'none';
					}
				}
				// Close if clicked outside
				window.onclick = function(event) {
					if (!event.target.matches('#menu a') && !event.target.closest('#loginDropdown') && !event.target.closest('form')) {
						var d = document.getElementById('loginDropdown');
						if (d.style.display === 'block') {
							d.style.display = 'none';
						}
					}
				}
				</script>
			</div>
		</div>

        <!-- System Broadcast Banner -->
        <div id="broadcast-banner" style="display:none; background:#1e293b; color:white; text-align:center; padding:10px; border-bottom:1px solid #334155; position:absolute; top:0; left:0; width:100%; box-sizing: border-box; z-index:9999; animation: slideDown 0.5s ease-out; box-shadow:0 4px 6px rgba(0,0,0,0.3);">
            <span id="broadcast-icon">📢</span> 
            <span id="broadcast-msg" style="font-weight:bold; margin:0 10px;">System Alert</span>
            <button onclick="dismissBroadcast()" style="background:transparent; border:none; color:#aaa; cursor:pointer; font-size:1.2em; position:absolute; right:15px; top:8px;">&times;</button>
        </div>
        <script>
        // Determine base path based on current location
        const IS_ADMIN = window.location.pathname.includes('/admin/');
        const API_BASE = IS_ADMIN ? '../' : '';

        function checkBroadcast() {
            // Prevent caching with timestamp
            fetch(API_BASE + 'api/get_broadcast.php?t=' + new Date().getTime())
                .then(res => res.json())
                .then(data => {
                    const banner = document.getElementById('broadcast-banner');
                    const msgSpan = document.getElementById('broadcast-msg');
                    
                    if (data.active) {
                        // Check if dismissed
                        const dismissedId = localStorage.getItem('dismissed_broadcast');
                        if (dismissedId != data.id) {
                            msgSpan.innerText = data.message;
                            banner.style.display = 'block';
                            
                            // Style by type
                            if(data.type === 'error') banner.style.background = '#ef4444'; // Red
                            if(data.type === 'success') banner.style.background = '#22c55e'; // Green
                            if(data.type === 'info') banner.style.background = '#3b82f6'; // Blue
                            banner.dataset.id = data.id;
                        }
                    } else {
                        banner.style.display = 'none';
                    }
                })
                .catch(err => console.log('Poll Error', err));
        }

        function dismissBroadcast() {
            const banner = document.getElementById('broadcast-banner');
            banner.style.display = 'none';
            if(banner.dataset.id) {
                localStorage.setItem('dismissed_broadcast', banner.dataset.id);
            }
        }

        // Poll every 60s
        setInterval(checkBroadcast, 60000);
        // Initial check
        document.addEventListener('DOMContentLoaded', checkBroadcast);
        </script>

        <!-- Mobile Drawer & Backdrop -->
        <div id="mobile-backdrop" class="mobile-backdrop" hidden></div>
        <nav id="mobile-drawer" class="mobile-drawer" aria-hidden="true">
            <div class="drawer-header">
                <h3>Menu</h3>
                <button id="mobile-menu-close" data-menu-close="1" aria-label="Close menu">&times;</button>
            </div>
            
            <div class="drawer-auth">
                <?php if(!is_logged_in()) { ?>
                    <a href="login.php" class="drawer-btn primary">Login</a>
                    <a href="register.php" class="drawer-btn secondary">Register</a>
                <?php } else { ?>
                    <?php 
                    // Brief user info if available
                    $uName = 'User';
                    if($user_id) {
                         // We might need to re-query if not in scope, but typically $user_id is set at top
                         // Simple fallback
                    }
                    ?>
                    <div style="margin-bottom:10px; color:var(--text-muted);">Welcome back!</div>
                    <a href="dashboard.php" class="drawer-btn primary">Dashboard</a>
                    <?php if(is_admin()) { ?>
                    <a href="admin/" class="drawer-btn accent">Admin Area</a>
                    <?php } ?>
                    <a href="profile.php" class="drawer-link">My Profile</a>
                    <a href="login_check.php?type=logout" class="drawer-link" style="color:#fecaca;">Logout</a>
                <?php } ?>
            </div>

            <!-- Nav Links cloned from #menu via JS -->
            <div id="drawer-nav-container"></div>
        </nav>

<?php include("includes/toast_logic.php"); ?>
