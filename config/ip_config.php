<?php
// Define allowed IP ranges for university computers
define('ALLOWED_IP_RANGES', [
    '192.168.100.6/32',    // Your shared IP address
]);

function isUniversityNetwork() {
    $clientIP = $_SERVER['REMOTE_ADDR'];
    
    // Debug line to see what IP is trying to access
    error_log("Access attempt from IP: " . $clientIP);
    
    if (!ipInRange($clientIP, $range)) {
        return false;
    }
    
    // Additional security checks could go here
    return true;
}

function ipInRange($ip, $range) {
    list($range, $netmask) = explode('/', $range, 2);
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    
    return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}

// Add additional security function
function verifyExamAccess() {
    if (!isUniversityNetwork()) {
        return false;
    }
    
    // Additional checks could include:
    // 1. Verify if student is scheduled for exam
    // 2. Check exam time window
    // 3. Verify one active session only
    // 4. Check for proper exam registration
    
    return true;
} 