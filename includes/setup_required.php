<?php
// Determine correct relative path to installer based on where we are included from
$installerPath = 'admin/install.php';
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
    $installerPath = 'install.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Setup Required | Bracket Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background-color: #0f172a;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            text-align: center;
            background: rgba(30, 41, 59, 0.7);
            padding: 40px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 400px;
            width: 100%;
        }
        h1 { margin: 0 0 15px 0; color: #f97316; }
        p { color: #cbd5e1; margin-bottom: 25px; line-height: 1.5; }
        .btn {
            display: inline-block;
            background: #f97316;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn:hover { background: #ea580c; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Setup Required</h1>
        <p>The application has not been configured yet.</p>
        <a href="<?php echo $installerPath; ?>" class="btn">Run Installer</a>
    </div>
</body>
</html>
