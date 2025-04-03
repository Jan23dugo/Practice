<?php
if (isset($_GET['token']) && $_GET['token'] === 'your_secret_token') {
    $logFile = __DIR__ . '/judge0_debug.log';
    if (file_exists($logFile)) {
        header('Content-Type: text/plain');
        readfile($logFile);
    } else {
        echo "No log file found";
    }
}
?> 