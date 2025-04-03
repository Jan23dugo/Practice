<?php
// Set error reporting to maximum
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP Error Display</h1>";

// Check PHP error log
$error_log_path = ini_get('error_log');
echo "<h2>PHP Error Log Path</h2>";
echo "<p>Error log path: " . ($error_log_path ? $error_log_path : "Not configured") . "</p>";

// Check if we can read the error log
if ($error_log_path && file_exists($error_log_path) && is_readable($error_log_path)) {
    echo "<h2>Recent PHP Errors</h2>";
    
    // Get the last 50 lines of the error log
    $log_content = file($error_log_path);
    $last_lines = array_slice($log_content, -50);
    
    echo "<pre style='background:#f5f5f5; padding:10px; overflow:auto; max-height:500px;'>";
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p>Cannot read PHP error log. It might not exist or might not be readable.</p>";
}

// Check our custom error logs
$logDir = __DIR__ . '/logs';
echo "<h2>Custom Error Logs</h2>";

if (file_exists($logDir) && is_dir($logDir)) {
    $logFiles = glob($logDir . '/*.txt');
    
    if (!empty($logFiles)) {
        echo "<ul>";
        foreach ($logFiles as $file) {
            $filename = basename($file);
            $filesize = filesize($file);
            echo "<li><a href='?log=" . urlencode($filename) . "'>$filename</a> - " . round($filesize / 1024, 2) . " KB</li>";
        }
        echo "</ul>";
        
        if (isset($_GET['log'])) {
            $requestedLog = $_GET['log'];
            $logPath = $logDir . '/' . $requestedLog;
            
            if (file_exists($logPath) && is_file($logPath) && is_readable($logPath)) {
                echo "<h3>Contents of $requestedLog</h3>";
                echo "<pre style='background:#f5f5f5; padding:10px; overflow:auto; max-height:500px;'>";
                echo htmlspecialchars(file_get_contents($logPath));
                echo "</pre>";
            } else {
                echo "<p style='color:red'>Invalid log file requested or file is not readable!</p>";
            }
        }
    } else {
        echo "<p>No log files found in the logs directory.</p>";
    }
} else {
    echo "<p>Logs directory does not exist or is not readable.</p>";
}

// Test session functionality
echo "<h2>Session Test</h2>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['test_value'] = "This is a test session value set at " . date('Y-m-d H:i:s');
echo "<p>Session test value set. Refresh the page to see if it persists.</p>";

if (isset($_SESSION['test_value'])) {
    echo "<p>Current session test value: " . htmlspecialchars($_SESSION['test_value']) . "</p>";
}

echo "<h3>All Session Variables</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check session configuration
echo "<h2>Session Configuration</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";

$session_settings = [
    'session.save_path' => ini_get('session.save_path'),
    'session.name' => ini_get('session.name'),
    'session.save_handler' => ini_get('session.save_handler'),
    'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'session.cookie_path' => ini_get('session.cookie_path'),
    'session.cookie_domain' => ini_get('session.cookie_domain'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    'session.use_cookies' => ini_get('session.use_cookies'),
    'session.use_only_cookies' => ini_get('session.use_only_cookies')
];

foreach ($session_settings as $setting => $value) {
    echo "<tr><td>$setting</td><td>$value</td></tr>";
}
echo "</table>";

// Check if the session directory is writable
$session_save_path = ini_get('session.save_path');
if (!empty($session_save_path)) {
    echo "<p>Session save path: $session_save_path</p>";
    if (is_dir($session_save_path)) {
        if (is_writable($session_save_path)) {
            echo "<p style='color:green'>Session directory is writable.</p>";
        } else {
            echo "<p style='color:red'>Session directory is not writable! This will cause session problems.</p>";
        }
    } else {
        echo "<p style='color:red'>Session directory does not exist!</p>";
    }
}

// Check redirect functionality
echo "<h2>Redirect Test</h2>";
echo "<p>Click the button below to test if redirects are working properly:</p>";
echo "<form method='post'>";
echo "<button type='submit' name='test_redirect'>Test Redirect</button>";
echo "</form>";

if (isset($_POST['test_redirect'])) {
    // Set a session variable to check after redirect
    $_SESSION['redirect_test'] = true;
    
    // Redirect to this same page with a parameter
    header("Location: error_display.php?redirect_test=1");
    exit;
}

if (isset($_GET['redirect_test']) && isset($_SESSION['redirect_test'])) {
    echo "<p style='color:green'>Redirect test successful!</p>";
    unset($_SESSION['redirect_test']);
}

// Display phpinfo
echo "<h2>PHP Information</h2>";
echo "<p><a href='?phpinfo=1'>Click here to view PHP information</a></p>";

if (isset($_GET['phpinfo'])) {
    phpinfo();
    exit;
}
?> 