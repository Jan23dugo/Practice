<?php
// Start session
session_start();

// Set error reporting to maximum
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PUP CCIS Qualifying Exam Diagnostic Tool</h1>";

// Check if logs directory exists and is writable
$logDir = __DIR__ . '/logs';
echo "<h2>Log Directory Check</h2>";
if (!file_exists($logDir)) {
    echo "<p style='color:red'>Logs directory does not exist. Creating it now...</p>";
    if (mkdir($logDir, 0755, true)) {
        echo "<p style='color:green'>Logs directory created successfully at: $logDir</p>";
    } else {
        echo "<p style='color:red'>Failed to create logs directory. Check permissions.</p>";
    }
} else {
    echo "<p style='color:green'>Logs directory exists at: $logDir</p>";
    if (is_writable($logDir)) {
        echo "<p style='color:green'>Logs directory is writable.</p>";
    } else {
        echo "<p style='color:red'>Logs directory is not writable. Please check permissions.</p>";
    }
}

// Check for existing log files
echo "<h2>Existing Log Files</h2>";
$logFiles = glob($logDir . '/error_log_*.txt');
if (!empty($logFiles)) {
    echo "<ul>";
    foreach ($logFiles as $file) {
        $filename = basename($file);
        $filesize = filesize($file);
        echo "<li><a href='?view_log=" . urlencode($filename) . "'>$filename</a> - " . round($filesize / 1024, 2) . " KB</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No log files found.</p>";
}

// Display log file contents if requested
if (isset($_GET['view_log'])) {
    $requestedLog = $_GET['view_log'];
    $logPath = $logDir . '/' . $requestedLog;
    
    if (file_exists($logPath) && is_file($logPath) && strpos($requestedLog, 'error_log_') === 0) {
        echo "<h2>Contents of $requestedLog</h2>";
        echo "<pre style='background:#f5f5f5; padding:10px; overflow:auto; max-height:500px;'>";
        echo htmlspecialchars(file_get_contents($logPath));
        echo "</pre>";
    } else {
        echo "<p style='color:red'>Invalid log file requested!</p>";
    }
}

// Check database connection
echo "<h2>Database Connection Test</h2>";
try {
    // Include database configuration
    include('config/config.php');
    
    if (!isset($conn)) {
        echo "<p style='color:red'>Database connection variable (\$conn) is not set in config.php</p>";
    } else {
        if ($conn->connect_error) {
            echo "<p style='color:red'>Database connection failed: " . $conn->connect_error . "</p>";
        } else {
            echo "<p style='color:green'>Database connection successful!</p>";
            
            // Test query
            $result = $conn->query("SELECT 1");
            if ($result) {
                echo "<p style='color:green'>Test query executed successfully.</p>";
            } else {
                echo "<p style='color:red'>Test query failed: " . $conn->error . "</p>";
            }
            
            // Check tables
            $tables = [
                'students_registerqe',
                'matched_courses',
                'coded_courses',
                'university_grading_systems'
            ];
            
            echo "<h3>Database Tables Check</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Table Name</th><th>Status</th><th>Error (if any)</th></tr>";
            
            foreach ($tables as $table) {
                echo "<tr>";
                echo "<td>$table</td>";
                
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->num_rows > 0) {
                    echo "<td style='color:green'>Exists</td><td></td>";
                } else {
                    echo "<td style='color:red'>Missing</td>";
                    echo "<td>" . $conn->error . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error checking database: " . $e->getMessage() . "</p>";
}

// Check upload directories
echo "<h2>Upload Directories Check</h2>";
$uploadDirs = [
    'uploads/tor',
    'uploads/school_id'
];

foreach ($uploadDirs as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    echo "<h3>$dir</h3>";
    
    if (!file_exists($fullPath)) {
        echo "<p style='color:red'>Directory does not exist. Creating it now...</p>";
        if (mkdir($fullPath, 0755, true)) {
            echo "<p style='color:green'>Directory created successfully.</p>";
        } else {
            echo "<p style='color:red'>Failed to create directory. Check permissions.</p>";
        }
    } else {
        echo "<p style='color:green'>Directory exists.</p>";
        if (is_writable($fullPath)) {
            echo "<p style='color:green'>Directory is writable.</p>";
        } else {
            echo "<p style='color:red'>Directory is not writable. Please check permissions.</p>";
        }
    }
}

// Check PHP configuration
echo "<h2>PHP Configuration</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";

$settings = [
    'PHP Version' => phpversion(),
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => ini_get('error_reporting'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'session.save_path' => ini_get('session.save_path')
];

foreach ($settings as $setting => $value) {
    echo "<tr><td>$setting</td><td>$value</td></tr>";
}
echo "</table>";

// Create a test log entry
echo "<h2>Test Log Entry</h2>";
$testLogFile = $logDir . '/error_log_' . date('Y-m-d') . '.txt';
$testLogEntry = "[" . date('Y-m-d H:i:s') . "] [TEST] This is a test log entry from diagnostic.php\n";
$testLogEntry .= str_repeat('-', 80) . "\n";

if (file_put_contents($testLogFile, $testLogEntry, FILE_APPEND)) {
    echo "<p style='color:green'>Test log entry created successfully.</p>";
} else {
    echo "<p style='color:red'>Failed to create test log entry. Check file permissions.</p>";
}

// Create a form to test file uploads
echo "<h2>Test File Upload</h2>";
echo "<form action='diagnostic.php' method='post' enctype='multipart/form-data'>";
echo "<input type='file' name='test_file'>";
echo "<input type='submit' name='upload_test' value='Test Upload'>";
echo "</form>";

if (isset($_POST['upload_test']) && isset($_FILES['test_file'])) {
    echo "<h3>Upload Test Results</h3>";
    echo "<pre>";
    print_r($_FILES['test_file']);
    echo "</pre>";
    
    if ($_FILES['test_file']['error'] == UPLOAD_ERR_OK) {
        $testUploadDir = __DIR__ . '/uploads/test';
        if (!file_exists($testUploadDir)) {
            mkdir($testUploadDir, 0755, true);
        }
        
        $testFilePath = $testUploadDir . '/' . basename($_FILES['test_file']['name']);
        if (move_uploaded_file($_FILES['test_file']['tmp_name'], $testFilePath)) {
            echo "<p style='color:green'>Test file uploaded successfully to: $testFilePath</p>";
        } else {
            echo "<p style='color:red'>Failed to move uploaded file. Check permissions.</p>";
        }
    } else {
        echo "<p style='color:red'>Upload error: " . $_FILES['test_file']['error'] . "</p>";
    }
}

// Check for Google Cloud Vision API configuration
echo "<h2>Google Cloud Vision API Configuration</h2>";
if (file_exists(__DIR__ . '/config/google_cloud_config.php')) {
    echo "<p style='color:green'>Google Cloud config file exists.</p>";
    
    // Check if we can include it without errors
    try {
        include_once(__DIR__ . '/config/google_cloud_config.php');
        if (defined('GOOGLE_API_KEY') && !empty(GOOGLE_API_KEY)) {
            echo "<p style='color:green'>GOOGLE_API_KEY is defined.</p>";
        } else {
            echo "<p style='color:red'>GOOGLE_API_KEY is not defined or empty.</p>";
        }
        
        if (defined('GOOGLE_VISION_API_ENDPOINT') && !empty(GOOGLE_VISION_API_ENDPOINT)) {
            echo "<p style='color:green'>GOOGLE_VISION_API_ENDPOINT is defined.</p>";
        } else {
            echo "<p style='color:red'>GOOGLE_VISION_API_ENDPOINT is not defined or empty.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Error including Google Cloud config: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>Google Cloud config file does not exist.</p>";
}

// Check for vendor directory (Composer dependencies)
echo "<h2>Composer Dependencies</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p style='color:green'>vendor/autoload.php exists.</p>";
    
    // Check if GuzzleHttp is available
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        if (class_exists('GuzzleHttp\\Client')) {
            echo "<p style='color:green'>GuzzleHttp\\Client class is available.</p>";
        } else {
            echo "<p style='color:red'>GuzzleHttp\\Client class is not available. You may need to run 'composer require guzzlehttp/guzzle'.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Error loading autoload.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>vendor/autoload.php does not exist. You may need to run 'composer install'.</p>";
}

// Display session data
echo "<h2>Session Data</h2>";
if (!empty($_SESSION)) {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "<p>No session data available.</p>";
}
?> 