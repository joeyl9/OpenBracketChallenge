<?php
/**
 * require_admin.php — Session-only auth gate for admin scripts.
 *
 * Include at the top of any admin/ file that needs protection.
 * Equivalent to: validatecookie(); check_admin_auth('super');
 *
 * Usage:
 *   require_once __DIR__ . '/require_admin.php';          // defaults to 'super'
 *   — OR set $admin_role before including:
 *   $admin_role = 'limited'; require_once __DIR__ . '/require_admin.php';
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

validatecookie();

// Default to 'super' if caller didn't set $admin_role
if (!isset($admin_role)) {
    $admin_role = 'super';
}

check_admin_auth($admin_role);

