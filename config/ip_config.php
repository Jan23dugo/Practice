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

// IP Address Configuration
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function isIPVerified($conn, $ip_address) {
    try {
        $query = "SELECT 1 FROM verified_ips WHERE ip_address = ? AND status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $ip_address);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    } catch (Exception $e) {
        error_log("Error checking IP verification: " . $e->getMessage());
        return false;
    }
}

// Function to check if current user's IP is verified
function isCurrentIPVerified($conn) {
    try {
        // First, check if there are any IP restrictions configured
        $count_query = "SELECT COUNT(*) as total FROM verified_ips WHERE status = 'active'";
        $count_result = $conn->query($count_query);
        $count_row = $count_result->fetch_assoc();
        
        // If no IP restrictions are configured, allow access from anywhere
        if ($count_row['total'] == 0) {
            return true;
        }
        
        // If IP restrictions are configured, check if current IP is in the list
        $client_ip = getClientIP();
        return isIPVerified($conn, $client_ip);
    } catch (Exception $e) {
        error_log("Error checking IP verification: " . $e->getMessage());
        // If there's an error and we can't determine restrictions, deny access for security
        return false;
    }
}
?> 