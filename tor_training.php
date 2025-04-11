<?php
require_once 'scan_tor.php';

/**
 * TOR Training System
 * 
 * This class extends the TOR Scanner to add machine learning capabilities.
 * It allows the system to learn from examples and improve accuracy over time.
 */
class TORTraining extends TORScanner {
    private $trainingDataDir = 'training_data';
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Ensure training data directory exists
        if (!file_exists($this->trainingDataDir)) {
            mkdir($this->trainingDataDir, 0755, true);
        }
    }
    
    /**
     * Train the system with a correctly extracted transcript
     * 
     * @param string $imagePath Path to image
     * @param array $correctData Correct extraction data (manually verified)
     * @param string $universityName Name of the university
     * @return bool Success status
     */
    public function trainWithExample($imagePath, $correctData, $universityName) {
        // Process image to get raw text
        $result = $this->processImage($imagePath);
        
        // If processing failed, return false
        if (isset($result['error'])) {
            return false;
        }
        
        // Get raw text from OCR
        $rawText = file_get_contents('debug_document.txt');
        if (empty($rawText)) {
            return false;
        }
        
        // Create a training example
        $trainingExample = [
            'university' => $universityName,
            'raw_text' => $rawText,
            'correct_data' => $correctData,
            'patterns' => $this->extractPatterns($rawText, $correctData)
        ];
        
        // Save training example
        $filename = $this->trainingDataDir . '/' . strtolower(str_replace(' ', '_', $universityName)) . '_' . date('Ymd_His') . '.json';
        file_put_contents($filename, json_encode($trainingExample, JSON_PRETTY_PRINT));
        
        return true;
    }
    
    /**
     * Extract patterns from a transcript by comparing raw text with correct data
     * 
     * @param string $text Raw OCR text
     * @param array $correctData Correct data
     * @return array Patterns
     */
    private function extractPatterns($text, $correctData) {
        $patterns = [
            'header_patterns' => [],
            'course_code_patterns' => [],
            'description_patterns' => [],
            'grade_patterns' => [],
            'units_patterns' => [],
            'semester_patterns' => []
        ];
        
        // Extract header patterns
        $lines = explode("\n", $text);
        $firstTenLines = array_slice($lines, 0, 10);
        foreach ($firstTenLines as $line) {
            if (preg_match('/university|college|institute/i', $line)) {
                $patterns['header_patterns'][] = preg_quote(trim($line), '/');
            }
        }
        
        // Extract course code patterns from correct data
        foreach ($correctData['courses'] as $course) {
            if (!empty($course['code'])) {
                // Find this course code in the text
                $cleanCode = preg_quote($course['code'], '/');
                if (preg_match('/[^\n]*' . $cleanCode . '[^\n]*/i', $text, $matches)) {
                    $context = $matches[0];
                    
                    // Extract the pattern of this course code
                    if (preg_match('/([A-Z]{2,5})[\s-]*(\d{3,5}[A-Z]?)/i', $course['code'], $codeMatches)) {
                        $patterns['course_code_patterns'][] = [
                            'prefix' => $codeMatches[1],
                            'number' => $codeMatches[2],
                            'context' => $context
                        ];
                    }
                    
                    // Extract grade pattern if available
                    if (!empty($course['grade']) && preg_match('/\b' . preg_quote($course['grade'], '/') . '\b/', $context)) {
                        $patterns['grade_patterns'][] = [
                            'value' => $course['grade'],
                            'context' => $context
                        ];
                    }
                    
                    // Extract units pattern if available
                    if (!empty($course['units']) && preg_match('/\b' . preg_quote($course['units'], '/') . '\b/', $context)) {
                        $patterns['units_patterns'][] = [
                            'value' => $course['units'],
                            'context' => $context
                        ];
                    }
                }
            }
        }
        
        // Look for semester patterns
        foreach ($lines as $line) {
            if (preg_match('/([1-4](?:st|nd|rd|th))\s+semester/i', $line, $matches)) {
                $patterns['semester_patterns'][] = [
                    'semester' => $matches[1],
                    'full_text' => $line
                ];
            }
        }
        
        return $patterns;
    }
    
    /**
     * Get all training examples
     * 
     * @return array Training examples
     */
    public function getAllTrainingExamples() {
        $examples = [];
        $files = glob($this->trainingDataDir . '/*.json');
        
        foreach ($files as $file) {
            $examples[] = json_decode(file_get_contents($file), true);
        }
        
        return $examples;
    }
    
    /**
     * Get training examples for a specific university
     * 
     * @param string $universityName University name
     * @return array Training examples for the university
     */
    public function getUniversityTrainingExamples($universityName) {
        $examples = [];
        $files = glob($this->trainingDataDir . '/' . strtolower(str_replace(' ', '_', $universityName)) . '_*.json');
        
        foreach ($files as $file) {
            $examples[] = json_decode(file_get_contents($file), true);
        }
        
        return $examples;
    }
    
    /**
     * Get all training file paths
     * 
     * @return array Training file paths
     */
    public function getTrainingFiles() {
        return glob($this->trainingDataDir . '/*.json');
    }
}

// API endpoint for training
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'train') {
    header('Content-Type: application/json');
    
    // Check if required parameters are provided
    if (!isset($_POST['image_path']) || !isset($_POST['correct_data']) || !isset($_POST['university'])) {
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }
    
    $imagePath = $_POST['image_path'];
    $correctData = json_decode($_POST['correct_data'], true);
    $universityName = $_POST['university'];
    
    // Validate correct data format
    if (json_last_error() !== JSON_ERROR_NONE || !isset($correctData['courses'])) {
        echo json_encode(['error' => 'Invalid correct data format']);
        exit;
    }
    
    $trainer = new TORTraining();
    $result = $trainer->trainWithExample($imagePath, $correctData, $universityName);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Training data saved successfully']);
    } else {
        echo json_encode(['error' => 'Failed to save training data']);
    }
    exit;
}

// Endpoint to view training examples
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'view_training') {
    header('Content-Type: application/json');
    
    $trainer = new TORTraining();
    
    if (isset($_GET['university'])) {
        $examples = $trainer->getUniversityTrainingExamples($_GET['university']);
    } else {
        $examples = $trainer->getAllTrainingExamples();
    }
    
    echo json_encode($examples, JSON_PRETTY_PRINT);
    exit;
}
?> 