<?php
// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="question_bank_template.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'question_text', 'question_type', 'points', 'category',
    'answer_1', 'answer_2', 'answer_3', 'answer_4',
    'correct_1', 'correct_2', 'correct_3', 'correct_4',
    'correct_answer', 'language', 'starter_code',
    'input_1', 'expected_output_1', 'is_hidden_1', 'description_1',
    'input_2', 'expected_output_2', 'is_hidden_2', 'description_2'
]);

// Add example rows
// Multiple choice example
fputcsv($output, [
    'What is the capital of France?', 'multiple-choice', '1', 'Geography',
    'Paris', 'London', 'Berlin', 'Madrid',
    '1', '0', '0', '0',
    '', '', '',
    '', '', '', '',
    '', '', '', ''
]);

// True/False example
fputcsv($output, [
    'The sky is blue.', 'true-false', '1', 'General Knowledge',
    '', '', '', '',
    '', '', '', '',
    'true', '', '',
    '', '', '', '',
    '', '', '', ''
]);

// Programming example - properly escape the starter code
$starter_code = "def add_numbers(a, b):\n    # Your code here\n    pass";
fputcsv($output, [
    'Write a function that returns the sum of two numbers.', 'programming', '2', 'Programming',
    '', '', '', '',
    '', '', '', '',
    '', 'python', $starter_code,
    '5 7', '12', '0', 'Basic addition',
    '10 -3', '7', '0', 'Handling negative numbers'
]);

fclose($output);
exit;
?> 