<?php
require_once 'tor_training.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Training Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1, h2, h3 {
            color: #333;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border: 1px solid #ddd;
            overflow: auto;
        }
        .back-link {
            margin-bottom: 20px;
            display: inline-block;
        }
        .card {
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .pattern-box {
            background: #fff;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <a href="train.php" class="back-link">← Back to Training Interface</a>
    
    <?php
    // Check if a specific file was requested
    if (isset($_GET['file'])) {
        $file = 'training_data/' . basename($_GET['file']);
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            
            if ($data) {
                echo '<h1>Training Data Details</h1>';
                
                echo '<div class="card">';
                echo '<h2>University: ' . htmlspecialchars($data['university']) . '</h2>';
                echo '<p>Training file: ' . htmlspecialchars(basename($file)) . '</p>';
                echo '</div>';
                
                echo '<h2>Courses</h2>';
                
                if (isset($data['correct_data']['courses']) && !empty($data['correct_data']['courses'])) {
                    echo '<table>';
                    echo '<tr><th>Code</th><th>Description</th><th>Grade</th><th>Units</th></tr>';
                    
                    foreach ($data['correct_data']['courses'] as $course) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($course['code'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($course['description'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($course['grade'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($course['units'] ?? '') . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                } else {
                    echo '<p>No course data found.</p>';
                }
                
                echo '<h2>Learned Patterns</h2>';
                
                if (isset($data['patterns']) && !empty($data['patterns'])) {
                    // Display header patterns
                    if (!empty($data['patterns']['header_patterns'])) {
                        echo '<h3>Header Patterns</h3>';
                        foreach ($data['patterns']['header_patterns'] as $pattern) {
                            echo '<div class="pattern-box">' . htmlspecialchars($pattern) . '</div>';
                        }
                    }
                    
                    // Display course code patterns
                    if (!empty($data['patterns']['course_code_patterns'])) {
                        echo '<h3>Course Code Patterns</h3>';
                        foreach ($data['patterns']['course_code_patterns'] as $pattern) {
                            echo '<div class="pattern-box">';
                            echo '<strong>Prefix:</strong> ' . htmlspecialchars($pattern['prefix']) . '<br>';
                            echo '<strong>Number:</strong> ' . htmlspecialchars($pattern['number']) . '<br>';
                            echo '<strong>Context:</strong> ' . htmlspecialchars($pattern['context']);
                            echo '</div>';
                        }
                    }
                    
                    // Display grade patterns
                    if (!empty($data['patterns']['grade_patterns'])) {
                        echo '<h3>Grade Patterns</h3>';
                        foreach ($data['patterns']['grade_patterns'] as $pattern) {
                            echo '<div class="pattern-box">';
                            echo '<strong>Value:</strong> ' . htmlspecialchars($pattern['value']) . '<br>';
                            echo '<strong>Context:</strong> ' . htmlspecialchars($pattern['context']);
                            echo '</div>';
                        }
                    }
                    
                    // Display units patterns
                    if (!empty($data['patterns']['units_patterns'])) {
                        echo '<h3>Units Patterns</h3>';
                        foreach ($data['patterns']['units_patterns'] as $pattern) {
                            echo '<div class="pattern-box">';
                            echo '<strong>Value:</strong> ' . htmlspecialchars($pattern['value']) . '<br>';
                            echo '<strong>Context:</strong> ' . htmlspecialchars($pattern['context']);
                            echo '</div>';
                        }
                    }
                    
                    // Display semester patterns
                    if (!empty($data['patterns']['semester_patterns'])) {
                        echo '<h3>Semester Patterns</h3>';
                        foreach ($data['patterns']['semester_patterns'] as $pattern) {
                            echo '<div class="pattern-box">';
                            echo '<strong>Semester:</strong> ' . htmlspecialchars($pattern['semester']) . '<br>';
                            echo '<strong>Full Text:</strong> ' . htmlspecialchars($pattern['full_text']);
                            echo '</div>';
                        }
                    }
                } else {
                    echo '<p>No patterns were extracted from this training example.</p>';
                }
                
                echo '<h2>Raw OCR Text</h2>';
                echo '<pre>' . htmlspecialchars($data['raw_text']) . '</pre>';
            } else {
                echo '<h1>Error</h1>';
                echo '<p>Invalid training data format.</p>';
            }
        } else {
            echo '<h1>Error</h1>';
            echo '<p>Training file not found.</p>';
        }
    } else {
        // No specific file requested, list all files
        $trainer = new TORTraining();
        $examples = $trainer->getAllTrainingExamples();
        
        echo '<h1>All Training Examples</h1>';
        
        if (!empty($examples)) {
            echo '<ul>';
            foreach ($examples as $i => $example) {
                echo '<li>';
                echo '<strong>' . htmlspecialchars($example['university']) . '</strong> - ';
                echo 'Courses: ' . (isset($example['correct_data']['courses']) ? count($example['correct_data']['courses']) : 0);
                echo ' <a href="?file=' . urlencode(basename($trainer->getTrainingFiles()[$i])) . '">View Details</a>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No training examples found.</p>';
        }
    }
    ?>
</body>
</html> 