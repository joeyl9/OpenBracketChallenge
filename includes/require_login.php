<?php
/**
 * require_login.php — Session-only auth gate for public pages.
 *
 * Include at the top of any page that requires an authenticated user.
 * Redirects to login.php if no valid session exists.
 *
 * After inclusion the calling script can rely on:
 *   $auth_user_id    — int  (users.id)
 *   $auth_user_email — string
 */

// Ensure functions.php (which starts the session) is loaded.
if (!defined('ADMIN_FUNCTIONS_LOADED')) {
    require_once __DIR__ . '/../admin/functions.php';
}

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$auth_user_id    = (int) $_SESSION['user_id'];
$auth_user_email = $_SESSION['useremail'] ?? '';
