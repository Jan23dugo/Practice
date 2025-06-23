<?php
// Session configuration - MUST be loaded before any session_start() calls

// Only set these if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    // Set session timeout to 8 hours (28800 seconds)
    ini_set('session.gc_maxlifetime', 28800);
    ini_set('session.cookie_lifetime', 28800);

    // Set session cookie parameters for security
    session_set_cookie_params([
        'lifetime' => 28800, // 8 hours
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}
?> 