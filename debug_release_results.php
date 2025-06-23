<?php
session_start();
require_once('config/config.php');

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("Access denied. Admin login required.");
}

echo "<h2>Debug: Result Release System</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background: #d4edda; border-color: #c3e6cb; }
    .error { background: #f8d7da; border-color: #f5c6cb; }
    .info { background: #d1ecf1; border-color: #bee5eb; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

// 1. Check if POST data is being received
echo "<div class='debug-section info'>";
echo "<h3>1. POST Data Check</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p><strong>✅ POST request received</strong></p>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
} else {
    echo "<p><strong>ℹ️ No POST data (this is normal for initial page load)</strong></p>";
    echo "<p>To test, go back to manage_results.php and try releasing results again.</p>";
}
echo "</div>";

// 2. Check database connection
echo "<div class='debug-section " . ($conn ? 'success' : 'error') . "'>";
echo "<h3>2. Database Connection</h3>";
if ($conn) {
    echo "<p><strong>✅ Database connected successfully</strong></p>";
    echo "<p>Server: " . $conn->server_info . "</p>";
} else {
    echo "<p><strong>❌ Database connection failed</strong></p>";
    die("Cannot proceed without database connection.");
}
echo "</div>";

// 3. Check exam_assignments table structure
echo "<div class='debug-section info'>";
echo "<h3>3. Table Structure Check</h3>";
$structure_query = "DESCRIBE exam_assignments";
$structure_result = $conn->query($structure_query);

if ($structure_result) {
    echo "<p><strong>✅ exam_assignments table exists</strong></p>";
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $structure_result->fetch_assoc()) {
        $highlight = '';
        if (in_array($row['Field'], ['result_message', 'next_steps', 'is_released', 'passed'])) {
            $highlight = 'style="background-color: #fff3cd;"';
        }
        echo "<tr $highlight>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if required columns exist
    $required_columns = ['result_message', 'next_steps', 'is_released', 'passed'];
    $existing_columns = [];
    $structure_result = $conn->query($structure_query);
    while ($row = $structure_result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    $missing_columns = array_diff($required_columns, $existing_columns);
    if (empty($missing_columns)) {
        echo "<p><strong>✅ All required columns exist</strong></p>";
    } else {
        echo "<p><strong>❌ Missing columns: " . implode(', ', $missing_columns) . "</strong></p>";
    }
} else {
    echo "<p><strong>❌ Cannot access exam_assignments table</strong></p>";
    echo "<p>Error: " . $conn->error . "</p>";
}
echo "</div>";

// 4. Check for completed exams
echo "<div class='debug-section info'>";
echo "<h3>4. Completed Exams Check</h3>";
$completed_query = "SELECT DISTINCT e.exam_id, e.title, 
                    COUNT(ea.student_id) as total_submissions,
                    SUM(CASE WHEN ea.passed = 1 THEN 1 ELSE 0 END) as passed_count,
                    SUM(CASE WHEN ea.passed = 0 THEN 1 ELSE 0 END) as failed_count,
                    SUM(CASE WHEN ea.is_released = 1 THEN 1 ELSE 0 END) as released_count
                    FROM exams e
                    JOIN exam_assignments ea ON e.exam_id = ea.exam_id
                    WHERE ea.completion_status = 'completed'
                    GROUP BY e.exam_id";

$completed_result = $conn->query($completed_query);

if ($completed_result && $completed_result->num_rows > 0) {
    echo "<p><strong>✅ Found completed exams</strong></p>";
    echo "<table>";
    echo "<tr><th>Exam ID</th><th>Title</th><th>Total</th><th>Passed</th><th>Failed</th><th>Released</th></tr>";
    while ($row = $completed_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['exam_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . $row['total_submissions'] . "</td>";
        echo "<td>" . $row['passed_count'] . "</td>";
        echo "<td>" . $row['failed_count'] . "</td>";
        echo "<td>" . $row['released_count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p><strong>⚠️ No completed exams found</strong></p>";
    echo "<p>This might be why you can't release results - there are no completed exams to release.</p>";
}
echo "</div>";

// 5. Test the actual release query
if (isset($_GET['test_exam_id'])) {
    $test_exam_id = intval($_GET['test_exam_id']);
    echo "<div class='debug-section info'>";
    echo "<h3>5. Test Release Query for Exam ID: $test_exam_id</h3>";
    
    // Test passed students query
    $test_passed_query = "SELECT COUNT(*) as count FROM exam_assignments WHERE exam_id = ? AND passed = 1 AND completion_status = 'completed'";
    $stmt = $conn->prepare($test_passed_query);
    $stmt->bind_param("i", $test_exam_id);
    $stmt->execute();
    $passed_result = $stmt->get_result()->fetch_assoc();
    
    // Test failed students query
    $test_failed_query = "SELECT COUNT(*) as count FROM exam_assignments WHERE exam_id = ? AND passed = 0 AND completion_status = 'completed'";
    $stmt = $conn->prepare($test_failed_query);
    $stmt->bind_param("i", $test_exam_id);
    $stmt->execute();
    $failed_result = $stmt->get_result()->fetch_assoc();
    
    echo "<p><strong>Passed students:</strong> " . $passed_result['count'] . "</p>";
    echo "<p><strong>Failed students:</strong> " . $failed_result['count'] . "</p>";
    
    if ($passed_result['count'] > 0 || $failed_result['count'] > 0) {
        echo "<p><strong>✅ Students found for release</strong></p>";
    } else {
        echo "<p><strong>❌ No students found for this exam</strong></p>";
    }
    echo "</div>";
}

// 6. Provide test links
echo "<div class='debug-section info'>";
echo "<h3>6. Test Actions</h3>";
if ($completed_result && $completed_result->num_rows > 0) {
    $completed_result = $conn->query($completed_query); // Re-run query
    echo "<p>Click to test release queries for specific exams:</p>";
    while ($row = $completed_result->fetch_assoc()) {
        echo "<p><a href='?test_exam_id=" . $row['exam_id'] . "'>Test Exam: " . htmlspecialchars($row['title']) . "</a></p>";
    }
}
echo "</div>";

// 7. Check PHP error log
echo "<div class='debug-section info'>";
echo "<h3>7. PHP Error Reporting</h3>";
echo "<p><strong>Error Reporting:</strong> " . (error_reporting() ? 'Enabled' : 'Disabled') . "</p>";
echo "<p><strong>Display Errors:</strong> " . (ini_get('display_errors') ? 'On' : 'Off') . "</p>";
echo "<p>Check your PHP error logs for any SQL errors or other issues.</p>";
echo "</div>";
?> 