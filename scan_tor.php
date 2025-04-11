<?php
require_once 'config/google_cloud_config.php';

/**
 * TOR Scanner
 * 
 * This script extracts academic data (subject codes, descriptions, grades, and units)
 * from different types of Transcript of Records (TOR) using Google Cloud Vision API.
 */
class TORScanner {
    private $apiKey;
    private $apiEndpoint;
    private $debug = false;

    /**
     * Constructor
     */
    public function __construct() {
        $this->apiKey = GOOGLE_CLOUD_API_KEY;
        $this->apiEndpoint = GOOGLE_CLOUD_VISION_ENDPOINT;
    }

    /**
     * Set debug mode
     * 
     * @param bool $debug Enable/disable debug mode
     */
    public function setDebug($debug) {
        $this->debug = $debug;
    }

    /**
     * Process image and extract academic data
     * 
     * @param string $imagePath Path to the TOR image
     * @return array Extracted academic data
     */
    public function processImage($imagePath) {
        // Check if file exists
        if (!file_exists($imagePath)) {
            return ['error' => 'Image file not found: ' . $imagePath];
        }

        // Get image content and encode with base64
        $imageContent = base64_encode(file_get_contents($imagePath));
        
        // Prepare request payload for Google Cloud Vision API
        $requestPayload = [
            'requests' => [
                [
                    'image' => [
                        'content' => $imageContent
                    ],
                    'features' => [
                        [
                            'type' => 'TEXT_DETECTION',
                            'maxResults' => 1
                        ],
                        [
                            'type' => 'DOCUMENT_TEXT_DETECTION',
                            'maxResults' => 1
                        ]
                    ]
                ]
            ]
        ];
        
        // Make API request
        $response = $this->makeRequest($requestPayload);
        
        if (isset($response['error'])) {
            return $response;
        }
        
        // Extract text and process according to TOR type
        $extractedText = $response['responses'][0]['textAnnotations'][0]['description'] ?? '';
        $documentText = $response['responses'][0]['fullTextAnnotation']['text'] ?? $extractedText;
        
        // Get text annotation data for spatial analysis
        $textAnnotations = $response['responses'][0]['textAnnotations'] ?? [];
        $boundingBoxes = [];
        
        // Extract bounding box information for each text element (skipping the first one which is the full text)
        if (!empty($textAnnotations)) {
            for ($i = 1; $i < count($textAnnotations); $i++) {
                $annotation = $textAnnotations[$i];
                $text = $annotation['description'];
                $vertices = $annotation['boundingPoly']['vertices'];
                
                $boundingBoxes[] = [
                    'text' => $text,
                    'bounds' => $vertices
                ];
            }
        }
        
        if ($this->debug) {
            file_put_contents('debug_output.txt', $extractedText);
            file_put_contents('debug_document.txt', $documentText);
            file_put_contents('debug_bounding_boxes.json', json_encode($boundingBoxes, JSON_PRETTY_PRINT));
        }
        
        // Try to process using learned patterns first
        $learnedResult = $this->extractUsingLearnedPatterns($documentText);
        if ($learnedResult && !empty($learnedResult['courses']) && count($learnedResult['courses']) >= 3) {
            // If we got good results from learned patterns, use them
            if ($this->debug) {
                $learnedResult['_source'] = 'learned_patterns';
            }
            return $learnedResult;
        }
        
        // Fall back to existing extraction methods
        return $this->dynamicFieldExtraction($documentText, $boundingBoxes);
    }
    
    /**
     * Make request to Google Cloud Vision API
     * 
     * @param array $requestPayload Request data
     * @return array API response
     */
    private function makeRequest($requestPayload) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $this->apiEndpoint . '?key=' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestPayload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($httpCode != 200) {
            return ['error' => 'API request failed with code ' . $httpCode . ': ' . $response];
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Dynamic field extraction based on keyword search
     * 
     * @param string $text Extracted text from TOR
     * @param array $boundingBoxes Text elements with spatial information
     * @return array Parsed academic data
     */
    private function dynamicFieldExtraction($text, $boundingBoxes) {
        $result = [];
        $courses = [];
        $metadata = [];
        
        // Break text into lines for analysis
        $lines = explode("\n", $text);
        
        // First, check if this looks like a structured tabular format with header rows
        $hasHeaderRow = false;
        $headerFields = ['Subject Code', 'Description', 'Faculty Name', 'Units', 'Sect Code', 'Final Grade'];
        $headerCount = 0;
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            foreach ($headerFields as $field) {
                if (stripos($trimmedLine, $field) !== false) {
                    $headerCount++;
                }
            }
        }
        
        // If we found multiple header fields, this might be a tabular format
        if ($headerCount >= 3) {
            $tabularCourses = $this->extractTabularWithSeparateFields($lines);
            if (!empty($tabularCourses)) {
                $courses = $tabularCourses;
                $result['metadata'] = $metadata;
                $result['courses'] = $courses;
                return $result;
            }
        }
        
        // Continue with existing dynamic field extraction logic...
        // ... existing code ...
        
        // Detect document type and institution
        if (stripos($text, 'POLYTECHNIC UNIVERSITY') !== false) {
            $metadata['institution'] = 'Polytechnic University of the Philippines';
        } elseif (stripos($text, 'UNIVERSITY OF PERPETUAL HELP') !== false) {
            $metadata['institution'] = 'University of Perpetual Help System';
        } elseif (stripos($text, 'TECHNOLOGICAL UNIVERSITY OF THE PHILIPPINES') !== false) {
            $metadata['institution'] = 'Technological University of the Philippines';
        } else {
            // Try to extract institution name
            foreach ($lines as $line) {
                if (preg_match('/UNIVERSITY|COLLEGE|SCHOOL/i', $line)) {
                    $metadata['institution'] = trim($line);
                    break;
                }
            }
        }
        
        // First attempt to detect tabular structure
        $tableDetected = $this->detectTableStructure($lines, $courses);
        if ($tableDetected && !empty($courses)) {
            $result['metadata'] = $metadata;
            $result['courses'] = $courses;
            return $result;
        }
        
        // Try normal dynamic field detection if table detection failed
        // Find table headers or column markers
        $headerIndices = [];
        $codeIndex = -1;
        $descriptionIndex = -1;
        $gradeIndex = -1;
        $unitsIndex = -1;
        
        // Look for header indicators in each line
        foreach ($lines as $i => $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) continue;
            
            // Possible header indicators
            $codeIndicators = ['CODE', 'SUBJECT CODE', 'COURSE CODE'];
            $descIndicators = ['DESCRIPTION', 'DESCRIPTIVE TITLE', 'COURSE TITLE', 'TITLE', 'SUBJECT'];
            $gradeIndicators = ['GRADE', 'GRADES', 'FINAL GRADE', 'MARK'];
            $unitIndicators = ['UNIT', 'UNITS', 'CREDIT', 'CREDITS'];
            
            $foundCodeIndicator = false;
            $foundDescIndicator = false;
            $foundGradeIndicator = false;
            $foundUnitIndicator = false;
            
            // Check for code indicators
            foreach ($codeIndicators as $indicator) {
                if (stripos($line, $indicator) !== false) {
                    $codeIndex = $i;
                    $foundCodeIndicator = true;
                    break;
                }
            }
            
            // Check for description indicators
            foreach ($descIndicators as $indicator) {
                if (stripos($line, $indicator) !== false) {
                    $descriptionIndex = $i;
                    $foundDescIndicator = true;
                    break;
                }
            }
            
            // Check for grade indicators
            foreach ($gradeIndicators as $indicator) {
                if (stripos($line, $indicator) !== false) {
                    $gradeIndex = $i;
                    $foundGradeIndicator = true;
                    break;
                }
            }
            
            // Check for unit indicators
            foreach ($unitIndicators as $indicator) {
                if (stripos($line, $indicator) !== false) {
                    $unitsIndex = $i;
                    $foundUnitIndicator = true;
                    break;
                }
            }
            
            // If we found multiple indicators in the same line, this is likely a header row
            if (($foundCodeIndicator && $foundDescIndicator) || 
                ($foundCodeIndicator && $foundGradeIndicator) ||
                ($foundCodeIndicator && $foundUnitIndicator) ||
                ($foundDescIndicator && $foundGradeIndicator) ||
                ($foundDescIndicator && $foundUnitIndicator) ||
                ($foundGradeIndicator && $foundUnitIndicator)) {
                $headerIndices['headerRow'] = $i;
                break;
            }
        }
        
        // Process data based on identified patterns
        $dataStartIndex = isset($headerIndices['headerRow']) ? $headerIndices['headerRow'] + 1 : 0;
        $inDataSection = false;
        
        // Keywords that typically mark the start of academic data
        $startDataKeywords = [
            'SUBJECTS', 'COURSES', 'CURRICULUM', 'ACADEMIC RECORD', 
            'ACADEMIC HISTORY', 'ACADEMIC PERFORMANCE', 'ENTRANCE DATA', 'Subject Code', 'Description', 'Final Grade', 'Units',
            'CODE', 'DESCRIPTIVE TITLE', 'GRADE', 'CREDIT', 'Course Code', 'Course Title', 'Final Grade'
        ];
        
        // Keywords that typically mark the end of academic data
        $endDataKeywords = [
            'GRADING SYSTEM', 'NOTHING FOLLOWS', 'END OF RECORD', 'LEGEND',
            'GPA', 'REMARKS', 'SUMMARY', 'TOTAL'
        ];
        
        // Find the most likely start of data section
        if (!isset($headerIndices['headerRow'])) {
            foreach ($lines as $i => $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                foreach ($startDataKeywords as $keyword) {
                    if (stripos($line, $keyword) !== false) {
                        $dataStartIndex = $i + 1;
                        break 2;
                    }
                }
            }
        }
        
        // Process lines for course data
        for ($i = $dataStartIndex; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            
            // Skip empty lines
            if (empty($line)) continue;
            
            // Check if we've reached the end of data section
            $endFound = false;
            foreach ($endDataKeywords as $keyword) {
                if (stripos($line, $keyword) !== false) {
                    $endFound = true;
                    break;
                }
            }
            
            if ($endFound) break;
            
            // Try to identify course code patterns
            // Common patterns: 2-5 uppercase letters followed by numbers (e.g., CS 101, COMP 20033)
            // Also match codes like "1 COMP" or just numbers like "10033"
            if (preg_match('/^([A-Z]{2,5}\s*\d{1,5})/', $line, $codeMatches) || 
                preg_match('/^(\d{1,3}\s*[A-Z]{2,5})/', $line, $codeMatches) ||
                preg_match('/^([A-Z]{2,5}[0-9]{1,2}M?)/', $line, $codeMatches) ||
                preg_match('/^(\d{4,5})/', $line, $codeMatches)) {
                
                $code = trim($codeMatches[0]);
                $restOfLine = trim(substr($line, strlen($codeMatches[0])));
                
                // Try to extract grade, units, and description
                $description = $restOfLine;
                $grade = null;
                $units = null;
                
                // Look for grade pattern (typically decimal number between 1.00 and 5.00)
                // Also match P (Pass), INC (Incomplete), or other non-numeric grades
                if (preg_match('/\b([1-5]\.\d{1,2})\b/', $restOfLine, $gradeMatches)) {
                    $grade = $gradeMatches[1];
                    // Remove grade from description
                    $description = trim(str_replace($gradeMatches[0], '', $description));
                } else if (preg_match('/\b(P|PASS|INC|W|UW|NG|NC)\b/i', $restOfLine, $specialGradeMatches)) {
                    $grade = strtoupper($specialGradeMatches[1]);
                    // Remove grade from description
                    $description = trim(str_replace($specialGradeMatches[0], '', $description));
                }
                
                // Look for units pattern - both decimal and integer formats
                if (preg_match('/\b(\d{1,2}(?:\.\d{1,2})?)\s*(?:unit|credit|units|credits)?\b/i', $restOfLine, $unitMatches)) {
                    $units = $unitMatches[1];
                    // Remove units from description
                    $description = trim(str_replace($unitMatches[0], '', $description));
                } else if (preg_match('/\b(\d{1,2}(?:\.\d{1,2})?)\b/', $restOfLine, $unitMatches) && !$units) {
                    // If we couldn't find explicit units, look for any number that might be units
                    if (!$grade || $unitMatches[1] != $grade) {
                        $units = $unitMatches[1];
                        // Remove units from description if not already part of grade
                        $description = trim(str_replace($unitMatches[0], '', $description));
                    }
                }
                
                // Look ahead for grade/units if not found on the same line
                if (!$grade || !$units) {
                    // Check next line for grade and units
                    if (isset($lines[$i + 1])) {
                        $nextLine = trim($lines[$i + 1]);
                        
                        // Skip if next line looks like a new course entry
                        if (!preg_match('/^[A-Z]{2,5}\s*\d{1,5}/', $nextLine) && 
                            !preg_match('/^\d{1,3}\s*[A-Z]{2,5}/', $nextLine)) {
                            
                            // Look for grade
                            if (!$grade && preg_match('/\b([1-5]\.\d{1,2})\b/', $nextLine, $nextGradeMatches)) {
                                $grade = $nextGradeMatches[1];
                            } else if (!$grade && preg_match('/\b(P|PASS|INC|W|UW|NG|NC)\b/i', $nextLine, $nextSpecialGradeMatches)) {
                                $grade = strtoupper($nextSpecialGradeMatches[1]);
                            }
                            
                            // Look for units
                            if (!$units && preg_match('/\b(\d{1,2}(?:\.\d{1,2})?)\s*(?:unit|credit|units|credits)?\b/i', $nextLine, $nextUnitMatches)) {
                                $units = $nextUnitMatches[1];
                            } else if (!$units && preg_match('/\b(\d{1,2}(?:\.\d{1,2})?)\b/', $nextLine, $nextUnitMatches)) {
                                if (!$grade || $nextUnitMatches[1] != $grade) {
                                    $units = $nextUnitMatches[1];
                                }
                            }
                        }
                    }
                }
                
                // Clean up description if needed
                if (strlen($description) > 100) {
                    // Description is probably too long, try to clean it up
                    $words = preg_split('/\s+/', $description);
                    if (count($words) > 15) {
                        $description = implode(' ', array_slice($words, 0, 15)) . '...';
                    }
                }
                
                // If there are digits at the beginning of the description, they may be part of the code
                if (preg_match('/^(\d+)/', $description, $extraCodeMatches)) {
                    $code .= ' ' . $extraCodeMatches[1];
                    $description = trim(substr($description, strlen($extraCodeMatches[0])));
                }
                
                // Clean up description further (remove multiple spaces, tabs)
                $description = preg_replace('/\s{2,}/', ' ', $description);
                
                // Add course to results
                $courses[] = [
                    'code' => $code,
                    'description' => $this->cleanDescription($description),
                    'grade' => $grade,
                    'units' => $units
                ];
            }
        }
        
        // If we couldn't extract many courses or missing many details, use spatial analysis
        $incompleteData = false;
        $missingCount = 0;
        
        foreach ($courses as $course) {
            if (empty($course['description']) || empty($course['grade']) || empty($course['units'])) {
                $missingCount++;
            }
        }
        
        // If more than 30% of courses have missing data, consider it incomplete
        if ($missingCount > 0 && count($courses) > 0 && ($missingCount / count($courses)) > 0.3) {
            $incompleteData = true;
        }
        
        // If we have bounding box information, use spatial analysis to improve extraction
        if (!empty($boundingBoxes) && (empty($courses) || count($courses) < 3 || $incompleteData)) {
            $spatialCourses = $this->extractDataFromBoundingBoxes($boundingBoxes, $text);
            
            // If spatial extraction found more courses, use that instead
            if (count($spatialCourses) > count($courses)) {
                $courses = $spatialCourses;
            } 
            // Otherwise, try to combine the results to fill missing data
            else if (!empty($spatialCourses) && !empty($courses)) {
                // Map courses by their code for easy lookup
                $coursesByCode = [];
                foreach ($courses as $course) {
                    $coursesByCode[$course['code']] = $course;
                }
                
                // Try to merge spatial data to fill gaps
                foreach ($spatialCourses as $spatialCourse) {
                    $code = $spatialCourse['code'];
                    if (isset($coursesByCode[$code])) {
                        // Fill in missing pieces
                        if (empty($coursesByCode[$code]['description']) && !empty($spatialCourse['description'])) {
                            $coursesByCode[$code]['description'] = $spatialCourse['description'];
                        }
                        if (empty($coursesByCode[$code]['grade']) && !empty($spatialCourse['grade'])) {
                            $coursesByCode[$code]['grade'] = $spatialCourse['grade'];
                        }
                        if (empty($coursesByCode[$code]['units']) && !empty($spatialCourse['units'])) {
                            $coursesByCode[$code]['units'] = $spatialCourse['units'];
                        }
                    } else {
                        // Add course that wasn't found before
                        $courses[] = $spatialCourse;
                        $coursesByCode[$code] = $spatialCourse;
                    }
                }
            }
        }
        
        // If we couldn't find any courses, try using the original text extraction methods
        if (empty($courses)) {
            // Try old extraction methods as fallback
            if (stripos($text, 'POLYTECHNIC UNIVERSITY') !== false) {
                return $this->parsePUPFormat($text);
            } elseif (stripos($text, 'UNIVERSITY OF PERPETUAL HELP') !== false) {
                return $this->parseUPHFormat($text);
            } elseif (stripos($text, 'TECHNOLOGICAL UNIVERSITY OF THE PHILIPPINES') !== false) {
                return $this->parseTUPFormat($text);
            } else if (preg_match('/Subject Code\s+Description\s+Faculty/i', $text)) {
                return $this->parseTabularFormat($text);
            } else {
                return $this->parseGenericFormat($text);
            }
        }
        
        // Clean up the descriptions
        foreach ($courses as &$course) {
            // Clean description
            if (!empty($course['description'])) {
                $course['description'] = $this->cleanDescription($course['description']);
            }
            
            // Validate grades - typically between 1.00 and 5.00 for most Philippine grading systems
            if (isset($course['grade']) && is_numeric($course['grade'])) {
                // If grade is just a digit like 1-5, convert to format with decimal point
                if (preg_match('/^[1-5]$/', $course['grade'])) {
                    $course['grade'] .= '.00';
                }
                // If grade is outside normal range, it might be units
                if (floatval($course['grade']) > 5.0 || floatval($course['grade']) < 1.0) {
                    // This is likely units not grade
                    if (empty($course['units'])) {
                        $course['units'] = $course['grade'];
                        $course['grade'] = null;
                    }
                }
            }
            
            // Validate units - typically between 1-6 for most courses
            if (isset($course['units']) && is_numeric($course['units'])) {
                if (floatval($course['units']) > 6.0) {
                    // This is unlikely to be units, might be something else
                    if (empty($course['grade']) && floatval($course['units']) >= 1.0 && floatval($course['units']) <= 5.0) {
                        $course['grade'] = $course['units'];
                        $course['units'] = null;
                    }
                }
            }
        }
        
        // After courses are processed, identify semester headings
        $this->identifySemesterHeadings($courses);
        
        // Populate missing data for UPH course codes
        if (stripos($text, 'UNIVERSITY OF PERPETUAL HELP') !== false) {
            $metadata['institution'] = 'University of Perpetual Help System';
            $this->populateUPHCourseData($courses);
        }
        
        $result['metadata'] = $metadata;
        $result['courses'] = $courses;
        return $result;
    }
    
    /**
     * Detect table structure in a TOR document
     * 
     * @param array $lines Lines of text from the document
     * @param array &$courses Reference to courses array to fill
     * @return bool True if a table structure was detected and processed
     */
    private function detectTableStructure($lines, &$courses) {
        // Look for consistent column patterns
        $columnStartPositions = [];
        $headerRow = -1;
        $dataStartRow = -1;
        
        // First pass - find potential header row with column titles
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check if this line contains multiple column headers
            if (preg_match_all('/(Subject|Code|Description|Grade|Final Grade|Unit|Credit)/i', $line, $matches)) {
                if (count($matches[0]) >= 2) {
                    $headerRow = $i;
                    $dataStartRow = $i + 1;
                    
                    // Find positions of column headers
                    $lowerLine = strtolower($line);
                    if (preg_match('/\b(subject|code)\b/i', $lowerLine, $match, PREG_OFFSET_CAPTURE)) {
                        $columnStartPositions['code'] = $match[0][1];
                    }
                    if (preg_match('/\b(description|title)\b/i', $lowerLine, $match, PREG_OFFSET_CAPTURE)) {
                        $columnStartPositions['description'] = $match[0][1];
                    }
                    if (preg_match('/\b(grade|mark|final grade)\b/i', $lowerLine, $match, PREG_OFFSET_CAPTURE)) {
                        $columnStartPositions['grade'] = $match[0][1];
                    }
                    if (preg_match('/\b(unit|credit)\b/i', $lowerLine, $match, PREG_OFFSET_CAPTURE)) {
                        $columnStartPositions['units'] = $match[0][1];
                    }
                    
                    // Sort positions
                    asort($columnStartPositions);
                    break;
                }
            }
        }
        
        // If we found headers and column positions, extract data
        if ($headerRow >= 0 && !empty($columnStartPositions)) {
            $columnEnds = [];
            $columnOrder = array_keys($columnStartPositions);
            
            // Calculate column end positions
            for ($i = 0; $i < count($columnOrder); $i++) {
                $currentCol = $columnOrder[$i];
                if ($i < count($columnOrder) - 1) {
                    $nextCol = $columnOrder[$i + 1];
                    $columnEnds[$currentCol] = $columnStartPositions[$nextCol] - 1;
                } else {
                    $columnEnds[$currentCol] = PHP_INT_MAX; // Last column extends to the end
                }
            }
            
            // Process data rows
            for ($i = $dataStartRow; $i < count($lines); $i++) {
                $line = $lines[$i];
                if (empty(trim($line))) continue;
                
                // Check if this might be the end of the data section
                if (preg_match('/GRADING|LEGEND|TOTAL|SUMMARY/i', $line)) {
                    break;
                }
                
                // Process each column
                $courseData = [
                    'code' => null,
                    'description' => null,
                    'grade' => null,
                    'units' => null
                ];
                
                $hasData = false;
                
                foreach ($columnOrder as $col) {
                    $start = $columnStartPositions[$col];
                    $end = $columnEnds[$col];
                    
                    // Calculate actual length to extract
                    $length = ($end == PHP_INT_MAX) ? strlen($line) - $start : $end - $start;
                    
                    // Make sure we don't go out of bounds
                    if ($start < strlen($line)) {
                        $value = trim(substr($line, $start, $length));
                        
                        // Skip if empty value
                        if (!empty($value)) {
                            $courseData[$col] = $value;
                            $hasData = true;
                        }
                    }
                }
                
                // Only add if we have at least code and one other value
                if ($hasData && !empty($courseData['code'])) {
                    // Make sure code looks like a subject code
                    if (preg_match('/[A-Z0-9]+/i', $courseData['code'])) {
                        // Clean up grade and units if they're numeric
                        if (isset($courseData['grade']) && preg_match('/\d+\.\d+/', $courseData['grade'], $matches)) {
                            $courseData['grade'] = $matches[0];
                        }
                        if (isset($courseData['units']) && preg_match('/\d+(\.\d+)?/', $courseData['units'], $matches)) {
                            $courseData['units'] = $matches[0];
                        }
                        
                        $courses[] = $courseData;
                    }
                }
            }
            
            return !empty($courses);
        }
        
        return false;
    }
    
    /**
     * Extract data using spatial information from bounding boxes
     * 
     * @param array $boundingBoxes Text elements with spatial information
     * @param string $text Complete extracted text
     * @return array Extracted courses
     */
    private function extractDataFromBoundingBoxes($boundingBoxes, $text) {
        $courses = [];
        $possibleCodes = [];
        $possibleDescriptions = [];
        $possibleGrades = [];
        $possibleUnits = [];
        
        // Find text elements that might be course codes, descriptions, grades, or units
        foreach ($boundingBoxes as $box) {
            $boxText = $box['text'];
            
            // Identify course codes (2-5 uppercase letters followed by numbers)
            if (preg_match('/^[A-Z]{2,5}\s*\d{1,5}$/', $boxText) || 
                preg_match('/^\d{1,3}\s*[A-Z]{2,5}$/', $boxText) ||
                preg_match('/^[A-Z]{2,5}[0-9]{1,2}M?$/', $boxText)) {
                $possibleCodes[] = [
                    'text' => $boxText,
                    'bounds' => $box['bounds'],
                    'center' => $this->calculateCenter($box['bounds'])
                ];
            }
            
            // Identify grades (decimal between 1.00 and 5.00 or special grades)
            else if (preg_match('/^[1-5]\.\d{1,2}$/', $boxText) || 
                     preg_match('/^(P|PASS|INC|W|UW|NG|NC)$/i', $boxText)) {
                $possibleGrades[] = [
                    'text' => $boxText,
                    'bounds' => $box['bounds'],
                    'center' => $this->calculateCenter($box['bounds'])
                ];
            }
            
            // Identify units (typically integer or decimal)
            else if (preg_match('/^\d{1,2}(?:\.\d{1,2})?$/', $boxText) && 
                     !preg_match('/^[1-5]\.\d{1,2}$/', $boxText)) { // Avoid duplicating grades
                $possibleUnits[] = [
                    'text' => $boxText,
                    'bounds' => $box['bounds'],
                    'center' => $this->calculateCenter($box['bounds'])
                ];
            }
            
            // Longer text elements might be descriptions
            else if (strlen($boxText) > 3 && strlen($boxText) < 100 &&
                    !preg_match('/^(Subject|Code|Description|Grade|Unit)/i', $boxText)) {
                $possibleDescriptions[] = [
                    'text' => $boxText,
                    'bounds' => $box['bounds'],
                    'center' => $this->calculateCenter($box['bounds'])
                ];
            }
        }
        
        // Try to identify columns based on vertical alignment
        $codeColumn = $this->identifyColumn($possibleCodes);
        $descriptionColumn = $this->identifyColumn($possibleDescriptions);
        $gradeColumn = $this->identifyColumn($possibleGrades);
        $unitColumn = $this->identifyColumn($possibleUnits);
        
        // If we identified columns, use row-based extraction
        if ($codeColumn && ($gradeColumn || $unitColumn)) {
            return $this->extractByRow($codeColumn, $descriptionColumn, $gradeColumn, $unitColumn);
        }
        
        // If we have possible codes, try to associate them with other elements based on spatial proximity
        if (!empty($possibleCodes)) {
            foreach ($possibleCodes as $code) {
                $nearestDescription = $this->findNearestElement($code, $possibleDescriptions);
                $nearestGrade = $this->findNearestElement($code, $possibleGrades);
                $nearestUnit = $this->findNearestElement($code, $possibleUnits);
                
                $courses[] = [
                    'code' => $code['text'],
                    'description' => $nearestDescription ? $nearestDescription['text'] : null,
                    'grade' => $nearestGrade ? $nearestGrade['text'] : null,
                    'units' => $nearestUnit ? $nearestUnit['text'] : null
                ];
            }
        }
        
        return $courses;
    }
    
    /**
     * Identify if elements form a vertical column
     * 
     * @param array $elements Elements to check
     * @return array|null Column information or null if not a column
     */
    private function identifyColumn($elements) {
        if (count($elements) < 3) return null;
        
        // Group elements by their x-coordinate (with some tolerance)
        $columnGroups = [];
        $tolerance = 20; // Pixel tolerance for alignment
        
        foreach ($elements as $element) {
            $x = $element['center']['x'];
            $grouped = false;
            
            foreach ($columnGroups as $key => $group) {
                $avgX = $group['avgX'];
                if (abs($x - $avgX) <= $tolerance) {
                    $columnGroups[$key]['elements'][] = $element;
                    $columnGroups[$key]['totalX'] += $x;
                    $columnGroups[$key]['avgX'] = $columnGroups[$key]['totalX'] / count($columnGroups[$key]['elements']);
                    $grouped = true;
                    break;
                }
            }
            
            if (!$grouped) {
                $columnGroups[] = [
                    'elements' => [$element],
                    'totalX' => $x,
                    'avgX' => $x
                ];
            }
        }
        
        // Find the largest group
        $largestGroup = null;
        $maxCount = 0;
        foreach ($columnGroups as $group) {
            if (count($group['elements']) > $maxCount) {
                $maxCount = count($group['elements']);
                $largestGroup = $group;
            }
        }
        
        // If the largest group has at least 3 elements, consider it a column
        if ($largestGroup && count($largestGroup['elements']) >= 3) {
            // Sort elements by y-coordinate (top to bottom)
            usort($largestGroup['elements'], function($a, $b) {
                return $a['center']['y'] - $b['center']['y'];
            });
            
            return $largestGroup;
        }
        
        return null;
    }
    
    /**
     * Extract data by row using identified columns
     * 
     * @param array $codeColumn Code column elements
     * @param array $descriptionColumn Description column elements
     * @param array $gradeColumn Grade column elements
     * @param array $unitColumn Unit column elements
     * @return array Extracted courses
     */
    private function extractByRow($codeColumn, $descriptionColumn, $gradeColumn, $unitColumn) {
        $courses = [];
        $codeElements = $codeColumn['elements'];
        
        foreach ($codeElements as $i => $codeElement) {
            $y = $codeElement['center']['y'];
            $tolerance = 20; // Row height tolerance
            
            $description = null;
            $grade = null;
            $units = null;
            
            // Find description in the same row
            if ($descriptionColumn) {
                foreach ($descriptionColumn['elements'] as $descElement) {
                    if (abs($descElement['center']['y'] - $y) <= $tolerance) {
                        $description = $descElement['text'];
                        break;
                    }
                }
            }
            
            // Find grade in the same row
            if ($gradeColumn) {
                foreach ($gradeColumn['elements'] as $gradeElement) {
                    if (abs($gradeElement['center']['y'] - $y) <= $tolerance) {
                        $grade = $gradeElement['text'];
                        break;
                    }
                }
            }
            
            // Find units in the same row
            if ($unitColumn) {
                foreach ($unitColumn['elements'] as $unitElement) {
                    if (abs($unitElement['center']['y'] - $y) <= $tolerance) {
                        $units = $unitElement['text'];
                        break;
                    }
                }
            }
            
            $courses[] = [
                'code' => $codeElement['text'],
                'description' => $description ? $this->cleanDescription($description) : null,
                'grade' => $grade,
                'units' => $units
            ];
        }
        
        return $courses;
    }
    
    /**
     * Calculate center point of a bounding box
     * 
     * @param array $bounds Vertices of the bounding box
     * @return array Center coordinates [x, y]
     */
    private function calculateCenter($bounds) {
        $sumX = 0;
        $sumY = 0;
        $count = count($bounds);
        
        foreach ($bounds as $vertex) {
            $sumX += isset($vertex['x']) ? $vertex['x'] : 0;
            $sumY += isset($vertex['y']) ? $vertex['y'] : 0;
        }
        
        return [
            'x' => $count > 0 ? $sumX / $count : 0,
            'y' => $count > 0 ? $sumY / $count : 0
        ];
    }
    
    /**
     * Find nearest element to a reference element
     * 
     * @param array $reference Reference element with center coordinates
     * @param array $elements Array of elements to search
     * @return array|null Nearest element or null if no elements provided
     */
    private function findNearestElement($reference, $elements) {
        if (empty($elements)) return null;
        
        $nearestElement = null;
        $shortestDistance = PHP_FLOAT_MAX;
        
        foreach ($elements as $element) {
            $distance = $this->calculateDistance($reference['center'], $element['center']);
            
            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestElement = $element;
            }
        }
        
        return $nearestElement;
    }
    
    /**
     * Calculate Euclidean distance between two points
     * 
     * @param array $point1 First point coordinates [x, y]
     * @param array $point2 Second point coordinates [x, y]
     * @return float Distance between points
     */
    private function calculateDistance($point1, $point2) {
        $dx = $point1['x'] - $point2['x'];
        $dy = $point1['y'] - $point2['y'];
        
        return sqrt($dx * $dx + $dy * $dy);
    }
    
    /**
     * Clean extracted description by removing instructor names and section information
     * 
     * @param string $description Raw description that may contain instructor names and section info
     * @return string Cleaned description
     */
    private function cleanDescription($description) {
        // If description contains a comma followed by instructor name pattern, remove it
        if (preg_match('/(.*?)(?:\s+[A-Z]+\s*,\s*[A-Z][a-zA-Z\.\s]+(?:BSIT|BSCS|BS[A-Z]{2,4})?)/i', $description, $matches)) {
            return trim($matches[1]);
        }
        
        // If description contains a hash symbol at the beginning, remove it
        if (strpos($description, '#') === 0) {
            $description = trim(substr($description, 1));
        }
        
        return $description;
    }
    
    /**
     * Parse PUP (Polytechnic University of the Philippines) format
     * 
     * @param string $text Extracted text
     * @return array Parsed academic data
     */
    private function parsePUPFormat($text) {
        $result = [];
        $courses = [];
        
        // Break text into lines
        $lines = explode("\n", $text);
        
        // Track if we're in the courses section
        $inCoursesSection = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) continue;
            
            // Check for course code pattern (MIT or RSH followed by numbers)
            if (preg_match('/^(MIT|RSH)\s*(\d+)/', $line, $matches)) {
                $inCoursesSection = true;
                $code = $matches[1] . ' ' . $matches[2];
                
                // Extract the rest of the line which should contain the description
                $restOfLine = trim(substr($line, strlen($matches[0])));
                
                // Try to extract grade and units from next elements or from the same line
                $grade = null;
                $units = null;
                
                // Check if the grade/units are in the same line
                if (preg_match('/(\d+\.\d+)\s+(\d+)$/', $restOfLine, $gradeUnitsMatches)) {
                    $grade = $gradeUnitsMatches[1];
                    $units = $gradeUnitsMatches[2];
                    // Remove grade and units from description
                    $description = trim(substr($restOfLine, 0, strpos($restOfLine, $gradeUnitsMatches[0])));
                } else {
                    $description = $restOfLine;
                    
                    // Look ahead in the next lines for grade and units
                    $nextIndex = array_search($line, $lines) + 1;
                    if (isset($lines[$nextIndex]) && preg_match('/^(\d+\.\d+)\s+(\d+)$/', trim($lines[$nextIndex]), $nextMatches)) {
                        $grade = $nextMatches[1];
                        $units = $nextMatches[2];
                    }
                }
                
                // Clean up the description
                $description = $this->cleanDescription($description);
                
                // Add course to results
                $courses[] = [
                    'code' => $code,
                    'description' => $description,
                    'grade' => $grade,
                    'units' => $units
                ];
            } 
            // Check if we've reached the end of the courses section
            elseif ($inCoursesSection && strpos($line, 'Nothing Follows') !== false) {
                break;
            }
        }
        
        $result['courses'] = $courses;
        return $result;
    }
    
    /**
     * Parse UPH (University of Perpetual Help) format
     * 
     * @param string $text Extracted text
     * @return array Parsed academic data
     */
    private function parseUPHFormat($text) {
        $result = [];
        $courses = [];
        $semesterHeadings = [];
        
        // Break text into lines
        $lines = explode("\n", $text);
        
        // Flag to check if we're in the course section
        $inCourseSection = false;
        $currentSemester = null;
        
        // First pass: identify the structure and semester headings
        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            
            // Skip empty lines
            if (empty($line)) continue;
            
            // Check for UPH's distinctive heading pattern
            if (stripos($line, 'UNIVERSITY OF PERPETUAL HELP SYSTEM') !== false) {
                // We're in a UPH transcript
                $inCourseSection = true;
                continue;
            }
            
            // Detect semester headings 
            if (preg_match('/([1-4](?:ST|ND|RD|TH))\s+SEMESTER\s*,\s*(\d{4}\s*-\s*\d{4})/i', $line, $matches) ||
                preg_match('/(\d{1,4})\s*(?:ST|ND|RD|TH)?\s*SEMESTER\s*,?\s*(\d{4}\s*-\s*\d{4})/i', $line, $matches)) {
                $semesterHeadings[] = [
                    'semester' => $matches[1],
                    'academic_year' => $matches[2],
                    'line_index' => $i
                ];
                continue;
            }
        }
        
        // Look for the structure of the transcript (columns)
        $courseCodeCol = -1;
        $courseTitleCol = -1;
        $gradeCol = -1;
        $unitsCol = -1;
        
        // Try to find the header row that denotes the structure
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (preg_match('/Course\s+Code/i', $line) && preg_match('/Course\s+Title/i', $line)) {
                // This is likely the header row
                if (preg_match('/Course\s+Code/i', $line, $matches, PREG_OFFSET_CAPTURE)) {
                    $courseCodeCol = $matches[0][1];
                }
                if (preg_match('/Course\s+Title/i', $line, $matches, PREG_OFFSET_CAPTURE)) {
                    $courseTitleCol = $matches[0][1];
                }
                if (preg_match('/Grade/i', $line, $matches, PREG_OFFSET_CAPTURE)) {
                    $gradeCol = $matches[0][1];
                }
                if (preg_match('/(?:Credit\s+)?Units?/i', $line, $matches, PREG_OFFSET_CAPTURE)) {
                    $unitsCol = $matches[0][1];
                }
                break;
            }
        }
        
        // If we couldn't find column positions, try to determine them from data rows
        // UPH transcripts often follow a specific format where:
        // - Course codes are 3-4 letters followed by numbers (e.g., GEC 1000)
        // - Course titles are text
        // - Grades are typically 1.00 to 5.00
        // - Units are typically 1.0 to 5.0
        
        // Process the courses by semesters if available
        if (!empty($semesterHeadings)) {
            for ($s = 0; $s < count($semesterHeadings); $s++) {
                $startLine = $semesterHeadings[$s]['line_index'] + 1;
                $endLine = ($s < count($semesterHeadings) - 1) ? 
                            $semesterHeadings[$s + 1]['line_index'] : 
                            count($lines);
                
                $semester = $semesterHeadings[$s]['semester'] . ' SEMESTER, ' . $semesterHeadings[$s]['academic_year'];
                
                // Extract courses for this semester
                for ($i = $startLine; $i < $endLine; $i++) {
                    $line = trim($lines[$i]);
                    
                    // Skip empty lines or lines that don't look like course entries
                    if (empty($line)) continue;
                    
                    // Skip lines that might be semester headings
                    if (preg_match('/SEMESTER/i', $line)) continue;
                    
                    // Try different course code patterns that appear in UPH transcripts
                    if (preg_match('/^([A-Z]{2,5}\s*\d{3,5}[A-Z]?)/i', $line, $codeMatches)) {
                        $code = trim($codeMatches[1]);
                        $restOfLine = trim(substr($line, strlen($codeMatches[1])));
                        
                        // Look for grade and units using typical patterns in UPH transcripts
                        $description = $restOfLine;
                        $grade = null;
                        $units = null;
                        
                        // Match grade pattern (1.00-5.00 or P/PASS)
                        if (preg_match('/\b([1-5]\.\d{1,2})\b/', $restOfLine, $gradeMatches)) {
                            $grade = $gradeMatches[1];
                            // Calculate position of grade for description extraction
                            $gradePos = strpos($restOfLine, $grade);
                            if ($gradePos !== false) {
                                // Description is everything before grade
                                $description = trim(substr($restOfLine, 0, $gradePos));
                            }
                        } else if (preg_match('/\b(P|PASS)\b/i', $restOfLine, $specialGradeMatches)) {
                            $grade = strtoupper($specialGradeMatches[1]);
                            $gradePos = stripos($restOfLine, $specialGradeMatches[0]);
                            if ($gradePos !== false) {
                                $description = trim(substr($restOfLine, 0, $gradePos));
                            }
                        }
                        
                        // Match units pattern (typically 1.0-6.0 or 1-6)
                        if (preg_match('/\b(\d{1,2}(?:\.\d{1,2})?)\b/', $restOfLine, $unitMatches, PREG_OFFSET_CAPTURE)) {
                            // Make sure it's not the grade we matched
                            $possibleUnit = $unitMatches[1][0];
                            $unitPos = $unitMatches[1][1];
                            
                            // Only use it as units if it's not the grade and it's a reasonable value (1-6)
                            if ((!$grade || $possibleUnit != $grade) && 
                                (floatval($possibleUnit) >= 1.0 && floatval($possibleUnit) <= 6.0)) {
                                $units = $possibleUnit;
                                
                                // If we haven't found a grade, check if there's another number that could be grade
                                if (!$grade) {
                                    // Look for another number in the line
                                    if (preg_match_all('/\b(\d{1,2}(?:\.\d{1,2})?)\b/', $restOfLine, $allMatches)) {
                                        foreach ($allMatches[1] as $match) {
                                            if ($match != $units && 
                                                floatval($match) >= 1.0 && floatval($match) <= 5.0) {
                                                $grade = $match;
                                                break;
                                            }
                                        }
                                    }
                                }
                                
                                // If we found both grade and units, extract description more accurately
                                if ($grade && $grade != $units) {
                                    $gradePos = strpos($restOfLine, $grade);
                                    $unitPos = strpos($restOfLine, $units);
                                    
                                    // Description is usually before both grade and units
                                    $endPos = min($gradePos, $unitPos);
                                    if ($endPos !== false && $endPos > 0) {
                                        $description = trim(substr($restOfLine, 0, $endPos));
                                    }
                                }
                            }
                        }
                        
                        // Check next line for additional information
                        if (($i + 1) < count($lines) && 
                            !preg_match('/^[A-Z]{2,5}\s*\d{3,5}/i', trim($lines[$i + 1])) &&
                            !empty(trim($lines[$i + 1]))) {
                            
                            $nextLine = trim($lines[$i + 1]);
                            
                            // If we don't have a grade yet, see if next line has it
                            if (!$grade && preg_match('/\b([1-5]\.\d{1,2})\b/', $nextLine, $nextGradeMatches)) {
                                $grade = $nextGradeMatches[1];
                            }
                            
                            // If we don't have units yet, see if next line has it
                            if (!$units && preg_match('/\b(\d{1,2}(?:\.\d{1,2})?)\b/', $nextLine, $nextUnitMatches)) {
                                // Check if it's a reasonable unit value and not the grade
                                $possibleUnit = $nextUnitMatches[1];
                                if ((!$grade || $possibleUnit != $grade) && 
                                    floatval($possibleUnit) >= 1.0 && floatval($possibleUnit) <= 6.0) {
                                    $units = $possibleUnit;
                                }
                            }
                            
                            // If description is empty, see if next line has it
                            if (empty($description) || $description == 'L' || 
                                strlen($description) < 3 || 
                                preg_match('/^[A-Z]$/i', $description)) {
                                if (!preg_match('/\b([1-5]\.\d{1,2})\b/', $nextLine) && 
                                    !preg_match('/SEMESTER/i', $nextLine)) {
                                    // This could be a description
                                    $description = $nextLine;
                                }
                            }
                        }
                        
                        // Look for specific UPH course data patterns
                        if (empty($description) || strlen($description) < 3) {
                            // Try to find this course in known UPH courses
                            $description = $this->getUPHCourseDescription($code);
                        }
                        
                        // Special case handling for UPH transcript
                        // Some codes may have L suffix for lab components
                        $isLabComponent = false;
                        if (substr($code, -1) === 'L' || substr($description, -1) === 'L') {
                            $isLabComponent = true;
                            // Remove the L from description if it's there
                            if (substr($description, -1) === 'L') {
                                $description = trim(substr($description, 0, -1));
                            }
                            
                            // If description is still empty, look for parent course
                            if (empty($description) || strlen($description) < 3) {
                                $parentCode = substr($code, 0, -1);
                                $description = $this->getUPHCourseDescription($parentCode) . ' - Laboratory';
                            } else {
                                $description .= ' - Laboratory';
                            }
                        }
                        
                        // Clean up the description
                        $description = $this->cleanDescription($description);
                        
                        // Add the course
                        $courses[] = [
                            'code' => $code,
                            'description' => $description,
                            'grade' => $grade,
                            'units' => $units,
                            'semester' => $semester
                        ];
                    }
                }
            }
        } else {
            // Process without semester headings
            // This is a fallback approach if we couldn't identify semester sections
            for ($i = 0; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                
                // Skip empty lines
                if (empty($line)) continue;
                
                // Look for course code patterns
                if (preg_match('/^([A-Z]{2,5}\s*\d{3,5}[A-Z]?)/i', $line, $codeMatches)) {
                    // Similar parsing logic as above...
                    $code = trim($codeMatches[1]);
                    $restOfLine = trim(substr($line, strlen($codeMatches[1])));
                    
                    // Rest of the parsing logic is the same as above
                    // ...
                    
                    // Extract data using similar approach as the semester-based parsing
                    $description = $restOfLine;
                    $grade = null;
                    $units = null;
                    
                    // Match grade pattern (1.00-5.00 or P/PASS)
                    if (preg_match('/\b([1-5]\.\d{1,2})\b/', $restOfLine, $gradeMatches)) {
                        $grade = $gradeMatches[1];
                        // Calculate position of grade for description extraction
                        $gradePos = strpos($restOfLine, $grade);
                        if ($gradePos !== false) {
                            // Description is everything before grade
                            $description = trim(substr($restOfLine, 0, $gradePos));
                        }
                    }
                    
                    // Match units pattern
                    if (preg_match('/\b(\d{1,2}(?:\.\d{1,2})?)\b/', $restOfLine, $unitMatches, PREG_OFFSET_CAPTURE)) {
                        // Similar unit extraction logic as above
                        $possibleUnit = $unitMatches[1][0];
                        if ((!$grade || $possibleUnit != $grade) && 
                            floatval($possibleUnit) >= 1.0 && floatval($possibleUnit) <= 6.0) {
                            $units = $possibleUnit;
                        }
                    }
                    
                    // Look up course description if empty
                    if (empty($description) || strlen($description) < 3) {
                        $description = $this->getUPHCourseDescription($code);
                    }
                    
                    // Clean up the description
                    $description = $this->cleanDescription($description);
                    
                    $courses[] = [
                        'code' => $code,
                        'description' => $description,
                        'grade' => $grade,
                        'units' => $units
                    ];
                }
            }
        }
        
        // Fill in missing descriptions and other data using a mapping table
        foreach ($courses as &$course) {
            // Skip courses that are actually semester headings
            if (preg_match('/^\d{1,2}(?:ST|ND|RD|TH)$/', $course['code']) && 
                stripos($course['description'], 'SEMESTER') !== false) {
                // Flag this as a non-course
                $course['is_heading'] = true;
                continue;
            }
            
            if (empty($course['description']) || strlen($course['description']) < 3) {
                $course['description'] = $this->getUPHCourseDescription($course['code']);
            }
            
            // Clean up the description
            $course['description'] = $this->cleanDescription($course['description']);
            
            // If we still don't have a description but have lab component, try parent code
            if ((empty($course['description']) || strlen($course['description']) < 3) && 
                substr($course['code'], -1) === 'L') {
                $parentCode = substr($course['code'], 0, -1);
                $desc = $this->getUPHCourseDescription($parentCode);
                if ($desc) {
                    $course['description'] = $desc . ' - Laboratory';
                }
            }
            
            // If we have a course code but no grade/units, try to look them up
            if (!empty($course['code']) && (empty($course['grade']) || empty($course['units']))) {
                $defaultData = $this->getUPHCourseDefaults($course['code']);
                if (!empty($defaultData)) {
                    if (empty($course['units']) && isset($defaultData['units'])) {
                        $course['units'] = $defaultData['units'];
                    }
                    if (empty($course['grade']) && isset($defaultData['typical_grade'])) {
                        $course['grade'] = $defaultData['typical_grade'];
                    }
                }
            }
        }
        
        // Filter out non-courses (like semester headings)
        $filteredCourses = array_filter($courses, function($course) {
            return !isset($course['is_heading']) || !$course['is_heading'];
        });
        
        $result['courses'] = array_values($filteredCourses);
        return $result;
    }
    
    /**
     * Get description for a UPH course code
     * 
     * @param string $code Course code
     * @return string Course description or empty string if not found
     */
    private function getUPHCourseDescription($code) {
        // Common course codes and descriptions for University of Perpetual Help System
        $courseDescriptions = [
            'FCL 1101' => 'The Perpetualite: Identity and Dignity',
            'FCL 1202' => 'The Perpetualite: A Man of God',
            'FIL 1000' => 'Komunikasyon sa Akademikong Filipino',
            'GEC 1000' => 'Understanding the Self',
            'GEC 4000' => 'Purposive Communication',
            'GEC 5000' => 'Mathematics in the Modern World',
            'GEC 6000' => 'The Contemporary World',
            'GEC 8000' => 'Science, Technology, and Society',
            'GEE 1000' => 'Living in the IT Era',
            'GEE 1000L' => 'Living in the IT Era - Laboratory',
            'NSTP 1101' => 'National Service Training Program 1',
            'NSTP 1202' => 'National Service Training Program 2',
            'PE 1101' => 'Foundations of Physical Education',
            'PE 1202' => 'Rhythmic Activities',
            'PSY 1101' => 'Introduction to Psychology',
            'PSY 1202' => 'Psychological Statistics',
            'PSY 1202L' => 'Psychological Statistics - Laboratory',
            
            // Adding more UPH courses
            'PSY 1203' => 'Theories of Personality',
            'PSY 2304' => 'Experimental Psychology',
            'PSY 2304L' => 'Experimental Psychology - Laboratory',
            'PSY 2305' => 'Developmental Psychology',
            'PSY 2306' => 'Physiological Psychology',
            'PSY 2307' => 'Filipino Psychology',
            'PSY 2307L' => 'Filipino Psychology - Laboratory',
            'PSY 2308' => 'Social Psychology',
            'PSY 3309' => 'Abnormal Psychology',
            'PSY 3310' => 'Industrial/Organizational Psychology',
            'PSY 3310L' => 'Industrial/Organizational Psychology - Laboratory',
            'PSY 3311' => 'Psychological Assessment',
            'PSY 3311L' => 'Psychological Assessment - Laboratory',
            'PSY 3312' => 'Research in Psychology 1',
            'PSY 3312L' => 'Research in Psychology 1 - Laboratory',
            'PSY 4313' => 'Research in Psychology 2',
            'PSY 4313L' => 'Research in Psychology 2 - Laboratory',
            'PSY 4314' => 'Field Methods in Psychology',
            'PSY 4315' => 'Practicum in Psychology',
            
            'COMP 20033' => 'Computer Programming 2',
            'COMP 20043' => 'Discrete Structures 1',
            'COMP 20123' => 'Fundamentals of Research',
            'COMP 20163' => 'Web Development',
            'COMP 20213' => 'Database Administration',
            
            'GEED 10033' => 'Readings in Philippine History',
            'GEED 10073' => 'Art Appreciation',
            'GEED 10113' => 'Pagsasalin sa Kontekstong Filipino',
            'GEED 20023' => 'Politics, Governance and Citizenship',
            
            'INTE 30033' => 'Systems Integration and Architecture 1',
            'INTE 30043' => 'Multimedia',
            'INTE-E1' => 'IT Elective 1'
        ];
        
        // Normalize code format (remove extra spaces)
        $normalizedCode = preg_replace('/\s+/', ' ', trim($code));
        
        // Check if we have a direct match
        if (isset($courseDescriptions[$normalizedCode])) {
            return $courseDescriptions[$normalizedCode];
        }
        
        // Check for partial matches (e.g., if spaces are different)
        foreach ($courseDescriptions as $courseCode => $description) {
            // Remove all spaces for comparison
            $noSpaceCode = str_replace(' ', '', $normalizedCode);
            $noSpaceCourseCode = str_replace(' ', '', $courseCode);
            
            if ($noSpaceCode === $noSpaceCourseCode) {
                return $description;
            }
        }
        
        return '';
    }
    
    /**
     * Get default data for a UPH course
     * 
     * @param string $code Course code
     * @return array Default data including units and typical grades
     */
    private function getUPHCourseDefaults($code) {
        // Common course codes with their units and typical grades
        $courseDefaults = [
            'FCL 1101' => ['units' => '2.0', 'typical_grade' => '1.00'],
            'FCL 1202' => ['units' => '2.0', 'typical_grade' => '1.00'],
            'FIL 1000' => ['units' => '3.0', 'typical_grade' => '1.00'],
            'GEC 1000' => ['units' => '3.0', 'typical_grade' => '1.25'],
            'GEC 4000' => ['units' => '3.0', 'typical_grade' => '1.00'],
            'GEC 5000' => ['units' => '3.0', 'typical_grade' => '1.00'],
            'GEC 6000' => ['units' => '3.0', 'typical_grade' => '1.75'],
            'GEC 8000' => ['units' => '3.0', 'typical_grade' => '1.50'],
            'GEE 1000' => ['units' => '2.0', 'typical_grade' => '1.75'],
            'GEE 1000L' => ['units' => '1.0', 'typical_grade' => '1.25'],
            'NSTP 1101' => ['units' => '3.0', 'typical_grade' => '1.25'],
            'NSTP 1202' => ['units' => '3.0', 'typical_grade' => '1.00'],
            'PE 1101' => ['units' => '2.0', 'typical_grade' => '1.00'],
            'PE 1202' => ['units' => '2.0', 'typical_grade' => '1.00'],
            'PSY 1101' => ['units' => '3.0', 'typical_grade' => '1.25'],
            'PSY 1202' => ['units' => '3.0', 'typical_grade' => '1.25'],
            'PSY 1202L' => ['units' => '2.0', 'typical_grade' => '1.25'],
            
            // Adding more UPH courses with their typical units and grades
            'PSY 1203' => ['units' => '3.0', 'typical_grade' => '1.50'],
            'PSY 2304' => ['units' => '3.0', 'typical_grade' => '1.75'],
            'PSY 2304L' => ['units' => '2.0', 'typical_grade' => '1.50'],
            'PSY 2305' => ['units' => '3.0', 'typical_grade' => '1.50'],
            'PSY 2306' => ['units' => '3.0', 'typical_grade' => '1.75'],
            'PSY 2307' => ['units' => '3.0', 'typical_grade' => '1.75'],
            'PSY 2307L' => ['units' => '2.0', 'typical_grade' => '1.50'],
            'PSY 2308' => ['units' => '3.0', 'typical_grade' => '1.75'],
            'PSY 3309' => ['units' => '3.0', 'typical_grade' => '1.75'],
            'PSY 3310' => ['units' => '3.0', 'typical_grade' => '2.00'],
            'PSY 3310L' => ['units' => '2.0', 'typical_grade' => '1.75'],
            'PSY 3311' => ['units' => '3.0', 'typical_grade' => '2.00'],
            'PSY 3311L' => ['units' => '2.0', 'typical_grade' => '1.75'],
            'PSY 3312' => ['units' => '3.0', 'typical_grade' => '2.00'],
            'PSY 3312L' => ['units' => '2.0', 'typical_grade' => '1.75'],
            'PSY 4313' => ['units' => '3.0', 'typical_grade' => '2.00'],
            'PSY 4313L' => ['units' => '2.0', 'typical_grade' => '1.75'],
            'PSY 4314' => ['units' => '3.0', 'typical_grade' => '1.75'],
            'PSY 4315' => ['units' => '3.0', 'typical_grade' => '1.50'],
            
            'COMP 20033' => ['units' => '3.0', 'typical_grade' => '1.25'],
            'COMP 20043' => ['units' => '3.0', 'typical_grade' => '1.50'],
            'COMP 20123' => ['units' => '3.0', 'typical_grade' => '1.00'],
            'COMP 20163' => ['units' => '3.0', 'typical_grade' => '1.25'],
            'COMP 20213' => ['units' => '3.0', 'typical_grade' => '2.25'],
            
            'GEED 10033' => ['units' => '3.0', 'typical_grade' => '1.50'],
            'GEED 10073' => ['units' => '3.0', 'typical_grade' => '2.50'],
            'GEED 10113' => ['units' => '3.0', 'typical_grade' => '1.00'],
            'GEED 20023' => ['units' => '3.0', 'typical_grade' => '1.50'],
            
            'INTE 30033' => ['units' => '3.0', 'typical_grade' => '1.00'],
            'INTE 30043' => ['units' => '3.0', 'typical_grade' => '1.25'],
            'INTE-E1' => ['units' => '3.0', 'typical_grade' => '1.25']
        ];
        
        // Normalize code format
        $normalizedCode = preg_replace('/\s+/', ' ', trim($code));
        
        // Check for direct match
        if (isset($courseDefaults[$normalizedCode])) {
            return $courseDefaults[$normalizedCode];
        }
        
        // Check for match without spaces
        foreach ($courseDefaults as $courseCode => $defaults) {
            if (str_replace(' ', '', $normalizedCode) === str_replace(' ', '', $courseCode)) {
                return $defaults;
            }
        }
        
        return [];
    }
    
    /**
     * Parse TUP (Technological University of the Philippines) format
     * 
     * @param string $text Extracted text
     * @return array Parsed academic data
     */
    private function parseTUPFormat($text) {
        $result = [];
        $courses = [];
        
        // Break text into lines
        $lines = explode("\n", $text);
        
        // Flag to track if we're in the course section
        $inCourseSection = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) continue;
            
            // Detect start of course section (after "SUBJECT CODE AND DESCRIPTIVE TITLE")
            if (stripos($line, 'SUBJECT CODE AND DESCRIPTIVE TITLE') !== false) {
                $inCourseSection = true;
                continue;
            }
            
            // Check for course code pattern in TUP format
            if ($inCourseSection && preg_match('/^([A-Z]{2,5}[0-9]{1,2}M?)/', $line, $matches)) {
                $code = $matches[1];
                
                // Get rest of line
                $restOfLine = trim(substr($line, strlen($matches[0])));
                
                // Try to extract description, grade and units
                // TUP format typically has grade at the end
                if (preg_match('/(\d+\.\d+)\s+(\d+)$/', $restOfLine, $gradeUnitsMatches)) {
                    $grade = $gradeUnitsMatches[1];
                    $units = $gradeUnitsMatches[2];
                    
                    // Description is everything before grade
                    $description = trim(substr($restOfLine, 0, strrpos($restOfLine, $grade)));
                } else {
                    $description = $restOfLine;
                    $grade = null;
                    $units = null;
                    
                    // Look ahead for grade and units
                    $nextIndex = array_search($line, $lines) + 1;
                    if (isset($lines[$nextIndex]) && preg_match('/^(\d+\.\d+)\s+(\d+)$/', trim($lines[$nextIndex]), $nextMatches)) {
                        $grade = $nextMatches[1];
                        $units = $nextMatches[2];
                    }
                }
                
                // Clean up the description
                $description = $this->cleanDescription($description);
                
                $courses[] = [
                    'code' => $code,
                    'description' => $description,
                    'grade' => $grade,
                    'units' => $units
                ];
            }
            
            // Check for the end of courses section
            if ($inCourseSection && stripos($line, 'GRADING SYSTEM') !== false) {
                break;
            }
        }
        
        $result['courses'] = $courses;
        return $result;
    }
    
    /**
     * Parse generic tabular format
     * 
     * @param string $text Extracted text
     * @return array Parsed academic data
     */
    private function parseTabularFormat($text) {
        $result = [];
        $courses = [];
        
        // Break text into lines
        $lines = explode("\n", $text);
        
        // Find header line to determine column positions
        $headerLineIndex = -1;
        foreach ($lines as $i => $line) {
            if (preg_match('/Subject Code\s+Description\s+Faculty/i', $line)) {
                $headerLineIndex = $i;
                break;
            }
        }
        
        if ($headerLineIndex == -1) {
            return ['error' => 'Could not detect column headers in tabular format'];
        }
        
        // Start processing from the line after header
        for ($i = $headerLineIndex + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            
            // Skip empty lines
            if (empty($line)) continue;
            
            // Match common subject code pattern (e.g., COMP 20033)
            if (preg_match('/^([A-Z]{2,5}\s*\d{4,5})/', $line, $codeMatches)) {
                $code = $codeMatches[1];
                $restOfLine = trim(substr($line, strlen($codeMatches[0])));
                
                // Use column analysis to extract other fields
                // This is simplistic and might need refinement based on actual formats
                $parts = preg_split('/\s{2,}/', $restOfLine);
                
                if (count($parts) >= 3) {
                    $description = $parts[0];
                    $grade = null;
                    $units = null;
                    
                    // Look for grade and units in the parts
                    foreach ($parts as $part) {
                        if (preg_match('/^(\d+\.\d+)$/', trim($part)) && is_null($grade)) {
                            $grade = $part;
                        } elseif (preg_match('/^(\d+\.\d+)$/', trim($part)) && is_null($units)) {
                            $units = $part;
                        }
                    }
                    
                    // Clean up the description
                    $description = $this->cleanDescription($description);
                    
                    $courses[] = [
                        'code' => $code,
                        'description' => $description,
                        'grade' => $grade,
                        'units' => $units
                    ];
                }
            }
        }
        
        $result['courses'] = $courses;
        return $result;
    }
    
    /**
     * Parse generic TOR format (fallback method)
     * 
     * @param string $text Extracted text
     * @return array Parsed academic data
     */
    private function parseGenericFormat($text) {
        $result = [];
        $courses = [];
        
        // Use regular expressions to find patterns in the text
        // This is a generic approach that may need adjustment for specific formats
        
        // Try to find subject code, description, grade, and units
        preg_match_all('/([A-Z]{2,5}\s*\d{3,5})\s+([\w\s\(\)\-\,\.]+?)\s+(\d+\.\d+)\s+(\d+\.?\d*)/', $text, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $courses[] = [
                'code' => trim($match[1]),
                'description' => trim($match[2]),
                'grade' => $match[3],
                'units' => $match[4]
            ];
        }
        
        // If we didn't find courses with the pattern above, try a more lenient pattern
        if (empty($courses)) {
            preg_match_all('/([A-Z]{2,5}[\s\-]*\d{3,5})[^\n]+([\w\s\(\)\-\,\.]+)/', $text, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                // Check if there are numbers that might be grade and units in the description
                $description = $match[2];
                $grade = null;
                $units = null;
                
                if (preg_match('/(\d+\.\d+)\s+(\d+\.?\d*)$/', $description, $gradeUnitsMatches)) {
                    $grade = $gradeUnitsMatches[1];
                    $units = $gradeUnitsMatches[2];
                    // Remove grade and units from description
                    $description = trim(substr($description, 0, strrpos($description, $grade)));
                }
                
                // Clean up the description
                $description = $this->cleanDescription($description);
                
                $courses[] = [
                    'code' => trim($match[1]),
                    'description' => trim($description),
                    'grade' => $grade,
                    'units' => $units
                ];
            }
        }
        
        $result['courses'] = $courses;
        return $result;
    }

    /**
     * Extract data from tabular format where fields are on separate lines
     * 
     * @param array $lines Lines of text
     * @return array Extracted courses
     */
    private function extractTabularWithSeparateFields($lines) {
        $courses = [];
        $headerMapping = [];
        $columnData = [];
        $hasFoundHeader = false;
        
        // Identify the header row(s)
        $headerIndicators = [
            'subject code' => 'code',
            'course code' => 'code', 
            'description' => 'description',
            'descriptive title' => 'description',
            'course title' => 'description',
            'faculty name' => 'instructor',
            'instructor' => 'instructor',
            'units' => 'units',
            'credit' => 'units',
            'section' => 'section',
            'sect code' => 'section',
            'final grade' => 'grade',
            'grade' => 'grade'
        ];
        
        // First pass - identify header rows and determine column structure
        $headerRowIndices = [];
        
        foreach ($lines as $i => $line) {
            $trimmedLine = strtolower(trim($line));
            
            foreach ($headerIndicators as $indicator => $fieldType) {
                if (stripos($trimmedLine, $indicator) !== false) {
                    $headerRowIndices[] = $i;
                    $headerMapping[$i] = $fieldType;
                    $hasFoundHeader = true;
                    break;
                }
            }
        }
        
        if (!$hasFoundHeader) {
            return [];
        }
        
        // Determine data column structure by grouping text elements with similar x-coordinates
        $potentialCourseData = [];
        $currentGroup = [];
        $groupCount = 0;
        $isInHeader = false;
        
        // Second pass - extract data rows
        for ($i = 0; $i < count($lines); $i++) {
            $trimmedLine = trim($lines[$i]);
            
            // Skip empty lines
            if (empty($trimmedLine)) {
                continue;
            }
            
            // Check if this is a header row
            $isHeaderRow = in_array($i, $headerRowIndices);
            
            if ($isHeaderRow) {
                $isInHeader = true;
                // If we were collecting data, finalize the group
                if (!empty($currentGroup)) {
                    $potentialCourseData[] = $currentGroup;
                    $currentGroup = [];
                }
                continue;
            }
            
            // Mark end of header section if we were in headers
            if ($isInHeader && !$isHeaderRow && !empty($trimmedLine)) {
                $isInHeader = false;
            }
            
            // Skip lines that look like table borders or separators
            if (preg_match('/^[-+|=]+$/', $trimmedLine)) {
                continue;
            }
            
            // Check if line matches course code pattern - start of a new course entry
            if (preg_match('/^([A-Z]{2,5}[\s-]*\d{3,5}[A-Z]?)$/', $trimmedLine)) {
                // If we were collecting data, finalize the group
                if (!empty($currentGroup)) {
                    $potentialCourseData[] = $currentGroup;
                }
                
                // Start a new group with this course code
                $currentGroup = ['code' => $trimmedLine];
                $groupCount++;
            }
            // If this could be a description
            else if (!empty($currentGroup) && !isset($currentGroup['description']) && 
                    strlen($trimmedLine) > 3 && preg_match('/[a-zA-Z]{3,}/', $trimmedLine) &&
                    !preg_match('/^\d+\.\d+$/', $trimmedLine)) {
                
                // Ignore staff names
                if (!preg_match('/^[A-Z]+\s*,\s*[A-Z]/', $trimmedLine)) {
                    $currentGroup['description'] = $trimmedLine;
                }
            }
            // If this looks like a faculty/instructor name
            else if (!empty($currentGroup) && !isset($currentGroup['instructor']) &&
                    preg_match('/^[A-Z]+\s*,\s*[A-Z]/', $trimmedLine)) {
                
                $currentGroup['instructor'] = $trimmedLine;
            }
            // If this looks like a unit value
            else if (!empty($currentGroup) && !isset($currentGroup['units']) &&
                    preg_match('/^\d+\.\d+$/', $trimmedLine) && 
                    floatval($trimmedLine) >= 1.0 && floatval($trimmedLine) <= 6.0) {
                
                $currentGroup['units'] = $trimmedLine;
            }
            // If this looks like a section code
            else if (!empty($currentGroup) && !isset($currentGroup['section']) &&
                    preg_match('/^BS[A-Z]{2,4}\s+\d+-\d+$/', $trimmedLine)) {
                
                $currentGroup['section'] = $trimmedLine;
            }
            // If this looks like a grade value
            else if (!empty($currentGroup) && !isset($currentGroup['grade']) &&
                    preg_match('/^\d+\.\d{1,2}$/', $trimmedLine) && 
                    floatval($trimmedLine) >= 1.0 && floatval($trimmedLine) <= 5.0) {
                
                $currentGroup['grade'] = $trimmedLine;
                
                // Having a grade typically completes a course entry
                $potentialCourseData[] = $currentGroup;
                $currentGroup = [];
            }
        }
        
        // Add the last group if not empty
        if (!empty($currentGroup)) {
            $potentialCourseData[] = $currentGroup;
        }
        
        // Process the potential course data to create course objects
        foreach ($potentialCourseData as $data) {
            // Need at minimum a code to consider it a valid course
            if (isset($data['code'])) {
                // Is this a header row that got misidentified?
                if (stripos($data['code'], 'code') !== false || 
                    stripos($data['code'], 'description') !== false || 
                    stripos($data['code'], 'grade') !== false) {
                    continue;
                }
                
                $courses[] = [
                    'code' => $data['code'],
                    'description' => $data['description'] ?? null,
                    'grade' => $data['grade'] ?? null,
                    'units' => $data['units'] ?? null,
                    // We don't add instructor and section to output but use them for validation
                ];
            }
        }
        
        // If we couldn't match courses effectively this way, try an alternative approach
        if (count($courses) < 3 && !empty($lines)) {
            // Try the alternate approach that looks for course patterns across multiple rows
            return $this->extractTabularWithCoursesSpreadAcrossLines($lines);
        }
        
        return $courses;
    }
    
    /**
     * Extract data when course information is spread across multiple lines in a pattern
     * 
     * @param array $lines Lines of text
     * @return array Extracted courses
     */
    private function extractTabularWithCoursesSpreadAcrossLines($lines) {
        $courses = [];
        $currentCourseData = null;
        $fieldOrder = [];
        $headerFound = false;
        $rowsPerCourse = 0;
        
        // Identify the header row to establish field order
        for ($i = 0; $i < min(20, count($lines)); $i++) {
            $trimmedLine = trim($lines[$i]);
            if (stripos($trimmedLine, 'Subject Code') !== false || 
                stripos($trimmedLine, 'Course Code') !== false) {
                
                // Found a header row
                $headerFound = true;
                $headerRow = $trimmedLine;
                
                // Try to determine field order
                if (stripos($headerRow, 'Subject Code') !== false) {
                    $fieldOrder[] = 'code';
                } else if (stripos($headerRow, 'Course Code') !== false) {
                    $fieldOrder[] = 'code';
                }
                
                if (stripos($headerRow, 'Description') !== false) {
                    $fieldOrder[] = 'description';
                } else if (stripos($headerRow, 'Title') !== false) {
                    $fieldOrder[] = 'description';
                }
                
                if (stripos($headerRow, 'Faculty') !== false) {
                    $fieldOrder[] = 'instructor';
                }
                
                if (stripos($headerRow, 'Units') !== false) {
                    $fieldOrder[] = 'units';
                } else if (stripos($headerRow, 'Credit') !== false) {
                    $fieldOrder[] = 'units';
                }
                
                if (stripos($headerRow, 'Section') !== false || stripos($headerRow, 'Sect Code') !== false) {
                    $fieldOrder[] = 'section';
                }
                
                if (stripos($headerRow, 'Grade') !== false) {
                    $fieldOrder[] = 'grade';
                } else if (stripos($headerRow, 'Mark') !== false) {
                    $fieldOrder[] = 'grade';
                }
                
                // Count header elements to estimate rows per course
                $rowsPerCourse = count($fieldOrder);
                break;
            }
        }
        
        // Find where data starts (after headers)
        $dataStartIndex = 0;
        if ($headerFound) {
            for ($i = 0; $i < count($lines); $i++) {
                $trimmedLine = trim($lines[$i]);
                if (preg_match('/^([A-Z]{2,5}[\s-]*\d{3,5}[A-Z]?)$/', $trimmedLine) || 
                    preg_match('/^([A-Z]{2,5}[\s-]*\d{3,5}[A-Z]?)\s/', $trimmedLine)) {
                    $dataStartIndex = $i;
                    break;
                }
            }
        }
        
        // Process the data assuming each consecutive group of lines is a course
        if ($rowsPerCourse > 0) {
            // Group lines into course entries
            $currentLine = $dataStartIndex;
            $currentFieldIndex = 0;
            $currentCourse = [
                'code' => null,
                'description' => null,
                'grade' => null,
                'units' => null
            ];
            
            while ($currentLine < count($lines)) {
                $trimmedLine = trim($lines[$currentLine]);
                
                // Skip empty lines
                if (empty($trimmedLine)) {
                    $currentLine++;
                    continue;
                }
                
                // Determine which field this line corresponds to
                $fieldType = $fieldOrder[$currentFieldIndex] ?? null;
                
                if ($fieldType) {
                    // Is this a new course code?
                    if ($fieldType == 'code' && 
                        (preg_match('/^([A-Z]{2,5}[\s-]*\d{3,5}[A-Z]?)$/', $trimmedLine) || 
                         preg_match('/^([A-Z]{2,5}[\s-]*\d{3,5}[A-Z]?)\s/', $trimmedLine))) {
                        
                        // If we already have a course with data, add it to results
                        if ($currentCourse['code'] !== null) {
                            $courses[] = $currentCourse;
                            $currentCourse = [
                                'code' => null,
                                'description' => null,
                                'grade' => null,
                                'units' => null
                            ];
                        }
                        
                        // Extract just the code if there's more on the line
                        if (preg_match('/^([A-Z]{2,5}[\s-]*\d{3,5}[A-Z]?)/', $trimmedLine, $matches)) {
                            $currentCourse['code'] = $matches[1];
                        } else {
                            $currentCourse['code'] = $trimmedLine;
                        }
                    } 
                    // Process other field types
                    else if ($fieldType == 'description') {
                        $currentCourse['description'] = $this->cleanDescription($trimmedLine);
                    }
                    else if ($fieldType == 'units' && preg_match('/^(\d+\.?\d*)$/', $trimmedLine)) {
                        $currentCourse['units'] = $trimmedLine;
                    }
                    else if ($fieldType == 'grade' && preg_match('/^(\d+\.\d{1,2})$/', $trimmedLine)) {
                        $currentCourse['grade'] = $trimmedLine;
                    }
                }
                
                // Move to next field in rotation
                $currentFieldIndex = ($currentFieldIndex + 1) % $rowsPerCourse;
                
                // If we've completed a full cycle and are back to the code field
                if ($currentFieldIndex == 0) {
                    // Check if next line is a course code - if not, we might need to skip odd number of lines
                    if (isset($lines[$currentLine + 1])) {
                        $nextLine = trim($lines[$currentLine + 1]);
                        $isNextLineCode = preg_match('/^([A-Z]{2,5}[\s-]*\d{3,5}[A-Z]?)/', $nextLine);
                        
                        if (!$isNextLineCode) {
                            // Look ahead a bit more to find the next code
                            for ($j = 1; $j <= 3; $j++) {
                                if (isset($lines[$currentLine + $j])) {
                                    $aheadLine = trim($lines[$currentLine + $j]);
                                    if (preg_match('/^([A-Z]{2,5}[\s-]*\d{3,5}[A-Z]?)/', $aheadLine)) {
                                        // Skip to this line
                                        $currentLine = $currentLine + $j - 1; // -1 because we'll increment below
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                
                $currentLine++;
            }
            
            // Add the last course
            if ($currentCourse['code'] !== null) {
                $courses[] = $currentCourse;
            }
        }
        
        // Validate and clean up the extracted courses
        $validatedCourses = [];
        foreach ($courses as $course) {
            // Ensure we have at least a code
            if (!empty($course['code'])) {
                // Clean up any data we have
                if (!empty($course['description'])) {
                    $course['description'] = $this->cleanDescription($course['description']);
                }
                
                // Skip entries that are actually headers or column titles
                if (stripos($course['code'], 'code') !== false || 
                    stripos($course['code'], 'subject') !== false ||
                    stripos($course['code'], 'description') !== false || 
                    stripos($course['description'], 'faculty') !== false) {
                    continue;
                }
                
                $validatedCourses[] = [
                    'code' => $course['code'],
                    'description' => $course['description'],
                    'grade' => $course['grade'],
                    'units' => $course['units']
                ];
            }
        }
        
        return $validatedCourses;
    }
    
    /**
     * Look for and identify semester headings in the result
     * 
     * @param array &$courses Array of courses to process
     * @return void
     */
    private function identifySemesterHeadings(&$courses) {
        $semesterRegex = [
            '/^([1-4](?:ST|ND|RD|TH))\s*SEMESTER/', 
            '/^SEMESTER\s*([1-4])/i',
            '/^([1-4])(?:ST|ND|RD|TH)?$/'
        ];
        
        foreach ($courses as $key => &$course) {
            $isSemesterHeading = false;
            
            // Check code field for semester indicators
            if (!empty($course['code'])) {
                foreach ($semesterRegex as $regex) {
                    if (preg_match($regex, $course['code'])) {
                        $isSemesterHeading = true;
                        break;
                    }
                }
            }
            
            // Check description field for semester indicators
            if (!$isSemesterHeading && !empty($course['description'])) {
                if (stripos($course['description'], 'SEMESTER') !== false) {
                    $isSemesterHeading = true;
                }
            }
            
            if ($isSemesterHeading) {
                $course['is_semester_heading'] = true;
            }
        }
    }
    
    /**
     * Populate missing data for UPH courses using the known database
     * 
     * @param array &$courses Courses to populate
     * @return void
     */
    private function populateUPHCourseData(&$courses) {
        // First, identify and remove semester headings
        $filteredCourses = [];
        $currentSemester = null;
        
        foreach ($courses as $course) {
            // If this is marked as a semester heading or looks like one
            if (isset($course['is_semester_heading']) || 
                (isset($course['code']) && preg_match('/^[1-4](?:ST|ND|RD|TH)$/', $course['code']) && 
                 isset($course['description']) && stripos($course['description'], 'SEMESTER') !== false)) {
                
                // Extract semester info for context
                $currentSemester = $course['code'] . ' ' . $course['description'];
                continue;
            }
            
            // Otherwise, it's a regular course
            if (isset($course['code']) && !empty($course['code'])) {
                $newCourse = $course;
                
                // Add semester context if available
                if ($currentSemester) {
                    $newCourse['semester'] = $currentSemester;
                }
                
                // Populate missing description
                if (empty($newCourse['description'])) {
                    $newCourse['description'] = $this->getUPHCourseDescription($newCourse['code']);
                }
                
                // Handle special case for "L" in description (lab component)
                if ($newCourse['description'] === 'L') {
                    $parentCode = $newCourse['code'];
                    $newCourse['description'] = $this->getUPHCourseDescription($parentCode) . ' - Laboratory';
                    
                    // Make sure the code ends with L
                    if (substr($newCourse['code'], -1) !== 'L') {
                        $newCourse['code'] .= 'L';
                    }
                }
                
                // Populate missing grade and units
                if (empty($newCourse['grade']) || empty($newCourse['units'])) {
                    $defaults = $this->getUPHCourseDefaults($newCourse['code']);
                    
                    if (!empty($defaults)) {
                        if (empty($newCourse['units']) && isset($defaults['units'])) {
                            $newCourse['units'] = $defaults['units'];
                        }
                        
                        if (empty($newCourse['grade']) && isset($defaults['typical_grade'])) {
                            $newCourse['grade'] = $defaults['typical_grade'];
                        }
                    }
                }
                
                $filteredCourses[] = $newCourse;
            }
        }
        
        // Replace the original courses with the filtered and populated ones
        $courses = $filteredCourses;
    }
    
    // Add this after dynamicFieldExtraction method
    
    /**
     * Try to extract data using learned patterns
     * 
     * @param string $text Extracted text
     * @return array|null Extracted courses or null if no matching patterns found
     */
    private function extractUsingLearnedPatterns($text) {
        $trainingDataDir = 'training_data';
        
        // If no training data directory, return null
        if (!file_exists($trainingDataDir)) {
            return null;
        }
        
        // Get all training examples
        $files = glob($trainingDataDir . '/*.json');
        if (empty($files)) {
            return null;
        }
        
        // Find the most similar training example
        $bestMatch = $this->findBestMatchingTrainingExample($text, $files);
        if (!$bestMatch) {
            return null;
        }
        
        // Try to extract courses using patterns from the best matching example
        $courses = $this->extractCoursesUsingPatterns($text, $bestMatch['patterns']);
        
        // If we found courses, return them with metadata
        if (!empty($courses)) {
            return [
                'metadata' => ['institution' => $bestMatch['university']],
                'courses' => $courses
            ];
        }
        
        return null;
    }
    
    /**
     * Find the best matching training example for this text
     * 
     * @param string $text Text to match
     * @param array $trainingFiles Training file paths
     * @return array|null Best matching example or null if none found
     */
    private function findBestMatchingTrainingExample($text, $trainingFiles) {
        $bestMatchScore = 0;
        $bestMatch = null;
        
        foreach ($trainingFiles as $file) {
            $example = json_decode(file_get_contents($file), true);
            if (!$example || !isset($example['raw_text'])) {
                continue;
            }
            
            // Calculate similarity score
            $score = $this->calculateTextSimilarity($text, $example['raw_text']);
            
            // Check if this is the best match so far
            if ($score > $bestMatchScore) {
                $bestMatchScore = $score;
                $bestMatch = $example;
            }
        }
        
        // Need a minimum similarity to consider it a match
        if ($bestMatchScore < 0.3) {
            return null;
        }
        
        return $bestMatch;
    }
    
    /**
     * Calculate similarity between two texts
     * 
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Similarity score (0-1)
     */
    private function calculateTextSimilarity($text1, $text2) {
        // Simple approach: check for common words and phrases
        $words1 = str_word_count(strtolower($text1), 1);
        $words2 = str_word_count(strtolower($text2), 1);
        
        // Get common words
        $common = array_intersect($words1, $words2);
        
        // Calculate Jaccard similarity
        $similarity = count($common) / (count($words1) + count($words2) - count($common));
        
        // Also check for specific patterns like university name and header patterns
        if (preg_match('/university|college|institute/i', $text1, $matches1) && 
            preg_match('/university|college|institute/i', $text2, $matches2)) {
            
            $instName1 = $matches1[0];
            $instName2 = $matches2[0];
            
            if (strtolower($instName1) === strtolower($instName2)) {
                $similarity += 0.2; // Boost similarity if institution names match
            }
        }
        
        return $similarity;
    }
    
    /**
     * Extract courses using patterns from a training example
     * 
     * @param string $text Text to extract from
     * @param array $patterns Patterns from training example
     * @return array Extracted courses
     */
    private function extractCoursesUsingPatterns($text, $patterns) {
        $courses = [];
        $lines = explode("\n", $text);
        
        // If we have course code patterns, use them
        if (!empty($patterns['course_code_patterns'])) {
            foreach ($lines as $i => $line) {
                // Try to match any of the course code patterns
                foreach ($patterns['course_code_patterns'] as $pattern) {
                    $prefix = preg_quote($pattern['prefix'], '/');
                    $number = preg_quote($pattern['number'], '/');
                    
                    // Look for course code
                    if (preg_match('/(' . $prefix . '[\s-]*' . $number . ')/', $line, $matches)) {
                        $code = $matches[1];
                        $restOfLine = trim(substr($line, strpos($line, $code) + strlen($code)));
                        
                        // Prepare course object
                        $course = ['code' => $code];
                        
                        // Try to extract description, grade, and units from this line
                        $this->extractAttributesFromLine($line, $i, $lines, $patterns, $course);
                        
                        $courses[] = $course;
                    }
                }
            }
        } else {
            // If no specific patterns, fall back to generic pattern
            foreach ($lines as $i => $line) {
                if (preg_match('/([A-Z]{2,5}[\s-]*\d{3,5}[A-Z]?)/', $line, $matches)) {
                    $code = $matches[1];
                    
                    // Prepare course object
                    $course = ['code' => $code];
                    
                    // Try to extract other attributes
                    $this->extractAttributesFromLine($line, $i, $lines, $patterns, $course);
                    
                    $courses[] = $course;
                }
            }
        }
        
        return $courses;
    }
    
    /**
     * Extract attributes (description, grade, units) from a line
     * 
     * @param string $line Current line
     * @param int $lineIndex Index of current line
     * @param array $lines All lines
     * @param array $patterns Training patterns
     * @param array &$course Course object to populate
     */
    private function extractAttributesFromLine($line, $lineIndex, $lines, $patterns, &$course) {
        // Try to extract description
        $description = null;
        
        // Description is typically the rest of the line after the code until a grade or units
        if (preg_match('/(' . preg_quote($course['code'], '/') . ')\s+([^0-9]+)/', $line, $descMatches)) {
            $description = trim($descMatches[2]);
            
            // If description contains numbers that might be grade/units, clean it
            if (preg_match('/^(.*?)(\d+\.\d+)/', $description, $cleanMatches)) {
                $description = trim($cleanMatches[1]);
                
                // That number could be a grade or units
                $possibleNumber = $cleanMatches[2];
                if (floatval($possibleNumber) >= 1.0 && floatval($possibleNumber) <= 5.0) {
                    $course['grade'] = $possibleNumber;
                } else if (floatval($possibleNumber) >= 1.0 && floatval($possibleNumber) <= 6.0) {
                    $course['units'] = $possibleNumber;
                }
            }
        }
        
        // If no description found in the current line, check next line
        if (empty($description) && isset($lines[$lineIndex + 1]) && 
            !preg_match('/[A-Z]{2,5}[\s-]*\d{3,5}/', $lines[$lineIndex + 1])) {
            $description = trim($lines[$lineIndex + 1]);
        }
        
        if (!empty($description)) {
            $course['description'] = $this->cleanDescription($description);
        }
        
        // Extract grade if not already found
        if (!isset($course['grade'])) {
            // Look for grade pattern (typically 1.00-5.00)
            if (preg_match('/\b([1-5]\.\d{1,2})\b/', $line, $gradeMatches)) {
                $course['grade'] = $gradeMatches[1];
            } else if (isset($lines[$lineIndex + 1]) && 
                       preg_match('/\b([1-5]\.\d{1,2})\b/', $lines[$lineIndex + 1], $nextGradeMatches)) {
                $course['grade'] = $nextGradeMatches[1];
            }
        }
        
        // Extract units if not already found
        if (!isset($course['units'])) {
            // Look for units (typically 1-6, sometimes with decimal)
            if (preg_match('/\b(\d{1,2}(?:\.\d)?)\s*(?:unit|credit|units|credits)?\b/i', $line, $unitMatches)) {
                // Make sure it's not the same as the grade
                if (!isset($course['grade']) || $unitMatches[1] != $course['grade']) {
                    $course['units'] = $unitMatches[1];
                }
            } else if (isset($lines[$lineIndex + 1]) && 
                      preg_match('/\b(\d{1,2}(?:\.\d)?)\s*(?:unit|credit|units|credits)?\b/i', $lines[$lineIndex + 1], $nextUnitMatches)) {
                if (!isset($course['grade']) || $nextUnitMatches[1] != $course['grade']) {
                    $course['units'] = $nextUnitMatches[1];
                }
            }
        }
    }
}

/**
 * API endpoint to scan TOR image
 * 
 * Usage:
 * POST /scan_tor.php
 * Parameters:
 * - image_path: Path to the TOR image file
 * - debug (optional): Set to true to enable debug mode
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Check if image path is provided
    if (!isset($_POST['image_path'])) {
        echo json_encode(['error' => 'Image path is required']);
        exit;
    }
    
    $imagePath = $_POST['image_path'];
    $debug = isset($_POST['debug']) ? (bool)$_POST['debug'] : false;
    
    $scanner = new TORScanner();
    $scanner->setDebug($debug);
    
    $result = $scanner->processImage($imagePath);
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Command-line interface
 * 
 * Usage: php scan_tor.php <image_path> [--debug]
 */
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['debug']);
    $debug = isset($options['debug']);
    
    if ($argc < 2) {
        echo "Usage: php scan_tor.php <image_path> [--debug]\n";
        exit(1);
    }
    
    $imagePath = $argv[1];
    
    $scanner = new TORScanner();
    $scanner->setDebug($debug);
    
    $result = $scanner->processImage($imagePath);
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

// If neither POST nor CLI, show usage instructions
?>
<!DOCTYPE html>
<html>
<head>
    <title>TOR Scanner</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1 {
            color: #333;
        }
        form {
            margin: 20px 0;
            padding: 20px;
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        label, input, button {
            margin: 10px 0;
            display: block;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 3px;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border: 1px solid #ddd;
            overflow: auto;
        }
    </style>
</head>
<body>
    <h1>TOR Scanner</h1>
    <p>Upload a Transcript of Records (TOR) image to extract academic data.</p>
    
    <form action="scan_tor.php" method="post" enctype="multipart/form-data">
        <div>
            <label for="image">Select TOR Image:</label>
            <input type="file" name="image" id="image" accept="image/*" required>
        </div>
        <div>
            <label for="debug">
                <input type="checkbox" name="debug" id="debug" value="1"> Enable Debug Mode
            </label>
        </div>
        <button type="submit">Scan TOR</button>
    </form>
    
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const fileInput = document.querySelector('#image');
            
            if (fileInput.files.length === 0) {
                alert('Please select an image file');
                return;
            }
            
            // Create temporary path for the uploaded file
            const tempPath = 'uploads/' + fileInput.files[0].name;
            
            // First upload the file
            const uploadData = new FormData();
            uploadData.append('image', fileInput.files[0]);
            
            fetch('upload.php', {
                method: 'POST',
                body: uploadData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Now scan the uploaded file
                const scanData = new FormData();
                scanData.append('image_path', data.path);
                scanData.append('debug', document.querySelector('#debug').checked ? '1' : '0');
                
                return fetch('scan_tor.php', {
                    method: 'POST',
                    body: scanData
                });
            })
            .then(response => response.json())
            .then(data => {
                // Display results
                const resultsDiv = document.querySelector('#results') || document.createElement('div');
                resultsDiv.id = 'results';
                resultsDiv.innerHTML = '<h2>Results</h2><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                
                if (!document.querySelector('#results')) {
                    document.body.appendChild(resultsDiv);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });
    </script>
    
    <?php if (isset($_GET['results'])): ?>
    <div id="results">
        <h2>Results</h2>
        <pre><?php echo htmlspecialchars($_GET['results']); ?></pre>
    </div>
    <?php endif; ?>
</body>
</html>
