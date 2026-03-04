<?php
include("database.php");
include_once("functions.php");
$query = "SELECT * FROM `meta` WHERE `id`=1";
$stmt = $db->query($query);
$meta = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Bracket Challenge Admin</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta charset="UTF-8">
	<meta name="author" content="Matt Felser, Brian Battaglia, John Holder, Robert Jailall" />
	<link rel="stylesheet" type="text/css" href="../images/style.css?v=<?php echo filemtime(__DIR__ . '/../images/style.css'); ?>" media="all" />
	<link rel="shortcut icon" href="../images/favicon.ico">
    
    <?php
    // Ensure Session is Started (Hardening)
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start(); 
    }

    // Theme Logic (Hardened & Cached)
    $current_theme_key = 'default';
    
    // 1. Check Session Cache (fastest)
    if (isset($_SESSION['theme']) && !empty($_SESSION['theme'])) {
        $current_theme_key = $_SESSION['theme'];
    } 
    // 2. Resolve via User ID (Unified Session > Legacy Cookie)
    else {
        $uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        if ($uid) {
            $t_stmt = $db->prepare("SELECT theme FROM users WHERE id = ?");
            $t_stmt->execute([$uid]);
            $t_res = $t_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($t_res && !empty($t_res['theme'])) {
                $current_theme_key = $t_res['theme'];
                // Cache it
                $_SESSION['theme'] = $current_theme_key;
            }
        }
        // 3. Break-Glass Fallback
        elseif (isset($_SESSION['admin_user'])) {
             // Admin User (Break-Glass) with no unified session -> Use Default
             $current_theme_key = 'default';
        }
    }
    
    $all_themes = getThemes();
    if (!array_key_exists($current_theme_key, $all_themes)) {
        $current_theme_key = 'default';
    }
    $active_theme = $all_themes[$current_theme_key];
    
    // Calculate Text Contrast based on Brightness
    $hex = $active_theme['accent'];
    $hex = ltrim($hex, '#');
    // Handle shorthand hex
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
    $accent_text = ($brightness > 150) ? '#000000' : '#FFFFFF';
    ?>
    
    <style>
    :root {
        /* Theme Core Colors */
        --accent-orange: <?php echo $active_theme['accent']; ?>;
        --accent-orange-hover: <?php echo $active_theme['accent']; ?>;
        --accent-text: <?php echo $accent_text; ?>;
        
        /* Full Site Theming Overrides */
        --primary-blue: <?php echo isset($active_theme['bg1']) ? $active_theme['bg1'] : $active_theme['header2']; ?> !important;
        --secondary-blue: <?php echo isset($active_theme['bg2']) ? $active_theme['bg2'] : $active_theme['header1']; ?> !important;
        
        /* Alias for Components */
        --bg-secondary: var(--secondary-blue);
        --bg-primary: var(--primary-blue);
        --text-light: #f8fafc;
        --text-muted: #94a3b8;
        
        --border-color: rgba(255,255,255,0.1) !important;
    }
    
    /* Header Override */
    #header {
        background: linear-gradient(135deg, <?php echo $active_theme['header1']; ?>, <?php echo $active_theme['header2']; ?>) !important;
        border-bottom-color: var(--accent-orange) !important;
    }
    
    /* Specific Element Overrides */
    h2, h3, .dashboard-card i {
        color: var(--accent-orange) !important;
    }
    .matchup .team.selected {
        background-color: var(--accent-orange) !important;
        color: var(--accent-text) !important;
        font-weight: bold;
    }
    a:hover {
        color: var(--accent-orange) !important;
    }
    input[type="submit"], button:not([class*="tox-"]):not(.dashboard-card), .btn {
        background: var(--accent-orange) !important;
        color: var(--accent-text) !important;
    }
    
    /* Table Headers */
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
	
	<?php
	//if this is the submit or what-if page, include the necessary javascript
	if(strpos($_SERVER['PHP_SELF'],"contactall.php") !== FALSE ) {
	?>
	<!-- for emailer ajax -->
	<script type="text/javascript" src="../js/emailall.js?v=<?php echo filemtime(__DIR__ . '/../js/emailall.js'); ?>&v=B" defer></script>
	<?php } ?>

    <!-- Global Scripts (Moved out of conditional) -->
	<script type="text/javascript" src="../js/jquery-3.7.1.min.js"></script>
	<script type="text/javascript" src="../js/jquery.dataTables.min.js"></script>

	<script type="text/javascript" src="../js/script.js?v=<?php echo filemtime(__DIR__ . '/../js/script.js'); ?>&v=B" defer></script>

    <?php
    // TinyMCE Restoration (Local Only) - Blog & Rules Pages
    $current_page_tmce = basename($_SERVER['PHP_SELF']);
    if ($current_page_tmce == 'blog.php' || $current_page_tmce == 'rules.php') {
        // Construct CSS variables for independent iframe context
        // These match the values defined in the :root style block above
        $tmce_bg1 = isset($active_theme['bg1']) ? $active_theme['bg1'] : $active_theme['header2'];
        $tmce_bg2 = isset($active_theme['bg2']) ? $active_theme['bg2'] : $active_theme['header1'];
        $tmce_accent = $active_theme['accent'];
        
        $tmce_css_vars = ":root {";
        $tmce_css_vars .= "--primary-blue: {$tmce_bg1};";
        $tmce_css_vars .= "--secondary-blue: {$tmce_bg2};";
        $tmce_css_vars .= "--accent-orange: {$tmce_accent};";
        $tmce_css_vars .= "--text-light: #f8fafc;";
        $tmce_css_vars .= "--text-muted: #94a3b8;";
        $tmce_css_vars .= "--border-color: rgba(255,255,255,0.1);";
        $tmce_css_vars .= "}";
    ?>
    <!-- TinyMCE UI Theme Override -->
    <link rel="stylesheet" type="text/css" href="../css/tinymce-theme.css?v=<?php echo filemtime(__DIR__ . '/../css/tinymce-theme.css'); ?>" />

    <script type="text/javascript" src="../js/lib/tinymce/tinymce.min.js"></script>
    <script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        tinymce.init({
            selector: 'textarea[name="content"], textarea[name="rules"]',
            license_key: 'gpl',
            promotion: false,
            branding: false,
            plugins: 'lists link table code wordcount',
            toolbar: 'undo redo | blocks | bold italic | bullist numlist | link | table | code',
            menubar: false,
            
            // Theme Integration
            skin: 'oxide-dark', // Base Dark Theme
            content_css: '../css/tinymce-content-theme.css?v=<?php echo filemtime(__DIR__ . '/../css/tinymce-content-theme.css'); ?>',
            content_style: '<?php echo $tmce_css_vars; ?>'
        });
    });
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
					<h2>Site Administration</h2>
				</div>
			</div>
			<div class="version-badge">
				<!-- Current Project Link -->
				<a href="https://github.com/joeyl9/OpenBracketChallenge" target="_blank" class="current-project" title="Current Maintained Project">
					<i class="fa-brands fa-github"></i> &copy; <?php echo date("Y"); ?> OpenBracketChallenge
				</a>
			</div>
            <!-- Mobile Trigger -->
            <button id="mobile-menu-btn" onclick="console.log('Admin Button Clicked Check');" aria-label="Open menu" aria-controls="mobile-drawer" aria-expanded="false">
                <span class="hamburger"><span></span><span></span><span></span></span>
            </button>
		</div>
	</div>
	
		<div id="subheader">
			<div id="menu">
			  	<ul>
					<li><a href="../index.php">HOME</a></li>
					
					<?php if( $meta['sweet16Competition'] == true ) { ?>
						<li><a href="../submit_second_chance.php">CREATE BRACKET</a></li>
					<?php }else{ ?>
					<li><a href="../submit.php">CREATE BRACKET</a></li>
					<?php } ?>
					<li><a href="../rules.php">RULES</a></li>
					<li><a href="../choose.php">STANDINGS</a></li>
					<?php if($meta['cost'] != 0) { ?>
					<li><a href="paid.php">PAYMENT TRACKER</a></li>
					<?php } ?>
					<?php if(is_admin()) { ?>
					<li><a href="../admin/">ADMIN AREA</a></li>
					<?php } ?>					
      			</ul>
			</div>
		</div>

        <!-- Mobile Drawer & Backdrop -->
        <div id="mobile-backdrop" class="mobile-backdrop" hidden></div>
        <nav id="mobile-drawer" class="mobile-drawer" aria-hidden="true">
            <div class="drawer-header">
                <h3>Menu</h3>
                <button id="mobile-menu-close" data-menu-close="1" aria-label="Close menu">&times;</button>
            </div>
            
            <div class="drawer-auth">
                <div style="margin-bottom:10px; color:var(--text-muted);">Admin Mode</div>
                <a href="./" class="drawer-btn primary">Admin Home</a>
                <a href="../index.php" class="drawer-btn secondary">Public Site</a>
                <a href="../profile.php" class="drawer-link">My Profile</a>
                <a href="../login_check.php?type=logout" class="drawer-link" style="color:#fecaca;">Logout</a>
            </div>

            <!-- Nav Links cloned from #menu via JS -->
            <div id="drawer-nav-container"></div>
        </nav>

<?php include("../includes/toast_logic.php"); ?>
<?php include("../includes/confirm_modal.php"); ?>

