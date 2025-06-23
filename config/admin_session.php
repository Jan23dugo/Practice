<?php
// Load session configuration first
require_once __DIR__ . '/session_config.php';

// Admin session management functions

function checkAdminSession() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if admin is logged in
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        // Redirect to login page
        header("Location: admin_login.php");
        exit();
    }
    
    // Check for session timeout (8 hours = 28800 seconds)
    $timeout_duration = 28800; // 8 hours
    
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        
        if ($inactive_time > $timeout_duration) {
            // Session has expired
            session_unset();
            session_destroy();
            header("Location: admin_login.php?timeout=1");
            exit();
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID periodically for security (every 30 minutes)
    if (!isset($_SESSION['session_regenerated'])) {
        $_SESSION['session_regenerated'] = time();
    } elseif (time() - $_SESSION['session_regenerated'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['session_regenerated'] = time();
    }
}

function extendAdminSession() {
    // This function can be called via AJAX to extend session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['admin_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    return false;
}

function getSessionTimeRemaining() {
    if (!isset($_SESSION['last_activity'])) {
        return 0;
    }
    
    $timeout_duration = 28800; // 8 hours
    $inactive_time = time() - $_SESSION['last_activity'];
    $remaining = $timeout_duration - $inactive_time;
    
    return max(0, $remaining);
}
?> 