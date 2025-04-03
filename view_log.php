<?php
// Simple script to view log files securely
// Only allow viewing files in the logs directory

// Start session for security
session_start();

// Validate that we're in an active session
if (!isset($_SESSION['extracted_subjects'])) {
    header("HTTP/1.1 403 Forbidden");
    echo "Access denied. Please process a TOR first.";
    exit;
}

// Get the requested file
$filename = isset($_GET['file']) ? $_GET['file'] : '';

// Security check - only allow viewing .txt files in the logs directory
if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-\.]+\.txt$/', $filename)) {
    header("HTTP/1.1 400 Bad Request");
    echo "Invalid filename.";
    exit;
}

// Build the full path to the log file
$logFile = __DIR__ . '/logs/' . $filename;

// Check if the file exists and is readable
if (!file_exists($logFile) || !is_readable($logFile)) {
    header("HTTP/1.1 404 Not Found");
    echo "Log file not found or not readable.";
    exit;
}

// Set content type to plain text
header('Content-Type: text/plain');

// Output the file contents
echo file_get_contents($logFile); 