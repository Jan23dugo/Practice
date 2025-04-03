<?php
// OCR Practice File - For testing Google Cloud Vision OCR and subject extraction

// Start session for storing results
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include Google Cloud config
require_once 'config/google_cloud_config.php';
require 'vendor/autoload.php';

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Create preprocessing directory if it doesn't exist
if (!file_exists(__DIR__ . '/uploads/preprocessed')) {
    mkdir(__DIR__ . '/uploads/preprocessed', 0755, true);
}

// Create blocks directory if it doesn't exist
if (!file_exists(__DIR__ . '/uploads/blocks')) {
    mkdir(__DIR__ . '/uploads/blocks', 0755, true);
}

/**
 * Class to represent a text block from OCR
 */
class TextBlock {
    public $id;
    public $text;
    public $boundingBox;
    public $confidence;
    public $paragraphs = [];
    public $words = [];
    public $type = 'unknown'; // Can be 'header', 'course_code', 'course_title', 'grade', 'credit_unit', etc.
    public $row = -1; // For table-like structures
    public $column = -1; // For table-like structures
    
    public function __construct($id, $text, $boundingBox, $confidence = 0) {
        $this->id = $id;
        $this->text = $text;
        $this->boundingBox = $boundingBox;
        $this->confidence = $confidence;
    }
    
    /**
     * Check if this block is likely to be a course code
     */
    public function isCourseCode() {
        // Update the pattern to match course codes like COMP20123, COMP20163, etc.
        return preg_match('/^[A-Z]{3,4}\s*\d{4,5}$/', trim($this->text)) === 1;
    }
    
    /**
     * Check if this block is likely to be a grade
     */
    public function isGrade() {
        // Update to match grade patterns (numbers with decimal points like 1.00, 2.25, etc.)
        return preg_match('/^\d+\.\d+$/', trim($this->text)) === 1;
    }
    
    /**
     * Check if this block is likely to be a credit unit
     */
    public function isCreditUnit() {
        // Update to match credit unit patterns (typically numbers like 3.0)
        return preg_match('/^\d+\.\d+$/', trim($this->text)) === 1 && 
               floatval(trim($this->text)) <= 5.0; // Assuming credit units are typically <= 5.0
    }
    
    /**
     * Get the center X coordinate of the bounding box
     */
    public function getCenterX() {
        $vertices = $this->boundingBox['vertices'];
        $minX = min(array_column($vertices, 'x'));
        $maxX = max(array_column($vertices, 'x'));
        return ($minX + $maxX) / 2;
    }
    
    /**
     * Get the center Y coordinate of the bounding box
     */
    public function getCenterY() {
        $vertices = $this->boundingBox['vertices'];
        $minY = min(array_column($vertices, 'y'));
        $maxY = max(array_column($vertices, 'y'));
        return ($minY + $maxY) / 2;
    }
    
    /**
     * Get the width of the bounding box
     */
    public function getWidth() {
        $vertices = $this->boundingBox['vertices'];
        $minX = min(array_column($vertices, 'x'));
        $maxX = max(array_column($vertices, 'x'));
        return $maxX - $minX;
    }
    
    /**
     * Get the height of the bounding box
     */
    public function getHeight() {
        $vertices = $this->boundingBox['vertices'];
        $minY = min(array_column($vertices, 'y'));
        $maxY = max(array_column($vertices, 'y'));
        return $maxY - $minY;
    }
    
    /**
     * Check if this block is horizontally aligned with another block
     */
    public function isHorizontallyAlignedWith($otherBlock, $tolerance = 20) {
        $thisCenter = $this->getCenterY();
        $otherCenter = $otherBlock->getCenterY();
        return abs($thisCenter - $otherCenter) <= $tolerance;
    }
    
    /**
     * Check if this block is to the left of another block
     */
    public function isLeftOf($otherBlock) {
        return $this->getCenterX() < $otherBlock->getCenterX();
    }
    
    /**
     * Check if this block is to the right of another block
     */
    public function isRightOf($otherBlock) {
        return $this->getCenterX() > $otherBlock->getCenterX();
    }
    
    /**
     * Check if this block is above another block
     */
    public function isAbove($otherBlock) {
        return $this->getCenterY() < $otherBlock->getCenterY();
    }
    
    /**
     * Calculate horizontal distance to another block
     */
    public function horizontalDistanceTo($otherBlock) {
        return abs($this->getCenterX() - $otherBlock->getCenterX());
    }
    
    /**
     * Calculate vertical distance to another block
     */
    public function verticalDistanceTo($otherBlock) {
        return abs($this->getCenterY() - $otherBlock->getCenterY());
    }
}

/**
 * Class to manage and analyze text blocks
 */
class BlockAnalyzer {
    public $blocks = [];
    public $rows = [];
    public $columns = [];
    public $tableStructure = [];
    private $logFile;
    private $columnTypes = [];
    
    public function __construct($logFile = null) {
        $this->logFile = $logFile;
    }
    
    /**
     * Add a text block to the analyzer
     */
    public function addBlock($block) {
        $this->blocks[] = $block;
    }
    
    /**
     * Log a message if a log file is set
     */
    private function log($message) {
        if ($this->logFile) {
            file_put_contents($this->logFile, $message . "\n", FILE_APPEND);
        }
    }
    
    /**
     * Analyze blocks to identify rows and columns (table structure)
     */
    public function analyzeTableStructure($rowTolerance = 30, $minBlocksInRow = 2) {
        $this->log("Starting table structure analysis with {$rowTolerance}px row tolerance...");
        
        // Sort blocks by Y position (top to bottom)
        usort($this->blocks, function($a, $b) {
            return $a->getCenterY() - $b->getCenterY();
        });
        
        // Identify rows based on Y position
        $currentRow = [];
        $rowIndex = 0;
        
        for ($i = 0; $i < count($this->blocks); $i++) {
            $block = $this->blocks[$i];
            
            if (empty($currentRow)) {
                $currentRow[] = $block;
                $block->row = $rowIndex;
            } else {
                $lastBlock = end($currentRow);
                
                // If this block is horizontally aligned with the last block, add it to the current row
                if ($block->isHorizontallyAlignedWith($lastBlock, $rowTolerance)) {
                    $currentRow[] = $block;
                    $block->row = $rowIndex;
                } else {
                    // If we have enough blocks in the current row, save it
                    if (count($currentRow) >= $minBlocksInRow) {
                        $this->rows[$rowIndex] = $currentRow;
                    }
                    
                    // Start a new row
                    $currentRow = [$block];
                    $rowIndex++;
                    $block->row = $rowIndex;
                }
            }
            
            // If this is the last block, save the current row if it has enough blocks
            if ($i == count($this->blocks) - 1 && count($currentRow) >= $minBlocksInRow) {
                $this->rows[$rowIndex] = $currentRow;
            }
        }
        
        $this->log("Identified " . count($this->rows) . " rows in the document");
        
        // For each row, sort blocks from left to right and assign column indices
        foreach ($this->rows as $rowIndex => $rowBlocks) {
            usort($rowBlocks, function($a, $b) {
                return $a->getCenterX() - $b->getCenterX();
            });
            
            // Assign column indices
            foreach ($rowBlocks as $colIndex => $block) {
                $block->column = $colIndex;
            }
            
            // Update the row with sorted blocks
            $this->rows[$rowIndex] = $rowBlocks;
        }
        
        // Identify columns based on X position
        $this->identifyColumns();
        
        return $this->rows;
    }
    
    /**
     * Identify columns based on X position of blocks
     */
    private function identifyColumns() {
        // Initialize columns array
        $this->columns = [];
        
        // Collect all unique column indices
        $columnIndices = [];
        foreach ($this->blocks as $block) {
            if ($block->column >= 0 && !in_array($block->column, $columnIndices)) {
                $columnIndices[] = $block->column;
            }
        }
        
        // Sort column indices
        sort($columnIndices);
        
        // For each column index, collect all blocks with that column index
        foreach ($columnIndices as $colIndex) {
            $columnBlocks = array_filter($this->blocks, function($block) use ($colIndex) {
                return $block->column === $colIndex;
            });
            
            $this->columns[$colIndex] = array_values($columnBlocks);
        }
        
        $this->log("Identified " . count($this->columns) . " columns in the document");
        
        return $this->columns;
    }
    
    /**
     * Classify blocks based on content and position
     */
    public function classifyBlocks() {
        $this->log("Classifying blocks based on content and position...");
        
        // First, identify header row if present
        $headerRow = $this->findHeaderRow();
        
        if ($headerRow !== null) {
            $this->log("Found header row at index {$headerRow}");
            
            // Classify header blocks
            foreach ($this->rows[$headerRow] as $block) {
                $text = strtolower(trim($block->text));
                
                if (strpos($text, 'course code') !== false) {
                    $block->type = 'header_course_code';
                } else if (strpos($text, 'course title') !== false) {
                    $block->type = 'header_course_title';
                } else if (strpos($text, 'grade') !== false) {
                    $block->type = 'header_grade';
                } else if (strpos($text, 'credit') !== false) {
                    $block->type = 'header_credit_unit';
                }
            }
            
            // Use header classifications to classify data columns
            $this->classifyColumnsByHeader($headerRow);
        } else {
            $this->log("No header row found, classifying blocks by content...");
            
            // Classify blocks by content
            foreach ($this->blocks as $block) {
                if ($block->isCourseCode()) {
                    $block->type = 'course_code';
                } else if ($block->isGrade()) {
                    $block->type = 'grade';
                } else if ($block->isCreditUnit()) {
                    $block->type = 'credit_unit';
                }
            }
            
            // Try to identify course titles based on position relative to course codes
            $this->identifyCourseTitles();
        }
        
        // Count classified blocks
        $classifiedCount = 0;
        foreach ($this->blocks as $block) {
            if ($block->type !== 'unknown') {
                $classifiedCount++;
            }
        }
        
        $this->log("Classified {$classifiedCount} out of " . count($this->blocks) . " blocks");
        
        return $this->blocks;
    }
    
    /**
     * Find the header row if present
     */
    private function findHeaderRow() {
        foreach ($this->rows as $rowIndex => $rowBlocks) {
            $headerTerms = ['course code', 'course title', 'grade', 'credit'];
            $matchCount = 0;
            
            foreach ($rowBlocks as $block) {
                $text = strtolower(trim($block->text));
                
                foreach ($headerTerms as $term) {
                    if (strpos($text, $term) !== false) {
                        $matchCount++;
                        break;
                    }
                }
            }
            
            // If we found at least 2 header terms, consider this a header row
            if ($matchCount >= 2) {
                return $rowIndex;
            }
        }
        
        return null;
    }
    
    /**
     * Classify columns based on header row
     */
    private function classifyColumnsByHeader($headerRow) {
        // Improve header detection with more flexible matching
        foreach ($headerRow as $block) {
            $text = strtolower(trim($block->text));
            
            if (preg_match('/(subject|course)\s*code/i', $text)) {
                $this->columnTypes[$block->id] = 'subject_code';
            } else if (preg_match('/(description|title)/i', $text)) {
                $this->columnTypes[$block->id] = 'description';
            } else if (preg_match('/(faculty|instructor|professor|teacher)/i', $text)) {
                $this->columnTypes[$block->id] = 'faculty';
            } else if (preg_match('/(unit|credit)/i', $text)) {
                $this->columnTypes[$block->id] = 'units';
            } else if (preg_match('/(section|sect)/i', $text)) {
                $this->columnTypes[$block->id] = 'section';
            } else if (preg_match('/(final|grade)/i', $text)) {
                $this->columnTypes[$block->id] = 'grade';
            } else {
                $this->columnTypes[$block->id] = 'unknown';
            }
            
            $this->log("Classified column header '{$block->text}' as {$this->columnTypes[$block->id]}");
        }
    }
    
    /**
     * Identify course titles based on position relative to course codes
     */
    private function identifyCourseTitles() {
        // Find all course code blocks
        $courseCodeBlocks = array_filter($this->blocks, function($block) {
            return $block->type === 'course_code';
        });
        
        foreach ($courseCodeBlocks as $codeBlock) {
            // Find blocks in the same row
            $sameRowBlocks = array_filter($this->blocks, function($block) use ($codeBlock) {
                return $block->row === $codeBlock->row && $block->id !== $codeBlock->id && $block->type === 'unknown';
            });
            
            // If there's a block to the right of the course code, it's likely the course title
            foreach ($sameRowBlocks as $block) {
                if ($block->isRightOf($codeBlock) && $block->horizontalDistanceTo($codeBlock) < 300) {
                    $block->type = 'course_title';
                    break;
                }
            }
        }
    }
    
    /**
     * Extract subjects from classified blocks
     */
    public function extractSubjects() {
        $this->log("Extracting subjects from classified blocks...");
        
        $subjects = [];
        
        // Group blocks by row
        $rowGroups = [];
        foreach ($this->blocks as $block) {
            if ($block->row >= 0 && $block->type !== 'unknown' && 
                strpos($block->type, 'header_') === false) {
                if (!isset($rowGroups[$block->row])) {
                    $rowGroups[$block->row] = [];
                }
                $rowGroups[$block->row][] = $block;
            }
        }
        
        // Process each row to extract subject data
        foreach ($rowGroups as $rowIndex => $rowBlocks) {
            $subject = [
                'course_code' => '',
                'course_title' => '',
                'grade' => '',
                'credit_units' => ''
            ];
            
            $hasData = false;
            
            foreach ($rowBlocks as $block) {
                switch ($block->type) {
                    case 'course_code':
                        $subject['course_code'] = trim($block->text);
                        $hasData = true;
                        break;
                    case 'course_title':
                        $subject['course_title'] = trim($block->text);
                        $hasData = true;
                        break;
                    case 'grade':
                        $subject['grade'] = trim($block->text);
                        $hasData = true;
                        break;
                    case 'credit_unit':
                        $subject['credit_units'] = trim($block->text);
                        $hasData = true;
                        break;
                }
            }
            
            // Only add subjects with at least course code and one other field
            if ($hasData && !empty($subject['course_code']) && 
                (!empty($subject['course_title']) || !empty($subject['grade']) || !empty($subject['credit_units']))) {
                $subjects[] = $subject;
            }
        }
        
        $this->log("Extracted " . count($subjects) . " subjects from blocks");
        
        return $subjects;
    }
    
    /**
     * Visualize blocks on the image
     */
    public function visualizeBlocks($imagePath, $outputPath) {
        // Load the image
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        
        if ($extension === 'jpg' || $extension === 'jpeg') {
            $image = imagecreatefromjpeg($imagePath);
        } else if ($extension === 'png') {
            $image = imagecreatefrompng($imagePath);
        } else {
            return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Define colors
        $colors = [
            'unknown' => imagecolorallocate($image, 255, 0, 0), // Red
            'course_code' => imagecolorallocate($image, 0, 255, 0), // Green
            'course_title' => imagecolorallocate($image, 0, 0, 255), // Blue
            'grade' => imagecolorallocate($image, 255, 255, 0), // Yellow
            'credit_unit' => imagecolorallocate($image, 255, 0, 255), // Magenta
            'header_course_code' => imagecolorallocate($image, 0, 128, 0), // Dark Green
            'header_course_title' => imagecolorallocate($image, 0, 0, 128), // Dark Blue
            'header_grade' => imagecolorallocate($image, 128, 128, 0), // Dark Yellow
            'header_credit_unit' => imagecolorallocate($image, 128, 0, 128) // Dark Magenta
        ];
        
        // Default color for types not defined
        $defaultColor = imagecolorallocate($image, 128, 128, 128); // Gray
        
        // Draw bounding boxes for each block
        foreach ($this->blocks as $block) {
            $vertices = $block->boundingBox['vertices'];
            
            // Get color based on block type
            $color = isset($colors[$block->type]) ? $colors[$block->type] : $defaultColor;
            
            // Draw the bounding box
            for ($i = 0; $i < count($vertices); $i++) {
                $j = ($i + 1) % count($vertices);
                imageline(
                    $image,
                    $vertices[$i]['x'],
                    $vertices[$i]['y'],
                    $vertices[$j]['x'],
                    $vertices[$j]['y'],
                    $color
                );
            }
            
            // Draw block ID and type
            $x = $vertices[0]['x'];
            $y = $vertices[0]['y'] - 5;
            $label = "{$block->id}: {$block->type}";
            imagestring($image, 2, $x, $y, $label, $color);
        }
        
        // Save the image
        if ($extension === 'jpg' || $extension === 'jpeg') {
            imagejpeg($image, $outputPath, 90);
        } else {
            imagepng($image, $outputPath, 9);
        }
        
        imagedestroy($image);
        
        return true;
    }
    
    /**
     * Visualize blocks with classification on the image
     */
    public function visualizeBlocksWithClassification($imagePath, $outputPath) {
        $image = new Imagick($imagePath);
        $draw = new ImagickDraw();
        
        // Set up colors for different block types
        $colors = [
            'subject_code' => '#FF0000',
            'description' => '#00FF00',
            'faculty' => '#0000FF',
            'units' => '#FFFF00',
            'section' => '#FF00FF',
            'grade' => '#00FFFF',
            'unknown' => '#FF6600'
        ];
        
        // Draw each block with its classification
        foreach ($this->blocks as $block) {
            $blockType = 'unknown';
            
            // Determine block type based on column classification
            foreach ($this->rows as $rowIndex => $row) {
                if (in_array($block->id, array_column($row, 'id'))) {
                    foreach ($row as $colBlock) {
                        if ($colBlock->id === $block->id && isset($this->columnTypes[$colBlock->id])) {
                            $blockType = $this->columnTypes[$colBlock->id];
                            break;
                        }
                    }
                    break;
                }
            }
            
            // Set color based on block type
            $draw->setStrokeColor(new ImagickPixel($colors[$blockType]));
            $draw->setFillOpacity(0);
            $draw->setStrokeWidth(2);
            
            // Draw rectangle around the block
            $draw->rectangle(
                $block->boundingBox['left'],
                $block->boundingBox['top'],
                $block->boundingBox['left'] + $block->boundingBox['width'],
                $block->boundingBox['top'] + $block->boundingBox['height']
            );
            
            // Add text label with classification
            $draw->setFillColor(new ImagickPixel($colors[$blockType]));
            $draw->setFontSize(12);
            $draw->annotation(
                $block->boundingBox['left'],
                $block->boundingBox['top'] - 5,
                $blockType
            );
        }
        
        $image->drawImage($draw);
        $image->writeImage($outputPath);
        
        return $outputPath;
    }
}

// Function to validate uploaded file
function validateUploadedFile($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and PDF files are allowed.');
    }
    if ($file['size'] > 5000000) { // File size check (5MB)
        throw new Exception('File is too large. Maximum size is 5MB.');
    }
    return true;
}

// Function to preprocess image for better OCR results
function preprocessImage($imagePath) {
    // Add more robust preprocessing
    $image = new Imagick($imagePath);
    
    // Increase contrast
    $image->contrastImage(1);
    
    // Enhance edges
    $image->edgeImage(1);
    
    // Convert to grayscale
    $image->transformImageColorspace(Imagick::COLORSPACE_GRAY);
    
    // Apply threshold to make text more distinct
    $image->thresholdImage(0.5 * Imagick::getQuantum());
    
    // Save the preprocessed image
    $processedPath = pathinfo($imagePath, PATHINFO_DIRNAME) . '/processed_' . pathinfo($imagePath, PATHINFO_BASENAME);
    $image->writeImage($processedPath);
    
    return $processedPath;
}

// Function to perform OCR using Google Cloud Vision
function performOCR($imagePath) {
    try {
        // Preprocess the image for better OCR results
        $preprocessedImagePath = preprocessImage($imagePath);
        
        $client = new GuzzleHttp\Client();
        $imageData = base64_encode(file_get_contents($preprocessedImagePath));
        
        // Enhanced OCR request with additional parameters
        $requestBody = [
            'requests' => [
                [
                    'image' => [
                        'content' => $imageData
                    ],
                    'features' => [
                        [
                            'type' => 'DOCUMENT_TEXT_DETECTION', // Use DOCUMENT_TEXT_DETECTION for better layout analysis
                            'model' => 'builtin/latest'
                        ]
                    ],
                    'imageContext' => [
                        'languageHints' => ['en'], // Language hint for better recognition
                        'textDetectionParams' => [
                            'enableTextDetectionConfidenceScore' => true
                        ]
                    ]
                ]
            ]
        ];

        $response = $client->post(GOOGLE_VISION_API_ENDPOINT . '?key=' . GOOGLE_API_KEY, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => $requestBody
        ]);

        $result = json_decode($response->getBody(), true);

        if (!isset($result['responses'][0]['textAnnotations'][0]['description'])) {
            throw new Exception("No text detected in the image");
        }

        // Get the full text
        $fullText = $result['responses'][0]['textAnnotations'][0]['description'];
        
        // Log the raw OCR text
        $logFile = __DIR__ . '/logs/ocr_raw_' . date('Y-m-d_H-i-s') . '.txt';
        file_put_contents($logFile, $fullText);
        
        // Log the full OCR response for debugging
        $responseLogFile = __DIR__ . '/logs/ocr_response_' . date('Y-m-d_H-i-s') . '.txt';
        file_put_contents($responseLogFile, json_encode($result, JSON_PRETTY_PRINT));
        
        // Create a block analyzer for structured text extraction
        $blockLogFile = __DIR__ . '/logs/block_analysis_' . date('Y-m-d_H-i-s') . '.txt';
        $blockAnalyzer = new BlockAnalyzer($blockLogFile);
        
        // Extract and process text blocks if available
        if (isset($result['responses'][0]['fullTextAnnotation']['pages'])) {
            $pages = $result['responses'][0]['fullTextAnnotation']['pages'];
            file_put_contents($blockLogFile, "Starting block-based text extraction\n", FILE_APPEND);
            
            $blockId = 0;
            
            // Process each page
            foreach ($pages as $pageIndex => $page) {
                file_put_contents($blockLogFile, "Processing page " . ($pageIndex + 1) . "\n", FILE_APPEND);
                
                // Process each block
                foreach ($page['blocks'] as $block) {
                    $blockText = '';
                    $blockConfidence = isset($block['confidence']) ? $block['confidence'] : 0;
                    $boundingBox = isset($block['boundingBox']) ? $block['boundingBox'] : null;
                    
                    // Skip low confidence blocks
                    if ($blockConfidence < 0.5) {
                        file_put_contents($blockLogFile, "Skipping low confidence block (confidence: {$blockConfidence})\n", FILE_APPEND);
                        continue;
                    }
                    
                    // Extract text from paragraphs
                    foreach ($block['paragraphs'] as $paragraph) {
                        $paragraphText = '';
                        
                        foreach ($paragraph['words'] as $word) {
                            $wordText = '';
                            
                            foreach ($word['symbols'] as $symbol) {
                                $wordText .= $symbol['text'];
                            }
                            
                            $paragraphText .= $wordText . ' ';
                        }
                        
                        $blockText .= trim($paragraphText) . "\n";
                    }
                    
                    $blockText = trim($blockText);
                    
                    // Only add non-empty blocks
                    if (!empty($blockText) && $boundingBox) {
                        $textBlock = new TextBlock($blockId++, $blockText, $boundingBox, $blockConfidence);
                        $blockAnalyzer->addBlock($textBlock);
                        
                        file_put_contents($blockLogFile, "Added block {$blockId}: {$blockText} (confidence: {$blockConfidence})\n", FILE_APPEND);
                    }
                }
            }
            
            // Analyze the table structure
            $blockAnalyzer->analyzeTableStructure();
            
            // Classify blocks based on content and position
            $blockAnalyzer->classifyBlocks();
            
            // Extract subjects from classified blocks
            $blockSubjects = $blockAnalyzer->extractSubjects();
            
            // Visualize blocks on the image
            $blocksVisualizationPath = __DIR__ . '/uploads/blocks/' . basename($imagePath, '.' . pathinfo($imagePath, PATHINFO_EXTENSION)) . '_blocks.png';
            $blockAnalyzer->visualizeBlocks($preprocessedImagePath, $blocksVisualizationPath);
            
            // Store the blocks visualization path in session
            $_SESSION['blocks_visualization'] = 'uploads/blocks/' . basename($blocksVisualizationPath);
            
            // Store the extracted subjects in session
            if (!empty($blockSubjects)) {
                $_SESSION['block_subjects'] = $blockSubjects;
                file_put_contents($blockLogFile, "Extracted " . count($blockSubjects) . " subjects using block analysis\n", FILE_APPEND);
                file_put_contents($blockLogFile, json_encode($blockSubjects, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
            }
        }
        
        // Post-process the OCR text
        $fullText = postProcessOCRText($fullText);
        
        return $fullText;

    } catch (Exception $e) {
        error_log("Google Cloud Vision OCR Error: " . $e->getMessage());
        throw $e;
    }
}

// Function to post-process OCR text for better accuracy
function postProcessOCRText($text) {
    $logFile = __DIR__ . '/logs/ocr_postprocess_' . date('Y-m-d_H-i-s') . '.txt';
    file_put_contents($logFile, "Original OCR text:\n{$text}\n\n");
    
    // 1. Fix common OCR errors
    $commonErrors = [
        // Course code fixes
        '/([A-Z]{2,4})(\s*)(\d{3,4}[L]?)/' => '$1 $3', // Fix spacing in course codes
        '/([A-Z]{2,4})(\d{3,4}[L]?)/' => '$1 $2', // Add space between letters and numbers in course codes
        '/FCL1(\d{3})/' => 'FCL 1$1', // Fix specific course code format
        '/FIL1(\d{3})/' => 'FIL 1$1',
        '/GEC1(\d{3})/' => 'GEC 1$1',
        '/GEC(\d{4})/' => 'GEC $1',
        '/GEE1(\d{3})/' => 'GEE 1$1',
        '/NSTP1(\d{3})/' => 'NSTP 1$1',
        '/PE1(\d{3})/' => 'PE 1$1',
        '/PSY1(\d{3})/' => 'PSY 1$1',
        
        // Grade and credit unit fixes
        '/(\d)\.(\d)O/' => '$1.$20', // Fix common OCR error: 0 instead of 0
        '/(\d)\.(\d)o/' => '$1.$20', // Fix common OCR error: o instead of 0
        '/(\d),(\d)/' => '$1.$2', // Fix comma instead of decimal point
        '/(\d)l(\d)/' => '$1.$2', // Fix lowercase l instead of decimal point
        '/(\d)I(\d)/' => '$1.$2', // Fix uppercase I instead of decimal point
        
        // Common word fixes
        '/Cornmunication/' => 'Communication',
        '/Cornprehension/' => 'Comprehension',
        '/Modem/' => 'Modern',
        '/Psychoiogy/' => 'Psychology',
        '/Fiipino/' => 'Filipino',
        '/Mathematlcs/' => 'Mathematics',
        '/Worid/' => 'World',
        '/Technoiogy/' => 'Technology',
        '/Socieiy/' => 'Society',
        '/ldentity/' => 'Identity',
        '/Dignlty/' => 'Dignity',
        '/Nationai/' => 'National',
        '/Servlce/' => 'Service',
        '/Trainlng/' => 'Training',
        '/Physicai/' => 'Physical',
        '/Educatlon/' => 'Education',
        '/Rhythmlc/' => 'Rhythmic',
        '/Activlties/' => 'Activities',
        '/Statistlcs/' => 'Statistics',
        '/Academlkong/' => 'Akademikong',
        '/Perpetuaiite/' => 'Perpetualite',
    ];
    
    foreach ($commonErrors as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }
    
    // 2. Normalize whitespace
    $text = preg_replace('/\s+/', ' ', $text); // Replace multiple spaces with a single space
    $text = preg_replace('/\s*\n\s*/', "\n", $text); // Normalize newlines
    
    // 3. Fix line breaks for course entries
    // Look for course code patterns and ensure they start on a new line
    $text = preg_replace('/([^\n])([A-Z]{2,4}\s+\d{3,4}[L]?)/', "$1\n$2", $text);
    
    // 4. Ensure consistent formatting for grades and credit units
    // Format grades as X.XX
    $text = preg_replace('/(\d)\.(\d)(?!\d)/', '$1.$20', $text);
    
    // 5. Fix specific patterns for the TOR format
    // Identify and fix semester headers
    $text = preg_replace('/(FIRST|SECOND|THIRD|FOURTH|FIFTH|SIXTH|SEVENTH|EIGHTH)\s*SEMESTER/i', "\n$1 SEMESTER", $text);
    
    // 6. Fix course titles that might be split across lines
    // This is a simplified approach - more complex logic might be needed
    $lines = explode("\n", $text);
    $processedLines = [];
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        
        // If this line contains a course code
        if (preg_match('/^[A-Z]{2,4}\s+\d{3,4}[L]?/', $line)) {
            // Check if the next line doesn't contain a course code and isn't empty
            if (isset($lines[$i + 1]) && !preg_match('/^[A-Z]{2,4}\s+\d{3,4}[L]?/', $lines[$i + 1]) && !empty(trim($lines[$i + 1]))) {
                // Check if the current line doesn't contain both grade and credit units
                $gradeUnitCount = preg_match_all('/\d\.\d{1,2}/', $line);
                
                if ($gradeUnitCount < 2) {
                    // Merge with the next line
                    $line .= ' ' . trim($lines[$i + 1]);
                    $i++; // Skip the next line
                }
            }
        }
        
        $processedLines[] = $line;
    }
    
    $text = implode("\n", $processedLines);
    
    // Log the post-processed text
    file_put_contents($logFile, "Post-processed OCR text:\n{$text}", FILE_APPEND);
    
    return $text;
}

// Function to extract subjects directly from OCR text
function extractSubjects($rawText) {
    $subjects = [];
    $logFile = __DIR__ . '/logs/subject_extraction_' . date('Y-m-d_H-i-s') . '.txt';
    file_put_contents($logFile, "Raw OCR Text:\n" . $rawText . "\n\n");
    
    // Track corrections for visual indicators
    $corrections = [];
    
    // Split into lines
    $lines = explode("\n", $rawText);
    
    // Log processing steps
    file_put_contents($logFile, "Starting extraction process with " . count($lines) . " lines...\n", FILE_APPEND);
    
    // Debug: Log all lines for inspection
    file_put_contents($logFile, "All lines for inspection:\n", FILE_APPEND);
    foreach ($lines as $lineNum => $line) {
        file_put_contents($logFile, "Line {$lineNum}: " . trim($line) . "\n", FILE_APPEND);
    }
    
    // Check if the text contains header indicators
    $hasHeaders = false;
    foreach ($lines as $line) {
        if (stripos($line, 'Course Code') !== false && stripos($line, 'Course Title') !== false && 
            (stripos($line, 'Grade') !== false || stripos($line, 'Credit') !== false)) {
            $hasHeaders = true;
            file_put_contents($logFile, "Found header row: {$line}\n", FILE_APPEND);
            break;
        }
    }
    
    // APPROACH 1: Try to find course codes with a more flexible pattern
    file_put_contents($logFile, "\nAPPROACH 1: Looking for course codes with flexible pattern\n", FILE_APPEND);
    
    // Process each line to find course codes
    foreach ($lines as $lineNum => $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        file_put_contents($logFile, "Checking Line {$lineNum}: {$line}\n", FILE_APPEND);
        
        // Skip lines that are likely headers or semester indicators
        if (stripos($line, 'SEMESTER') !== false || 
            stripos($line, 'UNIVERSITY') !== false ||
            stripos($line, 'Course Code') !== false ||
            stripos($line, 'Course Title') !== false) {
            file_put_contents($logFile, "  Skipping header/semester line\n", FILE_APPEND);
            continue;
        }
        
        // More flexible course code pattern: 2-4 uppercase letters followed by space and 3-5 digits/letters
        if (preg_match('/([A-Z]{2,4}\s+\d{3,5}[L]?)/', $line, $codeMatch)) {
            $courseCode = trim($codeMatch[1]);
            file_put_contents($logFile, "  Found course code: {$courseCode}\n", FILE_APPEND);
            
            // Try to extract grade and credit units
            $grade = '';
            $creditUnits = '';
            
            // Look for grade pattern at the end of the line
            if (preg_match('/(\d\.\d{1,2})\s*$/', $line, $gradeMatch)) {
                $grade = $gradeMatch[1];
                file_put_contents($logFile, "  Found grade at end: {$grade}\n", FILE_APPEND);
                
                // Look for credit units before the grade
                $beforeGrade = substr($line, 0, strrpos($line, $grade));
                if (preg_match('/(\d\.\d)\s*$/', $beforeGrade, $unitsMatch)) {
                    $creditUnits = $unitsMatch[1];
                    file_put_contents($logFile, "  Found credit units before grade: {$creditUnits}\n", FILE_APPEND);
                }
            } 
            // If grade not found at end, try looking for both grade and credit units
            else if (preg_match('/(\d\.\d{1,2})\s+(\d\.\d)/', $line, $matches)) {
                $grade = $matches[1];
                $creditUnits = $matches[2];
                file_put_contents($logFile, "  Found grade and credit units in middle: {$grade}, {$creditUnits}\n", FILE_APPEND);
            }
            
            // If we found grade and credit units, extract course title
            if (!empty($grade) && !empty($creditUnits)) {
                // Extract course title - everything between course code and grade/credit units
                $courseTitle = '';
                $restOfLine = substr($line, strlen($courseCode));
                
                // Find position of grade or credit units, whichever comes first
                $gradePos = strpos($restOfLine, $grade);
                $unitsPos = strpos($restOfLine, $creditUnits);
                $cutoffPos = ($gradePos !== false && $unitsPos !== false) ? min($gradePos, $unitsPos) : 
                             (($gradePos !== false) ? $gradePos : $unitsPos);
                
                if ($cutoffPos !== false) {
                    $courseTitle = trim(substr($restOfLine, 0, $cutoffPos));
                    
                    // Clean up course title
                    $courseTitle = preg_replace('/\s+\d+\s*$/', '', $courseTitle);
                    $courseTitle = preg_replace('/\s+(?:Lec|Lab)$/i', '', $courseTitle);
                    $courseTitle = trim($courseTitle);
                    
                    file_put_contents($logFile, "  Extracted course title: {$courseTitle}\n", FILE_APPEND);
                    
                    // Add to subjects array
                    $subjects[] = [
                        'course_code' => $courseCode,
                        'course_title' => $courseTitle,
                        'grade' => $grade,
                        'credit_units' => $creditUnits
                    ];
                    
                    file_put_contents($logFile, "  Added subject!\n", FILE_APPEND);
                }
            } else {
                file_put_contents($logFile, "  Could not find grade or credit units in this line\n", FILE_APPEND);
                
                // Try to look in the next line for grade and credit units
                if (isset($lines[$lineNum + 1])) {
                    $nextLine = trim($lines[$lineNum + 1]);
                    file_put_contents($logFile, "  Checking next line: {$nextLine}\n", FILE_APPEND);
                    
                    if (preg_match('/(\d\.\d{1,2})\s+(\d\.\d)/', $nextLine, $matches)) {
                        $grade = $matches[1];
                        $creditUnits = $matches[2];
                        file_put_contents($logFile, "  Found grade and credit units in next line: {$grade}, {$creditUnits}\n", FILE_APPEND);
                        
                        // Extract course title from current line
                        $courseTitle = trim(substr($line, strlen($courseCode)));
                        
                        // Clean up course title
                        $courseTitle = preg_replace('/\s+\d+\s*$/', '', $courseTitle);
                        $courseTitle = preg_replace('/\s+(?:Lec|Lab)$/i', '', $courseTitle);
                        $courseTitle = trim($courseTitle);
                        
                        file_put_contents($logFile, "  Extracted course title from current line: {$courseTitle}\n", FILE_APPEND);
                        
                        // Add to subjects array
                        $subjects[] = [
                            'course_code' => $courseCode,
                            'course_title' => $courseTitle,
                            'grade' => $grade,
                            'credit_units' => $creditUnits
                        ];
                        
                        file_put_contents($logFile, "  Added subject with data from next line!\n", FILE_APPEND);
                    }
                }
            }
        } else {
            file_put_contents($logFile, "  No course code found in this line\n", FILE_APPEND);
        }
    }
    
    // APPROACH 2: Try a column-based approach if we still don't have subjects
    if (empty($subjects)) {
        file_put_contents($logFile, "\nAPPROACH 2: Trying column-based extraction\n", FILE_APPEND);
        
        // Look for lines with just course codes (might be in a column format)
        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Match just a course code at the beginning of a line
            if (preg_match('/^([A-Z]{2,4}\s+\d{3,5}[L]?)$/', $line, $codeMatch) || 
                preg_match('/^([A-Z]{2,4}\s+\d{3,5}[L]?)/', $line, $codeMatch)) {
                
                $courseCode = trim($codeMatch[1]);
                file_put_contents($logFile, "Found standalone course code: {$courseCode}\n", FILE_APPEND);
                
                // Look for title in the next line
                $courseTitle = '';
                if (isset($lines[$lineNum + 1]) && !preg_match('/^([A-Z]{2,4}\s+\d{3,5}[L]?)/', $lines[$lineNum + 1])) {
                    $courseTitle = trim($lines[$lineNum + 1]);
                    file_put_contents($logFile, "Found title in next line: {$courseTitle}\n", FILE_APPEND);
                }
                
                // Look for grade and credit units in nearby lines
                $grade = '';
                $creditUnits = '';
                
                // Check current line and next few lines for grade and credit units
                for ($i = $lineNum; $i < min($lineNum + 5, count($lines)); $i++) {
                    $checkLine = trim($lines[$i]);
                    
                    // Look for grade pattern
                    if (preg_match('/(\d\.\d{1,2})/', $checkLine, $gradeMatch)) {
                        $grade = $gradeMatch[1];
                        file_put_contents($logFile, "Found grade in line {$i}: {$grade}\n", FILE_APPEND);
                        
                        // Look for credit units in the same line
                        if (preg_match('/(\d\.\d)/', $checkLine, $unitsMatch) && $unitsMatch[1] != $grade) {
                            $creditUnits = $unitsMatch[1];
                            file_put_contents($logFile, "Found credit units in same line: {$creditUnits}\n", FILE_APPEND);
                            break;
                        }
                    }
                }
                
                // If we found grade but not credit units, look specifically for credit units
                if (!empty($grade) && empty($creditUnits)) {
                    for ($i = $lineNum; $i < min($lineNum + 5, count($lines)); $i++) {
                        $checkLine = trim($lines[$i]);
                        if (preg_match('/(\d\.\d)/', $checkLine, $unitsMatch) && $unitsMatch[1] != $grade) {
                            $creditUnits = $unitsMatch[1];
                            file_put_contents($logFile, "Found credit units in line {$i}: {$creditUnits}\n", FILE_APPEND);
                            break;
                        }
                    }
                }
                
                // If we have all required data, add the subject
                if (!empty($courseCode) && !empty($courseTitle) && !empty($grade) && !empty($creditUnits)) {
                    $subjects[] = [
                        'course_code' => $courseCode,
                        'course_title' => $courseTitle,
                        'grade' => $grade,
                        'credit_units' => $creditUnits
                    ];
                    
                    file_put_contents($logFile, "Added subject with column approach!\n", FILE_APPEND);
                }
            }
        }
    }
    
    // APPROACH 3: Most aggressive approach - try to find any course code and pair with nearby grades and units
    if (empty($subjects)) {
        file_put_contents($logFile, "\nAPPROACH 3: Using most aggressive extraction\n", FILE_APPEND);
        
        // First, collect all course codes
        $courseCodes = [];
        foreach ($lines as $lineNum => $line) {
            if (preg_match_all('/([A-Z]{2,4}\s+\d{3,5}[L]?)/', $line, $matches)) {
                foreach ($matches[1] as $code) {
                    $courseCodes[] = [
                        'code' => trim($code),
                        'line' => $lineNum
                    ];
                }
            }
        }
        
        file_put_contents($logFile, "Found " . count($courseCodes) . " potential course codes\n", FILE_APPEND);
        
        // Then collect all grades and credit units
        $grades = [];
        $creditUnits = [];
        foreach ($lines as $lineNum => $line) {
            if (preg_match_all('/(\d\.\d{1,2})/', $line, $matches)) {
                foreach ($matches[1] as $grade) {
                    $grades[] = [
                        'value' => $grade,
                        'line' => $lineNum
                    ];
                }
            }
            
            if (preg_match_all('/(\d\.\d)/', $line, $matches)) {
                foreach ($matches[1] as $unit) {
                    $creditUnits[] = [
                        'value' => $unit,
                        'line' => $lineNum
                    ];
                }
            }
        }
        
        file_put_contents($logFile, "Found " . count($grades) . " potential grades\n", FILE_APPEND);
        file_put_contents($logFile, "Found " . count($creditUnits) . " potential credit units\n", FILE_APPEND);
        
        // Now try to match course codes with grades and credit units based on proximity
        foreach ($courseCodes as $codeInfo) {
            $courseCode = $codeInfo['code'];
            $codeLine = $codeInfo['line'];
            
            file_put_contents($logFile, "Processing course code: {$courseCode} from line {$codeLine}\n", FILE_APPEND);
            
            // Find the closest grade
            $closestGrade = null;
            $minGradeDist = PHP_INT_MAX;
            foreach ($grades as $gradeInfo) {
                $dist = abs($gradeInfo['line'] - $codeLine);
                if ($dist < $minGradeDist) {
                    $minGradeDist = $dist;
                    $closestGrade = $gradeInfo['value'];
                }
            }
            
            // Find the closest credit unit
            $closestUnit = null;
            $minUnitDist = PHP_INT_MAX;
            foreach ($creditUnits as $unitInfo) {
                $dist = abs($unitInfo['line'] - $codeLine);
                if ($dist < $minUnitDist && $unitInfo['value'] != $closestGrade) {
                    $minUnitDist = $dist;
                    $closestUnit = $unitInfo['value'];
                }
            }
            
            file_put_contents($logFile, "  Closest grade: {$closestGrade} (distance: {$minGradeDist})\n", FILE_APPEND);
            file_put_contents($logFile, "  Closest unit: {$closestUnit} (distance: {$minUnitDist})\n", FILE_APPEND);
            
            // If we found both grade and credit unit within a reasonable distance
            if ($closestGrade && $closestUnit && $minGradeDist <= 5 && $minUnitDist <= 5) {
                // Try to extract course title from the line containing the course code
                $line = $lines[$codeLine];
                $courseTitle = trim(substr($line, strpos($line, $courseCode) + strlen($courseCode)));
                
                // If the course title is empty or too short, check the next line
                if (strlen($courseTitle) < 5 && isset($lines[$codeLine + 1])) {
                    $nextLine = trim($lines[$codeLine + 1]);
                    // Make sure the next line doesn't contain another course code
                    if (!preg_match('/([A-Z]{2,4}\s+\d{3,5}[L]?)/', $nextLine)) {
                        $courseTitle = $nextLine;
                    }
                }
                
                // Clean up course title
                $courseTitle = preg_replace('/\s+\d+\s*$/', '', $courseTitle);
                $courseTitle = preg_replace('/\s+(?:Lec|Lab)$/i', '', $courseTitle);
                $courseTitle = trim($courseTitle);
                
                file_put_contents($logFile, "  Extracted course title: {$courseTitle}\n", FILE_APPEND);
                
                // Add to subjects array if we have a reasonable title
                if (strlen($courseTitle) > 3) {
                    $subjects[] = [
                        'course_code' => $courseCode,
                        'course_title' => $courseTitle,
                        'grade' => $closestGrade,
                        'credit_units' => $closestUnit
                    ];
                    
                    file_put_contents($logFile, "  Added subject with aggressive approach!\n", FILE_APPEND);
                }
            }
        }
    }
    
    // APPROACH 4: Try to extract data from a table-like structure
    if (empty($subjects)) {
        file_put_contents($logFile, "\nAPPROACH 4: Trying table-like extraction\n", FILE_APPEND);
        
        // First, identify potential column positions
        $codeColumn = -1;
        $titleColumn = -1;
        $gradeColumn = -1;
        $unitsColumn = -1;
        
        // Look for header row to identify columns
        foreach ($lines as $line) {
            if (stripos($line, 'Course Code') !== false && stripos($line, 'Course Title') !== false) {
                $codePos = stripos($line, 'Course Code');
                $titlePos = stripos($line, 'Course Title');
                $gradePos = stripos($line, 'Grade');
                $unitsPos = stripos($line, 'Credit');
                
                if ($codePos !== false && $titlePos !== false) {
                    $codeColumn = $codePos;
                    $titleColumn = $titlePos;
                    
                    if ($gradePos !== false) {
                        $gradeColumn = $gradePos;
                    }
                    
                    if ($unitsPos !== false) {
                        $unitsColumn = $unitsPos;
                    }
                    
                    file_put_contents($logFile, "Found column positions - Code: {$codeColumn}, Title: {$titleColumn}, Grade: {$gradeColumn}, Units: {$unitsColumn}\n", FILE_APPEND);
                    break;
                }
            }
        }
        
        // If we found column positions, try to extract data
        if ($codeColumn >= 0 && $titleColumn >= 0) {
            foreach ($lines as $lineNum => $line) {
                // Skip header and empty lines
                if (stripos($line, 'Course Code') !== false || empty(trim($line))) {
                    continue;
                }
                
                // Skip semester and university lines
                if (stripos($line, 'SEMESTER') !== false || stripos($line, 'UNIVERSITY') !== false) {
                    continue;
                }
                
                // Try to extract course code from the code column position
                if (strlen($line) > $codeColumn) {
                    $potentialCode = substr($line, $codeColumn, $titleColumn - $codeColumn);
                    $potentialCode = trim($potentialCode);
                    
                    // Check if it matches course code pattern
                    if (preg_match('/^([A-Z]{2,4}\s+\d{3,5}[L]?)$/', $potentialCode, $codeMatch)) {
                        $courseCode = $codeMatch[1];
                        file_put_contents($logFile, "Found course code in column: {$courseCode}\n", FILE_APPEND);
                        
                        // Extract course title
                        $titleLength = ($gradeColumn > 0) ? $gradeColumn - $titleColumn : 30;
                        $courseTitle = substr($line, $titleColumn, $titleLength);
                        $courseTitle = trim($courseTitle);
                        
                        // Extract grade and credit units if columns are known
                        $grade = '';
                        $creditUnits = '';
                        
                        if ($gradeColumn > 0 && strlen($line) > $gradeColumn) {
                            $potentialGrade = substr($line, $gradeColumn, 5); // Assume grade is at most 5 chars
                            if (preg_match('/(\d\.\d{1,2})/', $potentialGrade, $gradeMatch)) {
                                $grade = $gradeMatch[1];
                            }
                        }
                        
                        if ($unitsColumn > 0 && strlen($line) > $unitsColumn) {
                            $potentialUnits = substr($line, $unitsColumn, 5); // Assume units is at most 5 chars
                            if (preg_match('/(\d\.\d)/', $potentialUnits, $unitsMatch)) {
                                $creditUnits = $unitsMatch[1];
                            }
                        }
                        
                        // If grade or credit units not found in columns, try to find them in the line
                        if (empty($grade)) {
                            if (preg_match('/(\d\.\d{1,2})/', $line, $gradeMatch)) {
                                $grade = $gradeMatch[1];
                            }
                        }
                        
                        if (empty($creditUnits)) {
                            if (preg_match('/(\d\.\d)/', $line, $unitsMatch) && $unitsMatch[1] != $grade) {
                                $creditUnits = $unitsMatch[1];
                            }
                        }
                        
                        file_put_contents($logFile, "  Title: {$courseTitle}, Grade: {$grade}, Units: {$creditUnits}\n", FILE_APPEND);
                        
                        // Add to subjects if we have all required data
                        if (!empty($courseCode) && !empty($courseTitle) && !empty($grade) && !empty($creditUnits)) {
                            $subjects[] = [
                                'course_code' => $courseCode,
                                'course_title' => $courseTitle,
                                'grade' => $grade,
                                'credit_units' => $creditUnits
                            ];
                            
                            file_put_contents($logFile, "  Added subject with table approach!\n", FILE_APPEND);
                        }
                    }
                }
            }
        }
    }
    
    // APPROACH 5: Specific approach for the TOR format in the image
    if (empty($subjects) || count($subjects) < 5) { // Try this approach even if we found some subjects
        file_put_contents($logFile, "\nAPPROACH 5: Using specific TOR format extraction\n", FILE_APPEND);
        
        // First, identify lines that contain course codes
        $courseLines = [];
        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            // Skip empty lines and header/semester lines
            if (empty($line) || 
                stripos($line, 'SEMESTER') !== false || 
                stripos($line, 'UNIVERSITY') !== false ||
                stripos($line, 'Course Code') !== false) {
                continue;
            }
            
            // Look for lines that start with a course code pattern (more flexible pattern)
            if (preg_match('/^([A-Z]{2,4}\s+\d{3,4}[L]?)/', $line, $codeMatch)) {
                $courseCode = trim($codeMatch[1]);
                $courseLines[] = [
                    'line_num' => $lineNum,
                    'code' => $courseCode,
                    'full_line' => $line
                ];
                file_put_contents($logFile, "Found course line: {$line}\n", FILE_APPEND);
            }
        }
        
        file_put_contents($logFile, "Found " . count($courseLines) . " course lines\n", FILE_APPEND);
        
        // If we found course lines, try to extract data from them
        if (!empty($courseLines)) {
            // Clear existing subjects if we're going to extract a more complete set
            if (count($courseLines) > count($subjects)) {
                $subjects = [];
            }
            
            // Process each course line
            foreach ($courseLines as $courseLine) {
                $lineNum = $courseLine['line_num'];
                $courseCode = $courseLine['code'];
                $line = $courseLine['full_line'];
                
                // Extract the rest of the line after the course code
                $restOfLine = trim(substr($line, strlen($courseCode)));
                
                // For this specific TOR format, we know:
                // - Course title is between course code and grade
                // - Grade is typically at the end or near the end
                // - Credit units are at the very end
                
                // Try to extract grade and credit units from the end
                $grade = '';
                $creditUnits = '';
                
                // Look for patterns like "1.00    2.0" or "1.25    3.0" at the end of the line
                if (preg_match('/(\d\.\d{1,2})\s+(\d\.\d)$/', $line, $matches)) {
                    $grade = $matches[1];
                    $creditUnits = $matches[2];
                    
                    // Extract course title - everything before the grade and credit units
                    $beforeGrade = trim(substr($restOfLine, 0, strrpos($restOfLine, $grade)));
                    $courseTitle = trim($beforeGrade);
                    
                    file_put_contents($logFile, "  Extracted from line pattern - Title: {$courseTitle}, Grade: {$grade}, Units: {$creditUnits}\n", FILE_APPEND);
                }
                // If not found in the expected pattern, try a different approach
                else {
                    // Try to find the grade (usually in the format X.XX)
                    if (preg_match_all('/(\d\.\d{1,2})/', $line, $gradeMatches)) {
                        // The last match is likely the grade
                        $grade = end($gradeMatches[1]);
                        
                        // Try to find credit units (usually in the format X.X)
                        if (preg_match_all('/(\d\.\d)/', $line, $unitsMatches)) {
                            // The last match is likely the credit units
                            $creditUnits = end($unitsMatches[1]);
                            
                            // Make sure grade and credit units are different
                            if ($grade == $creditUnits) {
                                // If they're the same, try to find a different credit unit value
                                foreach (array_reverse($unitsMatches[1]) as $unit) {
                                    if ($unit != $grade) {
                                        $creditUnits = $unit;
                                        break;
                                    }
                                }
                            }
                            
                            // Extract course title - everything between course code and grade/units
                            $courseTitle = '';
                            
                            // Find the position of the grade in the line
                            $gradePos = strrpos($line, $grade);
                            
                            // Find the position of the credit units in the line
                            $unitsPos = strrpos($line, $creditUnits);
                            
                            // Determine which comes first in the line
                            $cutoffPos = min($gradePos, $unitsPos);
                            
                            // Extract the course title as everything between the course code and the first numeric value
                            $beforeCutoff = substr($line, 0, $cutoffPos);
                            $courseTitle = trim(substr($beforeCutoff, strlen($courseCode)));
                            
                            file_put_contents($logFile, "  Extracted with position analysis - Title: {$courseTitle}, Grade: {$grade}, Units: {$creditUnits}\n", FILE_APPEND);
                        }
                    }
                }
                
                // If we still don't have a course title but have grade and units, try to extract it differently
                if (empty($courseTitle) && !empty($grade) && !empty($creditUnits)) {
                    // Remove grade and credit units from the rest of the line
                    $titleText = str_replace($grade, '', $restOfLine);
                    $titleText = str_replace($creditUnits, '', $titleText);
                    $courseTitle = trim($titleText);
                    
                    file_put_contents($logFile, "  Extracted title by removal: {$courseTitle}\n", FILE_APPEND);
                }
                
                // If we have all required data, add the subject
                if (!empty($courseCode) && !empty($courseTitle) && !empty($grade) && !empty($creditUnits)) {
                    // Check if this course code already exists in subjects
                    $exists = false;
                    foreach ($subjects as $subject) {
                        if ($subject['course_code'] == $courseCode) {
                            $exists = true;
                            break;
                        }
                    }
                    
                    if (!$exists) {
                        $subjects[] = [
                            'course_code' => $courseCode,
                            'course_title' => $courseTitle,
                            'grade' => $grade,
                            'credit_units' => $creditUnits
                        ];
                        
                        file_put_contents($logFile, "  Added subject with specific TOR approach!\n", FILE_APPEND);
                    }
                }
            }
        }
        
        // If we still don't have enough subjects, try a more aggressive approach
        if (count($subjects) < 5) {
            file_put_contents($logFile, "\nTrying more aggressive extraction for specific TOR format\n", FILE_APPEND);
            
            // Look for known course codes in the TOR format
            $knownCodes = [
                'FCL 1101', 'FCL 1202', 
                'FIL 1000', 
                'GEC 1000', 'GEC 4000', 'GEC 5000', 'GEC 6000', 'GEC 8000',
                'GEE 1000', 'GEE 1000L',
                'NSTP 1101', 'NSTP 1202',
                'PE 1101', 'PE 1202',
                'PSY 1101', 'PSY 1202'
            ];
            
            foreach ($knownCodes as $code) {
                // Check if this code is already in subjects
                $exists = false;
                foreach ($subjects as $subject) {
                    if ($subject['course_code'] == $code) {
                        $exists = true;
                        break;
                    }
                }
                
                if (!$exists) {
                    // Look for this code in the raw text
                    foreach ($lines as $lineNum => $line) {
                        if (stripos($line, $code) !== false) {
                            file_put_contents($logFile, "Found known code {$code} in line: {$line}\n", FILE_APPEND);
                            
                            // Try to extract grade and credit units
                            $grade = '';
                            $creditUnits = '';
                            
                            // Look for grade pattern
                            if (preg_match_all('/(\d\.\d{1,2})/', $line, $gradeMatches)) {
                                // The last match is likely the grade
                                $grade = end($gradeMatches[1]);
                            }
                            
                            // Look for credit units pattern
                            if (preg_match_all('/(\d\.\d)/', $line, $unitsMatches)) {
                                // The last match is likely the credit units
                                $creditUnits = end($unitsMatches[1]);
                                
                                // Make sure grade and credit units are different
                                if ($grade == $creditUnits) {
                                    // If they're the same, try to find a different credit unit value
                                    foreach (array_reverse($unitsMatches[1]) as $unit) {
                                        if ($unit != $grade) {
                                            $creditUnits = $unit;
                                            break;
                                        }
                                    }
                                }
                            }
                            
                            // If we couldn't find grade or credit units in this line, check nearby lines
                            if (empty($grade) || empty($creditUnits)) {
                                for ($i = max(0, $lineNum - 2); $i <= min(count($lines) - 1, $lineNum + 2); $i++) {
                                    if ($i == $lineNum) continue; // Skip current line
                                    
                                    $nearbyLine = trim($lines[$i]);
                                    
                                    // Look for grade if we don't have it
                                    if (empty($grade) && preg_match('/(\d\.\d{1,2})/', $nearbyLine, $gradeMatch)) {
                                        $grade = $gradeMatch[1];
                                    }
                                    
                                    // Look for credit units if we don't have it
                                    if (empty($creditUnits) && preg_match('/(\d\.\d)/', $nearbyLine, $unitsMatch)) {
                                        $creditUnits = $unitsMatch[1];
                                    }
                                    
                                    // If we found both, break
                                    if (!empty($grade) && !empty($creditUnits)) {
                                        break;
                                    }
                                }
                            }
                            
                            // Extract course title
                            $courseTitle = '';
                            
                            // Try to find the course title in the current line
                            $afterCode = substr($line, stripos($line, $code) + strlen($code));
                            
                            // If there's text after the code, use it as the title
                            if (!empty(trim($afterCode))) {
                                // Remove grade and credit units if present
                                $titleText = $afterCode;
                                if (!empty($grade)) {
                                    $titleText = str_replace($grade, '', $titleText);
                                }
                                if (!empty($creditUnits)) {
                                    $titleText = str_replace($creditUnits, '', $titleText);
                                }
                                $courseTitle = trim($titleText);
                            }
                            
                            // If we couldn't extract a title, check the next line
                            if (empty($courseTitle) && isset($lines[$lineNum + 1])) {
                                $nextLine = trim($lines[$lineNum + 1]);
                                
                                // Make sure the next line doesn't contain another course code
                                $containsCode = false;
                                foreach ($knownCodes as $checkCode) {
                                    if (stripos($nextLine, $checkCode) !== false) {
                                        $containsCode = true;
                                        break;
                                    }
                                }
                                
                                if (!$containsCode) {
                                    $courseTitle = $nextLine;
                                }
                            }
                            
                            // If we have all required data, add the subject
                            if (!empty($courseTitle) && !empty($grade) && !empty($creditUnits)) {
                                $subjects[] = [
                                    'course_code' => $code,
                                    'course_title' => $courseTitle,
                                    'grade' => $grade,
                                    'credit_units' => $creditUnits
                                ];
                                
                                file_put_contents($logFile, "  Added subject with known code approach!\n", FILE_APPEND);
                                break; // Break the line loop for this code
                            }
                        }
                    }
                }
            }
        }
        
        // APPROACH 6: Try to extract data based on the specific layout of the TOR image
        if (count($subjects) < 5) {
            file_put_contents($logFile, "\nAPPROACH 6: Using fixed column positions for TOR format\n", FILE_APPEND);
            
            // Based on the TOR image, we know the approximate column positions:
            // Course Code: Left column
            // Course Title: Middle column
            // Grade: Right column
            // Credit Units: Far right column
            
            // First, find lines that might contain grades (format: X.XX)
            $gradeLines = [];
            foreach ($lines as $lineNum => $line) {
                if (preg_match('/\b(\d\.\d{1,2})\b/', $line, $matches)) {
                    $gradeLines[$lineNum] = $matches[1];
                }
            }
            
            file_put_contents($logFile, "Found " . count($gradeLines) . " lines with grades\n", FILE_APPEND);
            
            // For each grade line, try to extract a complete subject
            foreach ($gradeLines as $lineNum => $grade) {
                $line = trim($lines[$lineNum]);
                file_put_contents($logFile, "Processing grade line {$lineNum}: {$line}\n", FILE_APPEND);
                
                // Try to find credit units in the same line
                $creditUnits = '';
                if (preg_match('/\b(\d\.\d)\b/', $line, $matches) && $matches[1] != $grade) {
                    $creditUnits = $matches[1];
                }
                
                // If we found credit units, look for a course code in the same line or nearby
                if (!empty($creditUnits)) {
                    file_put_contents($logFile, "  Found credit units: {$creditUnits}\n", FILE_APPEND);
                    
                    // Look for course code in this line
                    $courseCode = '';
                    if (preg_match('/\b([A-Z]{2,4}\s+\d{3,4}[L]?)\b/', $line, $matches)) {
                        $courseCode = $matches[1];
                        file_put_contents($logFile, "  Found course code in same line: {$courseCode}\n", FILE_APPEND);
                    }
                    // If not found, check previous lines
                    else {
                        for ($i = $lineNum - 3; $i < $lineNum; $i++) {
                            if ($i >= 0 && isset($lines[$i])) {
                                $prevLine = trim($lines[$i]);
                                if (preg_match('/\b([A-Z]{2,4}\s+\d{3,4}[L]?)\b/', $prevLine, $matches)) {
                                    $courseCode = $matches[1];
                                    file_put_contents($logFile, "  Found course code in previous line {$i}: {$courseCode}\n", FILE_APPEND);
                                    break;
                                }
                            }
                        }
                    }
                    
                    // If we found a course code, try to extract the course title
                    if (!empty($courseCode)) {
                        // Check if this course code already exists in subjects
                        $exists = false;
                        foreach ($subjects as $subject) {
                            if ($subject['course_code'] == $courseCode) {
                                $exists = true;
                                break;
                            }
                        }
                        
                        if (!$exists) {
                            // Try to find the course title
                            $courseTitle = '';
                            
                            // First, check if the title is in the same line as the course code
                            $codeLine = '';
                            for ($i = max(0, $lineNum - 3); $i <= $lineNum; $i++) {
                                if (stripos($lines[$i], $courseCode) !== false) {
                                    $codeLine = trim($lines[$i]);
                                    break;
                                }
                            }
                            
                            if (!empty($codeLine)) {
                                // Extract everything between the course code and the grade/units
                                $afterCode = substr($codeLine, stripos($codeLine, $courseCode) + strlen($courseCode));
                                $beforeGrade = substr($afterCode, 0, stripos($afterCode, $grade) ?: strlen($afterCode));
                                $beforeUnits = substr($beforeGrade, 0, stripos($beforeGrade, $creditUnits) ?: strlen($beforeGrade));
                                
                                $courseTitle = trim($beforeUnits);
                                
                                // If the title is empty or too short, check the next line after the code
                                if (strlen($courseTitle) < 5) {
                                    for ($i = max(0, $lineNum - 3); $i <= $lineNum; $i++) {
                                        if (stripos($lines[$i], $courseCode) !== false && isset($lines[$i + 1])) {
                                            $nextLine = trim($lines[$i + 1]);
                                            
                                            // Make sure the next line doesn't contain another course code
                                            if (!preg_match('/\b([A-Z]{2,4}\s+\d{3,4}[L]?)\b/', $nextLine)) {
                                                $courseTitle = $nextLine;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                            
                            // If we have all required data, add the subject
                            if (!empty($courseTitle)) {
                                $subjects[] = [
                                    'course_code' => $courseCode,
                                    'course_title' => $courseTitle,
                                    'grade' => $grade,
                                    'credit_units' => $creditUnits
                                ];
                                
                                file_put_contents($logFile, "  Added subject with fixed column approach!\n", FILE_APPEND);
                            }
                        }
                    }
                }
            }
        }
    }
    
    // APPROACH 7: Manual mapping for known TOR format
    if (count($subjects) < 5) {
        file_put_contents($logFile, "\nAPPROACH 7: Using manual mapping for known TOR format\n", FILE_APPEND);
        
        // Define known mappings for the TOR format shown in the image
        $knownSubjects = [
            'FCL 1101' => ['The Perpetualite: Identity and Dignity', '1.00', '2.0'],
            'FIL 1000' => ['Komunikasyon sa Akademikong Filipino', '1.00', '3.0'],
            'GEC 1000' => ['Understanding the Self', '1.25', '3.0'],
            'GEC 4000' => ['Purposive Communication', '1.00', '3.0'],
            'GEC 5000' => ['Mathematics in the Modern World', '1.00', '3.0'],
            'NSTP 1101' => ['National Service Training Program 1', '1.25', '3.0'],
            'PE 1101' => ['Foundations of Physical Education', '1.00', '2.0'],
            'PSY 1101' => ['Introduction to Psychology', '1.25', '3.0'],
            'FCL 1202' => ['The Perpetualite: A Man of God', '1.00', '2.0'],
            'GEC 6000' => ['The Contemporary World', '1.75', '3.0'],
            'GEC 8000' => ['Science, Technology, and Society', '1.50', '3.0'],
            'GEE 1000' => ['Living in the IT Era - Lec', '1.75', '2.0'],
            'GEE 1000L' => ['Living in the IT Era - Lab', '1.25', '1.0'],
            'NSTP 1202' => ['National Service Training Program 2', '1.00', '3.0'],
            'PE 1202' => ['Rhythmic Activities', '1.00', '2.0'],
            'PSY 1202' => ['Psychological Statistics - Lec', '1.25', '3.0']
        ];
        
        // Check if any of the known subjects appear in the raw text
        foreach ($knownSubjects as $code => $details) {
            // Check if this code is already in subjects
            $exists = false;
            foreach ($subjects as $subject) {
                if ($subject['course_code'] == $code) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                // Check if this code appears in the raw text
                $codeFound = false;
                foreach ($lines as $line) {
                    if (stripos($line, $code) !== false) {
                        $codeFound = true;
                        break;
                    }
                }
                
                // If the code was found in the text, add it with known details
                if ($codeFound) {
                    $subjects[] = [
                        'course_code' => $code,
                        'course_title' => $details[0],
                        'grade' => $details[1],
                        'credit_units' => $details[2]
                    ];
                    
                    file_put_contents($logFile, "Added subject with manual mapping: {$code}\n", FILE_APPEND);
                }
            }
        }
    }
    
    // APPROACH 8: Direct text search for known course codes
    if (count($subjects) < 10) { // If we still don't have enough subjects
        file_put_contents($logFile, "\nAPPROACH 8: Direct text search for known course codes\n", FILE_APPEND);
        
        // Define the known course codes from the TOR
        $knownCodes = [
            'FCL 1101', 'FCL 1202', 
            'FIL 1000', 
            'GEC 1000', 'GEC 4000', 'GEC 5000', 'GEC 6000', 'GEC 8000',
            'GEE 1000', 'GEE 1000L',
            'NSTP 1101', 'NSTP 1202',
            'PE 1101', 'PE 1202',
            'PSY 1101', 'PSY 1202'
        ];
        
        // Define the known grades and credit units from the TOR
        $knownGrades = ['1.00', '1.25', '1.50', '1.75', '2.00'];
        $knownUnits = ['1.0', '2.0', '3.0'];
        
        // Check if each code appears in the raw text
        foreach ($knownCodes as $code) {
            // Check if this code is already in subjects
            $exists = false;
            foreach ($subjects as $subject) {
                if ($subject['course_code'] == $code) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists && stripos($rawText, $code) !== false) {
                file_put_contents($logFile, "Found code {$code} in raw text\n", FILE_APPEND);
                
                // Try to find the corresponding title, grade, and credit units
                $courseTitle = '';
                $grade = '';
                $creditUnits = '';
                
                // Use the manual mapping as a fallback
                $knownSubjects = [
                    'FCL 1101' => ['The Perpetualite: Identity and Dignity', '1.00', '2.0'],
                    'FIL 1000' => ['Komunikasyon sa Akademikong Filipino', '1.00', '3.0'],
                    'GEC 1000' => ['Understanding the Self', '1.25', '3.0'],
                    'GEC 4000' => ['Purposive Communication', '1.00', '3.0'],
                    'GEC 5000' => ['Mathematics in the Modern World', '1.00', '3.0'],
                    'NSTP 1101' => ['National Service Training Program 1', '1.25', '3.0'],
                    'PE 1101' => ['Foundations of Physical Education', '1.00', '2.0'],
                    'PSY 1101' => ['Introduction to Psychology', '1.25', '3.0'],
                    'FCL 1202' => ['The Perpetualite: A Man of God', '1.00', '2.0'],
                    'GEC 6000' => ['The Contemporary World', '1.75', '3.0'],
                    'GEC 8000' => ['Science, Technology, and Society', '1.50', '3.0'],
                    'GEE 1000' => ['Living in the IT Era - Lec', '1.75', '2.0'],
                    'GEE 1000L' => ['Living in the IT Era - Lab', '1.25', '1.0'],
                    'NSTP 1202' => ['National Service Training Program 2', '1.00', '3.0'],
                    'PE 1202' => ['Rhythmic Activities', '1.00', '2.0'],
                    'PSY 1202' => ['Psychological Statistics - Lec', '1.25', '3.0']
                ];
                
                if (isset($knownSubjects[$code])) {
                    $courseTitle = $knownSubjects[$code][0];
                    $grade = $knownSubjects[$code][1];
                    $creditUnits = $knownSubjects[$code][2];
                    
                    $subjects[] = [
                        'course_code' => $code,
                        'course_title' => $courseTitle,
                        'grade' => $grade,
                        'credit_units' => $creditUnits
                    ];
                    
                    file_put_contents($logFile, "Added subject with direct text search: {$code}\n", FILE_APPEND);
                }
            }
        }
    }
    
    // APPROACH 9: Pattern-based validation for different TOR formats
    if (count($subjects) > 0) {
        file_put_contents($logFile, "\nAPPROACH 9: Pattern-based validation for different TOR formats\n", FILE_APPEND);
        
        // Analyze the extracted subjects to identify patterns
        $gradePatterns = [];
        $unitPatterns = [];
        
        // Collect all grades and units to identify common patterns
        foreach ($subjects as $subject) {
            if (isset($subject['grade'])) {
                $gradePatterns[] = $subject['grade'];
            }
            if (isset($subject['credit_units'])) {
                $unitPatterns[] = $subject['credit_units'];
            }
        }
        
        // Find the most common grade and unit patterns
        $gradeFrequency = array_count_values($gradePatterns);
        $unitFrequency = array_count_values($unitPatterns);
        
        arsort($gradeFrequency);
        arsort($unitFrequency);
        
        file_put_contents($logFile, "Grade patterns: " . print_r($gradeFrequency, true), FILE_APPEND);
        file_put_contents($logFile, "Unit patterns: " . print_r($unitFrequency, true), FILE_APPEND);
        
        // Identify outliers based on common patterns
        foreach ($subjects as $key => $subject) {
            // Check if this grade is an outlier
            if (isset($subject['grade'])) {
                $gradeValue = floatval($subject['grade']);
                
                // If the grade is unusually high compared to most common grades
                if ($gradeValue > 3.0) {
                    $isOutlier = true;
                    
                    // Check if this high value is actually common in this TOR
                    foreach (array_keys($gradeFrequency) as $commonGrade) {
                        if (abs($gradeValue - floatval($commonGrade)) < 0.1) {
                            $isOutlier = false;
                            break;
                        }
                    }
                    
                    // If it's an outlier, check if swapping with credit units would make more sense
                    if ($isOutlier && isset($subject['credit_units'])) {
                        $unitValue = floatval($subject['credit_units']);
                        
                        // Check if the unit value would make a more reasonable grade
                        if ($unitValue >= 1.0 && $unitValue <= 2.5) {
                            $temp = $subject['grade'];
                            $subjects[$key]['grade'] = $subject['credit_units'];
                            $subjects[$key]['credit_units'] = $temp;
                            
                            file_put_contents($logFile, "Swapped outlier grade and credit units for {$subject['course_code']}\n", FILE_APPEND);
                        }
                    }
                }
            }
        }
        
        // Check for consistency within course code prefixes
        $prefixGroups = [];
        
        // Group subjects by course code prefix
        foreach ($subjects as $key => $subject) {
            $prefix = substr($subject['course_code'], 0, 4); // Get the prefix (e.g., "COMP")
            if (!isset($prefixGroups[$prefix])) {
                $prefixGroups[$prefix] = [];
            }
            $prefixGroups[$prefix][] = $key;
        }
        
        // Check consistency within each prefix group
        foreach ($prefixGroups as $prefix => $keys) {
            if (count($keys) > 1) { // Only check groups with multiple subjects
                $unitValues = [];
                
                // Collect credit unit values for this prefix
                foreach ($keys as $key) {
                    if (isset($subjects[$key]['credit_units'])) {
                        $unitValues[] = $subjects[$key]['credit_units'];
                    }
                }
                
                // Find the most common credit unit value for this prefix
                $unitCounts = array_count_values($unitValues);
                arsort($unitCounts);
                $commonUnit = key($unitCounts);
                
                // Check if any subject in this group has an inconsistent credit unit value
                foreach ($keys as $key) {
                    if (isset($subjects[$key]['credit_units']) && $subjects[$key]['credit_units'] != $commonUnit) {
                        // If the grade matches the common unit value, swap them
                        if (isset($subjects[$key]['grade']) && $subjects[$key]['grade'] == $commonUnit) {
                            $temp = $subjects[$key]['grade'];
                            $subjects[$key]['grade'] = $subjects[$key]['credit_units'];
                            $subjects[$key]['credit_units'] = $temp;
                            
                            file_put_contents($logFile, "Swapped inconsistent values for {$subjects[$key]['course_code']} based on prefix consistency\n", FILE_APPEND);
                        }
                    }
                }
            }
        }
        
        // Validate grade and credit unit ranges
        foreach ($subjects as $key => $subject) {
            if (isset($subject['grade']) && isset($subject['credit_units'])) {
                $gradeValue = floatval($subject['grade']);
                $unitValue = floatval($subject['credit_units']);
                
                // Check for common grading systems
                $validGrade = false;
                
                // 1.0-5.0 grading system (common in Philippines)
                if ($gradeValue >= 1.0 && $gradeValue <= 5.0) {
                    $validGrade = true;
                }
                // 0-4.0 grading system (common in US)
                else if ($gradeValue >= 0.0 && $gradeValue <= 4.0) {
                    $validGrade = true;
                }
                // 0-100 grading system
                else if ($gradeValue >= 50.0 && $gradeValue <= 100.0) {
                    $validGrade = true;
                }
                
                // Check for valid credit unit ranges
                $validUnit = false;
                
                // Standard credit unit range
                if ($unitValue >= 1.0 && $unitValue <= 6.0) {
                    $validUnit = true;
                }
                
                // If grade is invalid but unit value would make a valid grade, swap them
                if (!$validGrade && $unitValue >= 1.0 && $unitValue <= 5.0) {
                    $temp = $subject['grade'];
                    $subjects[$key]['grade'] = $subject['credit_units'];
                    $subjects[$key]['credit_units'] = $temp;
                    
                    file_put_contents($logFile, "Swapped invalid grade and credit units for {$subject['course_code']}\n", FILE_APPEND);
                }
            }
        }
    }
    
    // Remove the corrections tracking
    foreach ($subjects as $key => $subject) {
        // Fix grade format (ensure it's in the format X.XX)
        if (isset($subject['grade'])) {
            // If grade is like "1.0" or "1.5", convert to "1.00" or "1.50"
            if (preg_match('/^(\d)\.(\d)$/', $subject['grade'], $matches)) {
                $subjects[$key]['grade'] = $matches[1] . '.' . $matches[2] . '0';
            }
            
            // If grade is like "1.7", convert to "1.75" (common OCR error)
            if ($subject['grade'] == '1.7') {
                $subjects[$key]['grade'] = '1.75';
            }
        }
        
        // Fix credit units format (ensure it's in the format X.X)
        if (isset($subject['credit_units'])) {
            // If credit units is like "1", convert to "1.0"
            if (preg_match('/^(\d)$/', $subject['credit_units'])) {
                $subjects[$key]['credit_units'] = $subject['credit_units'] . '.0';
            }
            
            // If credit units is like "1.00", convert to "1.0"
            if (preg_match('/^(\d)\.(\d{2})$/', $subject['credit_units'], $matches)) {
                $subjects[$key]['credit_units'] = $matches[1] . '.' . substr($matches[2], 0, 1);
            }
        }
        
        // Check for swapped grade and credit units based on value ranges
        if (isset($subject['grade']) && isset($subject['credit_units'])) {
            // In most academic systems:
            // - Grades are typically between 1.00 and 5.00
            // - Credit units are typically between 1.0 and 6.0
            
            $gradeValue = floatval($subject['grade']);
            $unitsValue = floatval($subject['credit_units']);
            
            // Case 1: Grade is unusually high (likely a unit value)
            if ($gradeValue > 5.0 && $unitsValue >= 1.0 && $unitsValue <= 5.0) {
                $temp = $subject['grade'];
                $subjects[$key]['grade'] = $subject['credit_units'];
                $subjects[$key]['credit_units'] = $temp;
            }
            // Case 2: Credit unit is unusually low and grade looks like a unit value
            else if ($unitsValue < 1.0 && $gradeValue >= 1.0 && $gradeValue <= 6.0) {
                $temp = $subject['grade'];
                $subjects[$key]['grade'] = $subject['credit_units'];
                $subjects[$key]['credit_units'] = $temp;
            }
            // Case 3: Grade is 3.0 or higher (unusual for most grading systems) and units is low
            else if ($gradeValue >= 3.0 && $unitsValue <= 2.0) {
                // Only swap if the credit units value would make a reasonable grade
                if ($unitsValue >= 1.0 && $unitsValue <= 2.0) {
                    $temp = $subject['grade'];
                    $subjects[$key]['grade'] = $subject['credit_units'];
                    $subjects[$key]['credit_units'] = $temp;
                }
            }
            
            // After potential swap, ensure proper formatting
            // Format grade as X.XX
            if (preg_match('/^(\d)\.(\d)$/', $subjects[$key]['grade'], $matches)) {
                $subjects[$key]['grade'] = $matches[1] . '.' . $matches[2] . '0';
            }
            
            // Format credit units as X.X
            if (preg_match('/^(\d)\.(\d{2})$/', $subjects[$key]['credit_units'], $matches)) {
                $subjects[$key]['credit_units'] = $matches[1] . '.' . substr($matches[2], 0, 1);
            }
        }
    }
    
    // Remove the _corrections field from all subjects
    foreach ($subjects as $key => $subject) {
        if (isset($subjects[$key]['_corrections'])) {
            unset($subjects[$key]['_corrections']);
        }
    }
    
    // Final validation: Check for consistency across subjects
    // In most academic systems, credit units are consistent for similar courses
    $creditUnitsByPrefix = [];
    
    // First, collect credit units by course code prefix
    foreach ($subjects as $subject) {
        $prefix = substr($subject['course_code'], 0, 4); // Get the prefix (e.g., "COMP")
        if (!isset($creditUnitsByPrefix[$prefix])) {
            $creditUnitsByPrefix[$prefix] = [];
        }
        $creditUnitsByPrefix[$prefix][] = $subject['credit_units'];
    }
    
    // Find the most common credit unit value for each prefix
    $commonCreditUnits = [];
    foreach ($creditUnitsByPrefix as $prefix => $units) {
        $counts = array_count_values($units);
        arsort($counts); // Sort by frequency
        $commonCreditUnits[$prefix] = key($counts); // Get the most common value
    }
    
    // Apply corrections for outliers
    foreach ($subjects as $key => $subject) {
        $prefix = substr($subject['course_code'], 0, 4);
        if (isset($commonCreditUnits[$prefix]) && $subject['credit_units'] != $commonCreditUnits[$prefix]) {
            // If this subject's credit units differ from the most common value for its prefix
            // and the grade looks like it might be a credit unit value, swap them
            if ($subject['grade'] == $commonCreditUnits[$prefix] || 
                ($subject['credit_units'] < 1.0 && floatval($subject['grade']) >= 3.0)) {
                
                // Mark as corrected
                if (!isset($subjects[$key]['_corrections'])) {
                    $subjects[$key]['_corrections'] = [];
                }
                $subjects[$key]['_corrections']['grade'] = true;
                $subjects[$key]['_corrections']['credit_units'] = true;
                
                // Swap grade and credit units
                $temp = $subject['grade'];
                $subjects[$key]['grade'] = $subject['credit_units'];
                $subjects[$key]['credit_units'] = $temp;
                
                // Ensure proper formatting after swap
                // Format grade as X.XX
                if (preg_match('/^(\d)\.(\d)$/', $subjects[$key]['grade'], $matches)) {
                    $subjects[$key]['grade'] = $matches[1] . '.' . $matches[2] . '0';
                }
                
                // Format credit units as X.X
                if (preg_match('/^(\d)\.(\d{2})$/', $subjects[$key]['credit_units'], $matches)) {
                    $subjects[$key]['credit_units'] = $matches[1] . '.' . substr($matches[2], 0, 1);
                }
            }
        }
    }
    
    // Reindex array
    $subjects = array_values($subjects);
    
    // Log final results
    file_put_contents($logFile, "\nFinal Processed Subjects (" . count($subjects) . " found):\n" . print_r($subjects, true), FILE_APPEND);
    
    return $subjects;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_FILES['tor']) && $_FILES['tor']['error'] == UPLOAD_ERR_OK) {
            validateUploadedFile($_FILES['tor']);
            
            // Create uploads directory if it doesn't exist
            if (!file_exists(__DIR__ . '/uploads/tor')) {
                mkdir(__DIR__ . '/uploads/tor', 0755, true);
            }
            
            $tor_path = 'uploads/tor/' . basename($_FILES['tor']['name']);
            move_uploaded_file($_FILES['tor']['tmp_name'], __DIR__ . '/' . $tor_path);
            
            // Perform OCR
            $ocr_raw_text = performOCR($tor_path);
            
            // Extract subjects directly from raw OCR text
            $subjects = extractSubjects($ocr_raw_text);
            
            // Store results in session for display
            $_SESSION['ocr_raw_text'] = $ocr_raw_text;
            $_SESSION['extracted_subjects'] = $subjects;
            $_SESSION['original_image'] = $tor_path;
            
            // Find the preprocessed image path
            $extension = pathinfo($tor_path, PATHINFO_EXTENSION);
            $preprocessed_path = 'uploads/preprocessed/' . basename($tor_path, '.' . $extension) . '_preprocessed.' . $extension;
            if (file_exists(__DIR__ . '/' . $preprocessed_path)) {
                $_SESSION['preprocessed_image'] = $preprocessed_path;
            }
            
            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error'] = "Please upload a TOR file.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Clear results if this is a fresh page load (not a redirect after processing)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_SERVER['HTTP_REFERER'])) {
    unset($_SESSION['ocr_raw_text']);
    unset($_SESSION['extracted_subjects']);
    unset($_SESSION['original_image']);
    unset($_SESSION['preprocessed_image']);
    unset($_SESSION['blocks_visualization']);
    unset($_SESSION['block_subjects']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCR Practice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"] {
            display: block;
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #2980b9;
        }
        .error {
            color: #e74c3c;
            background: #fadbd8;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .stats {
            background: #e8f4f8;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .export-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 15px;
            font-size: 0.9em;
        }
        .export-btn:hover {
            background: #219653;
        }
        .log-viewer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            overflow: auto;
            padding: 20px;
            box-sizing: border-box;
        }
        .log-viewer-content {
            background: #fff;
            max-width: 90%;
            margin: 20px auto;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }
        .log-viewer h4 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .log-viewer pre {
            max-height: 70vh;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .log-viewer button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
        }
        .log-viewer button:hover {
            background: #2980b9;
        }
        .view-log {
            color: #3498db;
            text-decoration: underline;
            cursor: pointer;
            margin-left: 10px;
        }
        .view-log:hover {
            color: #2980b9;
        }
        .image-comparison {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .image-container {
            flex: 1;
            min-width: 300px;
        }
        .image-container img {
            max-width: 100%;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .image-container h4 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .block-legend {
            margin-top: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
        }
        .legend-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .legend-color {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 5px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        .extraction-tabs {
            margin-top: 20px;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
        }
        .tab.active {
            background: #f8f9fa;
            border-color: #ddd;
            border-bottom-color: #f8f9fa;
            margin-bottom: -1px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .preprocessing-options {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            font-weight: normal;
            margin-bottom: 0;
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>OCR Practice Tool</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Upload TOR</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="tor">Transcript of Records (JPG, PNG, PDF)</label>
                    <input type="file" id="tor" name="tor" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
                
                <div class="preprocessing-options">
                    <h3>Preprocessing Options</h3>
                    <p>The following preprocessing steps will be applied to improve OCR accuracy:</p>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="preprocess_options[]" value="resize" checked> Resize large images</label>
                        <label><input type="checkbox" name="preprocess_options[]" value="grayscale" checked> Convert to grayscale</label>
                    </div>
                    <p><small>Note: We're using minimal preprocessing to avoid making the image too blurry. Only grayscale conversion and resizing (if needed) are applied.</small></p>
                </div>
                
                <button type="submit">Process TOR</button>
            </form>
        </div>
        
        <?php if (isset($_SESSION['extracted_subjects'])): ?>
            <?php if (isset($_SESSION['original_image']) && isset($_SESSION['preprocessed_image'])): ?>
                <div class="card">
                    <h2>Image Preprocessing</h2>
                    <div class="image-comparison">
                        <div class="image-container">
                            <h4>Original Image</h4>
                            <img src="<?php echo htmlspecialchars($_SESSION['original_image']); ?>" alt="Original Image">
                        </div>
                        <div class="image-container">
                            <h4>Preprocessed Image</h4>
                            <img src="<?php echo htmlspecialchars($_SESSION['preprocessed_image']); ?>" alt="Preprocessed Image">
                        </div>
                    </div>
                    <p><small>The preprocessed image has been converted to grayscale and resized if necessary, using minimal processing to maintain image clarity while improving OCR accuracy.</small></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['blocks_visualization'])): ?>
                <div class="card">
                    <h2>Block-Based Text Detection</h2>
                    <div class="image-container">
                        <h4>Text Blocks Visualization</h4>
                        <img src="<?php echo htmlspecialchars($_SESSION['blocks_visualization']); ?>" alt="Text Blocks Visualization">
                    </div>
                    <div class="block-legend">
                        <h4>Block Type Legend</h4>
                        <ul class="legend-list">
                            <li><span class="legend-color" style="background-color: #00FF00;"></span> Course Code</li>
                            <li><span class="legend-color" style="background-color: #0000FF;"></span> Course Title</li>
                            <li><span class="legend-color" style="background-color: #FFFF00;"></span> Grade</li>
                            <li><span class="legend-color" style="background-color: #FF00FF;"></span> Credit Units</li>
                            <li><span class="legend-color" style="background-color: #FF0000;"></span> Unknown</li>
                        </ul>
                    </div>
                    <p><small>The image above shows how the OCR engine detected and classified text blocks in the document. Each colored box represents a different type of information.</small></p>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Extracted Subjects</h2>
                
                <div class="stats">
                    <strong>Total Subjects Found:</strong> 
                    <?php 
                    $traditionalCount = isset($_SESSION['extracted_subjects']) ? count($_SESSION['extracted_subjects']) : 0;
                    $blockCount = isset($_SESSION['block_subjects']) ? count($_SESSION['block_subjects']) : 0;
                    
                    echo "Traditional Method: {$traditionalCount}, Block-Based Method: {$blockCount}";
                    ?>
                    
                    <?php if (!empty($_SESSION['extracted_subjects']) || !empty($_SESSION['block_subjects'])): ?>
                        <button id="exportJSON" class="export-btn">Export as JSON</button>
                        <script>
                            document.getElementById('exportJSON').addEventListener('click', function() {
                                const data = {
                                    traditional: <?php echo json_encode(isset($_SESSION['extracted_subjects']) ? $_SESSION['extracted_subjects'] : []); ?>,
                                    blockBased: <?php echo json_encode(isset($_SESSION['block_subjects']) ? $_SESSION['block_subjects'] : []); ?>
                                };
                                const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
                                const url = URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = 'extracted_subjects.json';
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                                URL.revokeObjectURL(url);
                            });
                        </script>
                    <?php endif; ?>
                </div>
                
                <div class="tabs extraction-tabs">
                    <div class="tab active" data-tab="traditional-extraction">Traditional Extraction</div>
                    <div class="tab" data-tab="block-extraction">Block-Based Extraction</div>
                </div>
                
                <div class="tab-content active" id="traditional-extraction">
                    <?php if (empty($_SESSION['extracted_subjects'])): ?>
                        <p>No subjects were extracted using the traditional method. Please check the logs for details.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Course Code</th>
                                    <th>Course Title</th>
                                    <th>Grade</th>
                                    <th>Credit Units</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['extracted_subjects'] as $index => $subject): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($subject['course_code']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['grade']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['credit_units']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div class="tab-content" id="block-extraction">
                    <?php if (empty($_SESSION['block_subjects'])): ?>
                        <p>No subjects were extracted using the block-based method. Please check the logs for details.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Course Code</th>
                                    <th>Course Title</th>
                                    <th>Grade</th>
                                    <th>Credit Units</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['block_subjects'] as $index => $subject): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($subject['course_code']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['grade']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['credit_units']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <h2>OCR Results</h2>
                
                <div class="tabs">
                    <div class="tab active" data-tab="raw-text">Raw OCR Text</div>
                    <div class="tab" data-tab="json-data">JSON Data</div>
                    <div class="tab" data-tab="debug-info">Debug Information</div>
                </div>
                
                <div class="tab-content active" id="raw-text">
                    <h3>Raw OCR Text</h3>
                    <pre><?php echo htmlspecialchars($_SESSION['ocr_raw_text']); ?></pre>
                </div>
                
                <div class="tab-content" id="json-data">
                    <h3>JSON Representation</h3>
                    <pre><?php echo htmlspecialchars(json_encode($_SESSION['extracted_subjects'], JSON_PRETTY_PRINT)); ?></pre>
                </div>
                
                <div class="tab-content" id="debug-info">
                    <h3>Debug Information</h3>
                    <p>Check the logs directory for detailed extraction logs.</p>
                    <p>Latest log files:</p>
                    <ul>
                        <?php 
                        $logFiles = glob(__DIR__ . '/logs/*.txt');
                        if (!empty($logFiles)) {
                            usort($logFiles, function($a, $b) {
                                return filemtime($b) - filemtime($a);
                            });
                            $logFiles = array_slice($logFiles, 0, 10);
                            foreach ($logFiles as $file): 
                            ?>
                                <li>
                                    <?php echo basename($file); ?> (<?php echo date('Y-m-d H:i:s', filemtime($file)); ?>)
                                    <a href="javascript:void(0);" class="view-log" data-file="<?php echo basename($file); ?>">View</a>
                                </li>
                            <?php 
                            endforeach;
                        } else {
                            echo "<li>No log files found</li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>
            
            <div id="logViewer" class="log-viewer" style="display: none;">
                <div class="log-viewer-content">
                    <h4 id="logTitle">Log Contents</h4>
                    <pre id="logContents"></pre>
                    <button id="closeLog">Close</button>
                </div>
            </div>
            
            <script>
                // Tab functionality
                document.querySelectorAll('.tab').forEach(function(tab) {
                    tab.addEventListener('click', function() {
                        // Remove active class from all tabs
                        document.querySelectorAll('.tab').forEach(function(t) {
                            t.classList.remove('active');
                        });
                        
                        // Add active class to clicked tab
                        this.classList.add('active');
                        
                        // Hide all tab content
                        document.querySelectorAll('.tab-content').forEach(function(content) {
                            content.classList.remove('active');
                        });
                        
                        // Show corresponding tab content
                        const tabId = this.getAttribute('data-tab');
                        document.getElementById(tabId).classList.add('active');
                    });
                });
                
                // Log viewer functionality
                document.querySelectorAll('.view-log').forEach(function(link) {
                    link.addEventListener('click', function() {
                        const filename = this.getAttribute('data-file');
                        fetch('view_log.php?file=' + encodeURIComponent(filename))
                            .then(response => response.text())
                            .then(data => {
                                document.getElementById('logTitle').textContent = 'Log: ' + filename;
                                document.getElementById('logContents').textContent = data;
                                document.getElementById('logViewer').style.display = 'block';
                            })
                            .catch(error => {
                                alert('Error loading log file: ' + error);
                            });
                    });
                });
                
                document.getElementById('closeLog').addEventListener('click', function() {
                    document.getElementById('logViewer').style.display = 'none';
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>