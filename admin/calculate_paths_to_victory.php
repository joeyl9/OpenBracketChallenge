<?php
// admin/calculate_paths_to_victory.php
// Production Entry Point for V2 Calculation Engine

// 1. Disable Buffering & Compression immediately
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(1);

require_once 'database.php';
require_once 'functions.php';
require_once '_calc_runners.php'; // Contains v2_calc_run

validatecookie();

// Prevent timeouts
set_time_limit(0);
ignore_user_abort(true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Calculating...</title>
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        body { background: #0f172a; color: white; display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; margin:0; }
        .loader { text-align: center; background: rgba(255,255,255,0.05); padding: 40px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); min-width: 400px; }
        .spinner { font-size: 3rem; color: #f97316; animation: spin 1s linear infinite; margin-bottom: 20px; }
        .status { font-size: 1.25rem; font-weight: 600; margin-bottom: 10px; }
        .detail { color: #94a3b8; font-size: 0.9rem; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        .success { color: #22c55e; animation: none; }
        .error { color: #ef4444; animation: none; }
    </style>
</head>
<body>
    <div class="loader">
        <i class="fas fa-circle-notch spinner" id="icon"></i>
        <div class="status" id="status">Initializing Engine...</div>
        <div class="detail" id="detail">Preparing V2 Calculation...</div>
    </div>

<?php
// CRITICAL: Send padding to force browser to render initial chunk
echo str_pad("<!-- flush clutter -->", 4096);
flush();

// UX Delay: Force "Initializing..." to be visible for 1.2 seconds
usleep(1200000); 

// Determine Mode
$mode = 'full'; // Default changed from 'smart' to 'full' to force cache regeneration
if (isset($_GET['mode']) && in_array($_GET['mode'], ['quick', 'full', 'smart'])) {
    $mode = $_GET['mode'];
}

// Truncate logic default: true for Admin Calc
$truncate = true;
if (isset($_GET['truncate']) && $_GET['truncate'] === 'false') {
    $truncate = false;
    error_log("Web Trigger: truncate=false (Explicit Override)");
} else {
    error_log("Web Trigger: Defaulting to truncate=true, allow_web_truncate=true");
}

// Set Max Runtime based on mode (900s for Smart/Full, 300s for Quick)
$maxRuntime = ($mode === 'quick') ? 300 : 900;

echo "<script>
    document.getElementById('status').innerText = 'Running Calculation...';
    document.getElementById('detail').innerText = 'Processing brackets...';
</script>";
echo str_pad(" ", 1024);
flush();

// Run Calculation
try {
    // Pass Options to Runner
    $runOpts = [
        'mode' => $mode,
        'truncate' => $truncate,
        'allow_web_truncate' => $truncate,
        'max_runtime' => $maxRuntime
    ];

    $result = v2_calc_run($db, $runOpts);
    
    if (isset($result['error'])) {
        throw new Exception($result['error']);
    }
    
    $runtime = number_format($result['runtime_ms'], 2);
    $displayMode = strtoupper($result['mode_executed']);
    
    $_SESSION['msg'] = "Calculations Complete! ($displayMode took $runtime ms)";
    
    // SUCCESS UI UPDATE
    echo "<script>
        document.getElementById('icon').className = 'fas fa-check-circle spinner success';
        document.getElementById('status').innerText = 'Complete!';
        document.getElementById('detail').innerText = '$displayMode finished in $runtime ms.';
    </script>";
    flush();
    
    // UX Delay: Force "Complete!" to be visible for 1.5 seconds before redirect
    usleep(1500000); 
    
    echo "<script>window.location.href = 'index.php';</script>";
    
} catch (Exception $e) {
    $err = addslashes($e->getMessage());
    echo "<script>
        document.getElementById('icon').className = 'fas fa-exclamation-triangle spinner error';
        document.getElementById('status').innerText = 'Calculation Failed';
        document.getElementById('detail').innerText = '$err';
    </script>";
    exit();
}

echo "<?php include('footer.php'); ?>
</body>
</html>";
?>


