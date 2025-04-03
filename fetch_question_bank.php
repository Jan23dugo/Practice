<?php
include('config/config.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Build the base query with proper table aliases and joins
$query = "SELECT 
            qb.question_id,
            qb.question_text,
            qb.question_type,
            qb.category,
            qb.points,
            GROUP_CONCAT(
                DISTINCT 
                CONCAT(qba.answer_text, ':::', CAST(qba.is_correct AS CHAR(1)), ':::', CAST(qba.position AS CHAR(1)))
                ORDER BY qba.position ASC
            ) as answer_data,
            qbp.starter_code,
            qbp.language,
            qbp.programming_id,
            COUNT(DISTINCT qbt.test_case_id) as test_case_count
          FROM question_bank qb
          LEFT JOIN question_bank_answers qba ON qb.question_id = qba.question_id
          LEFT JOIN question_bank_programming qbp ON qb.question_id = qbp.question_id
          LEFT JOIN question_bank_test_cases qbt ON qbp.programming_id = qbt.programming_id
          WHERE 1=1";

// Add filters
if (!empty($search)) {
    $query .= " AND (qb.question_text LIKE ? OR qb.category LIKE ?)";
}
if (!empty($type)) {
    $query .= " AND qb.question_type = ?";
}
if (!empty($category)) {
    $query .= " AND qb.category = ?";
}

$query .= " GROUP BY qb.question_id ORDER BY qb.question_id DESC";

try {
    // Prepare and execute the query
    $stmt = $conn->prepare($query);

    // Bind parameters if they exist
    if (!empty($search) && !empty($type) && !empty($category)) {
        $search_param = "%$search%";
        $stmt->bind_param("ssss", $search_param, $search_param, $type, $category);
    } else if (!empty($search) && !empty($type)) {
        $search_param = "%$search%";
        $stmt->bind_param("sss", $search_param, $search_param, $type);
    } else if (!empty($search)) {
        $search_param = "%$search%";
        $stmt->bind_param("ss", $search_param, $search_param);
    } else if (!empty($type)) {
        $stmt->bind_param("s", $type);
    } else if (!empty($category)) {
        $stmt->bind_param("s", $category);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $questions = [];

    while ($row = $result->fetch_assoc()) {
        // Format the answers
        $formatted_answers = [];
        if ($row['answer_data']) {
            $answers_array = explode(',', $row['answer_data']);
            foreach ($answers_array as $answer_data) {
                list($text, $is_correct, $position) = explode(':::', $answer_data);
                $formatted_answers[] = [
                    'text' => $text,
                    'is_correct' => $is_correct == '1',
                    'position' => (int)$position
                ];
            }
        }
        
        // Build the question array
        $question = [
            'question_id' => $row['question_id'],
            'question_text' => $row['question_text'],
            'question_type' => $row['question_type'],
            'category' => $row['category'],
            'points' => (int)$row['points'],
            'formatted_answers' => $formatted_answers
        ];

        // Add programming-specific fields if applicable
        if ($row['question_type'] === 'programming') {
            $question['language'] = $row['language'];
            $question['starter_code'] = $row['starter_code'];
            $question['test_case_count'] = (int)$row['test_case_count'];
        }

        $questions[] = $question;
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'questions' => $questions,
        'count' => count($questions)
    ]);

} catch (Exception $e) {
    // Return error response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
