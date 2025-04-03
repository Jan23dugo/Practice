<?php
// Function to execute C++ code
function executeCPPCode($code) {
    // Create a temporary file to store the code
    $tempFile = tempnam(sys_get_temp_dir(), 'cpp_');
    $sourceFile = $tempFile . '.cpp';
    $exeFile = $tempFile . '.exe';
    
    // Write the code to the source file
    file_put_contents($sourceFile, $code);
    
    // Compile the code (adjust the compiler path as needed for your server)
    $compileCommand = "g++ -o " . escapeshellarg($exeFile) . " " . escapeshellarg($sourceFile) . " 2>&1";
    exec($compileCommand, $compileOutput, $compileReturnVar);
    
    // Check if compilation was successful
    if ($compileReturnVar !== 0) {
        // Compilation error
        $result = "Compilation Error:\n" . implode("\n", $compileOutput);
    } else {
        // Run the compiled program
        $runCommand = escapeshellarg($exeFile) . " 2>&1";
        exec($runCommand, $runOutput, $runReturnVar);
        
        $result = implode("\n", $runOutput);
        
        // If no output but program ran successfully
        if (empty($result) && $runReturnVar === 0) {
            $result = "Program executed successfully, but produced no output.";
        }
    }
    
    // Clean up temporary files
    @unlink($sourceFile);
    @unlink($exeFile);
    @unlink($tempFile);
    
    return $result;
}
?> 