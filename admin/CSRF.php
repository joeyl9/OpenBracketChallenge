<?php

class CSRF {
    
    /**
     * Generate a token and store it in the session.
     * @return string The generated token.
     */
    public static function generate() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Check if the provided token matches the session token.
     * @param string $token The token from the form submission.
     * @return bool True if valid, False otherwise.
     */
    public static function check($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Helper to render the hidden input field.
     */
    public static function input() {
        $token = self::generate();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}

