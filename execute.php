<?php
// Turn off error display for AJAX
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    ini_set('display_errors', 0);
}

header('Content-Type: application/json');

// Get the parameters
$code = $_POST['code'] ?? '';
$input = $_POST['input'] ?? '';
$action = $_POST['action'] ?? 'run';

// Execute PHP code safely
function executePHPCode($code, $input = '') {
    if (empty(trim($code))) {
        return "No code provided to execute.";
    }
    
    // Check for dangerous functions
    $blockedFunctions = [
        'exec', 'shell_exec', 'system', 'passthru', 'proc_open', 
        'popen', 'eval', 'assert', 'file_get_contents', 'file_put_contents', 
        'unlink', 'rmdir', 'mkdir', 'rename', 'copy', 'fopen'
    ];
    
    foreach ($blockedFunctions as $func) {
        if (stripos($code, $func) !== false) {
            return "Error: Use of '$func' is not allowed for security reasons.";
        }
    }
    
    // Handle input if provided
    if (!empty($input)) {
        $inputLines = explode("\n", $input);
        $inputCode = '
            // Mock input handler
            $GLOBALS["__INPUT_LINES"] = ' . var_export($inputLines, true) . ';
            $GLOBALS["__INPUT_INDEX"] = 0;
            
            // Override readline
            function readline($prompt = "") {
                if ($prompt) echo $prompt;
                if (isset($GLOBALS["__INPUT_LINES"][$GLOBALS["__INPUT_INDEX"]])) {
                    $line = $GLOBALS["__INPUT_LINES"][$GLOBALS["__INPUT_INDEX"]++];
                    return $line;
                }
                return "";
            }
        ';
        $code = $inputCode . $code;
    }
    
    // Set execution limits
    set_time_limit(5);
    ini_set('memory_limit', '64M');
    
    // Capture output
    ob_start();
    try {
        $result = eval('?>' . $code);
        if ($result !== null && $result !== false) {
            echo $result;
        }
    } catch (Throwable $e) {
        echo "Error: " . $e->getMessage();
    }
    $output = ob_get_clean();
    
    return $output ?: "Code executed successfully, but produced no output.";
}

// Handle the request based on action
if ($action === 'run') {
    // Just run the code
    $output = executePHPCode($code, $input);
    echo json_encode(['output' => $output]);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>