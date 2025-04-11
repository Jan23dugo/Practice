<?php
// Test file for Unstract API connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to test Unstract API
function testUnstractConnection() {
    // Create logs directory if it doesn't exist
    $logsDir = __DIR__ . '/logs';
    if (!file_exists($logsDir)) {
        mkdir($logsDir, 0777, true);
    }

    // Start logging
    $logFile = $logsDir . '/unstract_test_' . date('Y-m-d_H-i-s') . '.log';
    $log = "=== UNSTRACT API TEST LOG ===\n";
    $log .= "Date: " . date('Y-m-d H:i:s') . "\n\n";

    echo "<div style='font-family: monospace; white-space: pre-wrap;'>";
    echo "=== UNSTRACT API TEST ===\n\n";

    // Initialize cURL
    $curl = curl_init();
    
    // Use an actual TOR file from your uploads directory
    $filePath = __DIR__ . '/uploads/tor/sample.pdf';
    
    // Check if file exists and log details
    echo "Checking file...\n";
    $log .= "File check:\n";
    
    if (!file_exists($filePath)) {
        $message = "Error: Test file not found at {$filePath}. Please upload a TOR file first.";
        echo $message . "\n";
        $log .= $message . "\n";
        file_put_contents($logFile, $log);
        die();
    }

    // Get and log file details
    $fileDetails = [
        'path' => $filePath,
        'exists' => 'Yes',
        'size' => filesize($filePath) . ' bytes',
        'mime_type' => mime_content_type($filePath),
        'permissions' => substr(sprintf('%o', fileperms($filePath)), -4)
    ];

    echo "File details:\n";
    foreach ($fileDetails as $key => $value) {
        echo "- {$key}: {$value}\n";
    }
    echo "\n";

    $log .= "File details:\n" . print_r($fileDetails, true) . "\n";

    // Prepare the request
    $postFields = [
        'files' => new CURLFile($filePath),
        'timeout' => '300',
        'include_metadata' => 'false'
    ];
    
    // Download CA certificate if not exists
    $caPath = __DIR__ . '/cacert.pem';
    if (!file_exists($caPath)) {
        echo "Downloading CA certificate...\n";
        $ca = file_get_contents('https://curl.se/ca/cacert.pem');
        if ($ca) {
            file_put_contents($caPath, $ca);
            echo "CA certificate downloaded successfully.\n\n";
        } else {
            echo "Warning: Could not download CA certificate. SSL verification might fail.\n\n";
        }
    }

    // Set cURL options with detailed error handling
    $curlOptions = [
        CURLOPT_URL => 'https://us-central.unstract.com/deployment/api/org_SBbh31LYckHO5i28/tor-data-extractor/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer dff56a8a-6d02-4089-bd87-996d7be8b1bb'
        ],
        CURLOPT_VERBOSE => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CAINFO => $caPath,
        // Additional SSL options
        CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        CURLOPT_SSL_CIPHER_LIST => 'TLSv1.2',
        // Follow redirects
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_AUTOREFERER => true
    ];

    // Check SSL/TLS support
    echo "Checking SSL/TLS Support:\n";
    if (!curl_version()['features'] & CURL_VERSION_SSL) {
        echo "- WARNING: SSL is not supported by cURL\n";
        $log .= "WARNING: SSL is not supported by cURL\n";
    } else {
        echo "- SSL is supported\n";
        echo "- SSL Version: " . curl_version()['ssl_version'] . "\n";
    }
    echo "\n";

    // Log cURL options
    echo "cURL Options:\n";
    foreach ($curlOptions as $key => $value) {
        if (!is_array($value)) {
            $optionName = curl_strerror($key);
            $optionValue = is_bool($value) ? ($value ? 'true' : 'false') : (is_string($value) ? $value : json_encode($value));
            echo "- {$optionName}: {$optionValue}\n";
        }
    }
    echo "\n";

    $log .= "cURL Options:\n" . print_r($curlOptions, true) . "\n";

    // Set all cURL options
    curl_setopt_array($curl, $curlOptions);
    
    // Capture CURL debug output
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($curl, CURLOPT_STDERR, $verbose);
    
    // Execute request
    echo "Sending request to Unstract API...\n";
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    // Get additional error information
    $info = curl_getinfo($curl);
    
    echo "\nConnection Info:\n";
    echo "- HTTP Status Code: " . (isset($info['http_code']) ? $info['http_code'] : 'N/A') . "\n";
    echo "- Total Time: " . (isset($info['total_time']) ? number_format($info['total_time'], 6) : 'N/A') . " seconds\n";
    echo "- DNS Lookup Time: " . (isset($info['namelookup_time']) ? number_format($info['namelookup_time'], 6) : 'N/A') . " seconds\n";
    echo "- Connect Time: " . (isset($info['connect_time']) ? number_format($info['connect_time'], 6) : 'N/A') . " seconds\n";
    echo "- SSL/TLS Time: " . (isset($info['pretransfer_time']) ? number_format($info['pretransfer_time'], 6) : 'N/A') . " seconds\n";
    echo "- Response Size: " . (isset($info['size_download']) ? $info['size_download'] : 'N/A') . " bytes\n\n";

    $log .= "Connection Info:\n" . print_r($info, true) . "\n";
    
    // Get debug information
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    
    if ($err) {
        echo "cURL Error:\n" . $err . "\n\n";
        $log .= "cURL Error:\n" . $err . "\n\n";
    }
    
    echo "Verbose cURL log:\n";
    echo $verboseLog . "\n\n";
    
    $log .= "Verbose cURL log:\n" . $verboseLog . "\n\n";

    if ($response) {
        echo "API Response:\n";
        $decodedResponse = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo json_encode($decodedResponse, JSON_PRETTY_PRINT) . "\n";
            $log .= "API Response:\n" . json_encode($decodedResponse, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Raw response (could not decode JSON):\n" . $response . "\n";
            $log .= "Raw response:\n" . $response . "\n";
        }
    }
    
    curl_close($curl);
    echo "</div>";

    // Save log file
    file_put_contents($logFile, $log);
    echo "\nLog file saved to: " . $logFile . "\n";
}

// Add some basic styling
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2 { color: #333; }
    .error { color: red; }
    .success { color: green; }
</style>";

// Run the test
echo "<h2>Testing Unstract API Connection</h2>";
testUnstractConnection();
?> 