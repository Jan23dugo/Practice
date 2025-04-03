<?php
// Start session to access session variables
session_start();

// Include database connection if needed
include 'config/config.php';

// Security check - you might want to add proper authentication here
// This is a simple example - in production, add proper authentication

// Function to list log files
function listLogFiles($directory) {
    $logFiles = [];
    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && 
                (strpos($file, 'ocr_process_') !== false || 
                 strpos($file, 'subject_extraction_') !== false)) {
                $logFiles[] = $file;
            }
        }
    }
    return $logFiles;
}

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Handle file viewing
$selectedLog = isset($_GET['log']) ? $_GET['log'] : null;
$logContent = '';
$isSafe = false;

if ($selectedLog) {
    // Basic security check to prevent directory traversal
    $isSafe = (strpos($selectedLog, '..') === false && 
              (strpos($selectedLog, 'ocr_process_') !== false || 
               strpos($selectedLog, 'subject_extraction_') !== false));
    
    if ($isSafe) {
        $logPath = $logDir . '/' . $selectedLog;
        if (file_exists($logPath)) {
            $logContent = file_get_contents($logPath);
        } else {
            $logContent = "Log file not found.";
        }
    } else {
        $logContent = "Invalid log file selected.";
    }
}

// Get session debug output
$sessionDebug = isset($_SESSION['debug_output']) ? $_SESSION['debug_output'] : '';
$ocrError = isset($_SESSION['ocr_error']) ? $_SESSION['ocr_error'] : '';
$matches = isset($_SESSION['matches']) ? $_SESSION['matches'] : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCR Log Viewer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2, h3 {
            color: #75343A;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .panel {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .log-list {
            background: #fff;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 20px;
            max-height: 200px;
            overflow-y: auto;
        }
        .log-content {
            background: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            white-space: pre-wrap;
            overflow-x: auto;
            font-family: monospace;
            max-height: 500px;
            overflow-y: auto;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        a {
            color: #75343A;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .tab {
            overflow: hidden;
            border: 1px solid #ccc;
            background-color: #f1f1f1;
            margin-bottom: 20px;
        }
        .tab button {
            background-color: inherit;
            float: left;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 14px 16px;
            transition: 0.3s;
        }
        .tab button:hover {
            background-color: #ddd;
        }
        .tab button.active {
            background-color: #75343A;
            color: white;
        }
        .tabcontent {
            display: none;
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-top: none;
        }
        .visible {
            display: block;
        }
        .match-item {
            padding: 8px;
            margin: 5px 0;
            border-radius: 4px;
        }
        .match-success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
        }
        .match-fail {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>OCR Log Viewer</h1>
        <p>This tool helps you view the OCR processing results and debug information.</p>
        
        <div class="tab">
            <button class="tablinks active" onclick="openTab(event, 'SessionDebug')">Session Debug</button>
            <button class="tablinks" onclick="openTab(event, 'LogFiles')">Log Files</button>
            <button class="tablinks" onclick="openTab(event, 'Matches')">Subject Matches</button>
        </div>
        
        <!-- Session Debug Tab -->
        <div id="SessionDebug" class="tabcontent visible">
            <h2>Session Debug Information</h2>
            
            <?php if (!empty($ocrError)): ?>
                <div class="error">
                    <strong>OCR Error:</strong> <?php echo $ocrError; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($sessionDebug)): ?>
                <div class="panel">
                    <?php echo $sessionDebug; ?>
                </div>
            <?php else: ?>
                <p>No session debug information available. Try submitting the registration form first.</p>
            <?php endif; ?>
        </div>
        
        <!-- Log Files Tab -->
        <div id="LogFiles" class="tabcontent">
            <h2>OCR Log Files</h2>
            <div class="log-list">
                <?php 
                $logFiles = listLogFiles($logDir);
                if (empty($logFiles)): 
                ?>
                    <p>No log files found. Try submitting the registration form first.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($logFiles as $file): ?>
                            <li>
                                <a href="?log=<?php echo urlencode($file); ?>"><?php echo $file; ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <?php if ($selectedLog && $isSafe): ?>
                <h3>Log Content: <?php echo htmlspecialchars($selectedLog); ?></h3>
                <div class="log-content"><?php echo htmlspecialchars($logContent); ?></div>
            <?php elseif ($selectedLog): ?>
                <div class="error">Invalid log file selected.</div>
            <?php endif; ?>
        </div>
        
        <!-- Matches Tab -->
        <div id="Matches" class="tabcontent">
            <h2>Subject Matching Results</h2>
            
            <?php if (!empty($matches)): ?>
                <div class="panel">
                    <?php foreach ($matches as $match): ?>
                        <div class="match-item <?php echo (strpos($match, '✓') !== false) ? 'match-success' : 'match-fail'; ?>">
                            <?php echo htmlspecialchars($match); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No subject matching results available. Try submitting the registration form first.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            
            // Hide all tab content
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("visible");
            }
            
            // Remove "active" class from all tab buttons
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            
            // Show the current tab and add "active" class to the button
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("visible");
            evt.currentTarget.className += " active";
        }
    </script>
</body>
</html> 