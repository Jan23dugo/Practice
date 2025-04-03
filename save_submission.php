<?php
// Database connection
$db = new PDO('mysql:host=localhost;dbname=your_exam_db', 'username', 'password');

// Get data from POST request
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['code']) || !isset($data['language']) || !isset($data['questionId']) || !isset($data['studentId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Prepare query
$stmt = $db->prepare('INSERT INTO submissions (student_id, question_id, language, code, status, score, submitted_at) 
                     VALUES (:student_id, :question_id, :language, :code, :status, :score, NOW())');

// Execute query
try {
    $stmt->execute([
        ':student_id' => $data['studentId'],
        ':question_id' => $data['questionId'],
        ':language' => $data['language'],
        ':code' => $data['code'],
        ':status' => 'submitted',
        ':score' => $data['score'] ?? 0
    ]);
    
    echo json_encode(['success' => true, 'submission_id' => $db->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
