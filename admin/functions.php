<?php
if (!defined('ADMIN_FUNCTIONS_LOADED')) {
define('ADMIN_FUNCTIONS_LOADED', true);

if (session_status() === PHP_SESSION_NONE) {
    // Phase 0.5: Hardening (24 Hour Sessions)
    ini_set('session.gc_maxlifetime', 86400); 
    ini_set('session.cookie_lifetime', 86400);
    ini_set('session.use_strict_mode', 1);

    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Core Security Includes
require_once __DIR__ . '/CSRF.php';

// Quick Output Sanitization Helper
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Versioning
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.0.0');
}

// Security Headers
// Security Headers (CLI Guard)
if (php_sapi_name() !== 'cli') {
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
}

include_once __DIR__ . '/../includes/bracket_helpers.php';

function validatecookie()
{
    // Use the centralized strict check
    if (is_admin()) {
        return;
    }

    // Not authorized.
    // If logged in as User but not Admin (e.g. Player), send to Home
    if (isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit();
    }

    // If not logged in at all, redirect to Unified Login with return path
    $next = urlencode($_SERVER['REQUEST_URI']);
    header("Location: ../login.php?next=$next");
    exit();
}

function check_admin_auth($required_role = 'limited')
{
    // 1. Determine Effective Identity
    $current_role = null;

    // Priority: Break-Glass Admin > Unified User
    if (isset($_SESSION['admin_user']) && isset($_SESSION['admin_user']['role'])) {
        $current_role = $_SESSION['admin_user']['role'];
    } elseif (isset($_SESSION['user_role'])) {
        $current_role = $_SESSION['user_role'];
    }

    // 2. Validate Role Existence & Allowlist
    // Only these specific roles are allowed ANY access to /admin/
    $allowed_admin_roles = ['super', 'limited', 'pay'];
    
    if (!$current_role || !in_array($current_role, $allowed_admin_roles, true)) {
        // If logged in as player (or unknown), redirect to home. Otherwise login.
        if (isset($_SESSION['user_id'])) {
             header("Location: ../index.php");
        } else {
             $next = urlencode($_SERVER['REQUEST_URI']);
             header("Location: ../login.php?next=$next");
        }
        exit();
    }

    // 3. Hierarchy Check (Strict Allowlist per Tier)
    // $required_role map:
    // 'super'   => only 'super'
    // 'limited' => 'super', 'limited'
    // 'pay'     => 'super', 'pay' (assuming Pay Editor is independent)
    // 'any'     => 'super', 'limited', 'pay'
    
    $authorized = false;

    switch ($required_role) {
        case 'super':
            if ($current_role === 'super') $authorized = true;
            break;
        case 'limited':
            if (in_array($current_role, ['super', 'limited'], true)) $authorized = true;
            break;
        case 'pay':
            if (in_array($current_role, ['super', 'pay'], true)) $authorized = true;
            break;
        default: // 'any' or 'limited' legacy default
            // Already checked against $allowed_admin_roles above
            $authorized = true; 
            break;
    }

    if (!$authorized) {
        // User is an ADMIN, but doesn't have the required level (e.g. Limited trying to access Super page)
        // Do NOT redirect to home, just deny access.
        header('HTTP/1.0 403 Forbidden');
        die("<h1>403 Access Denied</h1><p>You do not have permission to view this page.</p><p><a href='index.php'>Return to Dashboard</a></p>");
    }
}

function log_admin_action($action_type, $details)
{
    global $db;
    
    $actor_id = null;
    $actor_type = 'unknown';
    $actor_role = 'unknown';

    if (isset($_SESSION['admin_user'])) {
        $actor_id = $_SESSION['admin_user']['id'];
        $actor_type = 'break_glass_admin';
        $actor_role = $_SESSION['admin_user']['role'] ?? 'unknown';
    } elseif (isset($_SESSION['user_id'])) {
        $actor_id = $_SESSION['user_id'];
        $actor_type = 'unified_user';
        $actor_role = $_SESSION['user_role'] ?? 'unknown';
    }

    if ($actor_id) {
        $ip = $_SERVER['REMOTE_ADDR'];
        // Prefix details with explicit actor info for audit clarity
        // Format: [Type:ID] (Role) Details
        $log_details = sprintf("[%s:%s] (%s) %s", $actor_type, $actor_id, $actor_role, $details);

        $stmt = $db->prepare("INSERT INTO admin_logs (admin_id, action_type, details, ip_address) VALUES (?, ?, ?, ?)");
        // Note: admin_id column is used for the ID, regardless of source table.
        $stmt->execute([$actor_id, $action_type, $log_details, $ip]);
    }
}

function is_admin() {
    $allowed = ['super', 'limited', 'pay'];
    
    // Check Break-Glass
    if (isset($_SESSION['admin_user']['role']) && in_array($_SESSION['admin_user']['role'], $allowed, true)) {
        return true;
    }
    
    // Check Unified
    if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $allowed, true)) {
        return true;
    }
    
    return false;
}

/* CSRF Protection Helpers */
function generate_csrf_token() {
    return CSRF::generate();
}

function verify_csrf_token() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die("CSRF Validation Failed. Please refresh the page and try again.");
    }
}

function csrf_field() {
    echo CSRF::input();
}





function getLoserMap($db)
{
	$loser_query = "SELECT * FROM `master` WHERE `id`=3"; //select losers
	$stmt = $db->query($loser_query);
	$loser_data = $stmt->fetch(PDO::FETCH_ASSOC);
	
	$loserMap = array();
	
	for( $i=1; $i<=64; $i++ )
	{	
		if( isset($loser_data[$i]) && $loser_data[$i] != NULL )
		{
			$loserMap[$loser_data[$i]] = true;
		}
	}
	
	return $loserMap;
}

function timeBetween($start,$end,$after=' ago',$color=1){
	//both times must be in seconds
	$time = $end - $start;
	if($time <= 60){
		if($color==1){
			return '<span style="color:#009900;">Online';
		}else{
			return 'Online';
		}
	}
	if(60 < $time && $time <= 3600){
		$minutes = round($time/60,0);
		if ($minutes < 2)
			return $minutes.' minute'.$after;
		else
			return $minutes.' minutes'.$after;
	}
	if(3600 < $time && $time <= 86400){
		$hours = round($time/3600,0);
		if ($hours < 2)
			return $hours.' hour'.$after;
		else
			return $hours.' hours'.$after;
	}
	if(86400 < $time && $time <= 604800){
		$days = round($time/86400,0);
		if ($days < 2)
			return $days.' day'.$after;
		else
			return $days.' days'.$after;
	}
	if(604800 < $time && $time <= 2592000){
		$weeks = round($time/604800,0);
		if ($weeks < 2)
			return $weeks.' week'.$after;
		else
			return $weeks.' weeks'.$after;
	}
	if(2592000 < $time && $time <= 29030400){
		$months = round($time/2592000,0);
		if ($months < 2)
			return $months.' month'.$after;
		else
			return $months.' months'.$after;
	}
	if($time > 29030400){
		return 'More than a year'.$after;
	}
}





function getParentGraph()
{
	$childGraph = getChildGraph();
	
	$parentGraph = array();
	
	for( $i=63; $i >= 33; $i-- )
	{
		$parentGraph[$childGraph[$i][0]] = $i;
		$parentGraph[$childGraph[$i][1]] = $i;
	}
	
	return $parentGraph;
}

function ordinal_suffix($value)
{
	$suffix = "";
	if( is_numeric($value) )
	{
		if(substr($value, -2, 2) == 11 || substr($value, -2, 2) == 12 || substr($value, -2, 2) == 13){
			$suffix = "th";
		}
		else if (substr($value, -1, 1) == 1){
			$suffix = "st";
		}
		else if (substr($value, -1, 1) == 2){
			$suffix = "nd";
		}
		else if (substr($value, -1, 1) == 3){
			$suffix = "rd";
		}
		else {
			$suffix = "th";
		}
	}
	return $value . $suffix;
}


function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function getThemes() {
    global $db;
    
    // Safety check just in case function calls happen before DB is possibly init in some weird edge cases
    if(!isset($db)) return [];

    try {
        $stmt = $db->query("SELECT * FROM `themes` ORDER BY `group_name` ASC, `name` ASC");
        $results = [];
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['theme_key'];
            $results[$key] = [
                'name'    => $row['name'],
                'accent'  => $row['accent'],
                'header1' => $row['header1'],
                'header2' => $row['header2'],
                'bg1'     => $row['bg1'],
                'bg2'     => $row['bg2'],
                'group'   => $row['group_name']
            ];
        }
        
        // Fallback if table is empty
        if(empty($results)) {
             return ['default' => ['name' => 'Default (Midnight)', 'accent' => '#f97316', 'header1' => '#0f172a', 'header2' => '#1e293b', 'bg1' => '#0f172a', 'bg2' => '#1e293b', 'group' => 'System']];
        }

        return $results;

    } catch (Exception $e) {
        // Fallback in case table doesn't exist yet
        return ['default' => ['name' => 'Default (Midnight)', 'accent' => '#f97316', 'header1' => '#0f172a', 'header2' => '#1e293b', 'bg1' => '#0f172a', 'bg2' => '#1e293b', 'group' => 'System']];
    }
}

// Simulator Logic
function simulateLeaderboard($db, $if_data) {
    // 1. Fetch Current Master Data (Real Results)
    $stmt = $db->query("SELECT * FROM `master` WHERE `id`=2");
    $master = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Merge Scenario (Override Master with If-Data)
    if(is_array($if_data)) {
        foreach($if_data as $gid => $team) {
            if(!empty($team)) {
                $master[$gid] = $team;
            }
        }
    }
    
    // 3. Prep Maps
    $seedMap = getSeedMap($db);
    $roundMap = getRoundMap();
    $points = getScoringArray($db, 'main'); 
    
    // 4. Score All Brackets
    $stmt = $db->query("SELECT id, name, person, email, paid, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`, `13`, `14`, `15`, `16`, `17`, `18`, `19`, `20`, `21`, `22`, `23`, `24`, `25`, `26`, `27`, `28`, `29`, `30`, `31`, `32`, `33`, `34`, `35`, `36`, `37`, `38`, `39`, `40`, `41`, `42`, `43`, `44`, `45`, `46`, `47`, `48`, `49`, `50`, `51`, `52`, `53`, `54`, `55`, `56`, `57`, `58`, `59`, `60`, `61`, `62`, `63` FROM brackets");
    
    $results = [];
    
    while($b = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $score = 0;
        
        for($j=1; $j<64; $j++) {
            $pick = $b[$j];
            $result = $master[$j];
            $r = $roundMap[$j];
            
            if(empty($pick)) continue;
            
            $seed = isset($seedMap[$pick]) ? $seedMap[$pick] : 1;
            $pts = isset($points[$seed][$r]) ? $points[$seed][$r] : 0;
            
            if(!empty($result) && $pick == $result) {
                $score += $pts;
            }
        }
        $results[] = [
            'id' => $b['id'],
            'name' => $b['name'],
            'person' => $b['person'],
            'score' => $score,
            'email' => $b['email']
        ];
    }
    
    // 5. Sort DESC
    usort($results, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    return $results;
}

} // End Guard

