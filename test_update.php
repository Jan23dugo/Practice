<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Update Test</h1>";

// Include database configuration
include('config/config.php');

// Test database connection
echo "<h2>Step 1: Testing Database Connection</h2>";
if ($conn->connect_error) {
    echo "<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color:green'>Connection successful!</p>";
}

// Check if the table exists
echo "<h2>Step 2: Checking Table Structure</h2>";
$table_query = "SHOW TABLES LIKE 'register_studentsqe'";
$table_result = $conn->query($table_query);

if ($table_result->num_rows == 0) {
    echo "<p style='color:red'>Table 'register_studentsqe' does not exist!</p>";
    exit;
} else {
    echo "<p style='color:green'>Table 'register_studentsqe' exists.</p>";
}

// Check table structure
$columns_query = "SHOW COLUMNS FROM register_studentsqe";
$columns_result = $conn->query($columns_query);

echo "<h3>Table Columns:</h3>";
echo "<ul>";
$has_status_column = false;
$has_reference_id_column = false;

while ($column = $columns_result->fetch_assoc()) {
    echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
    
    if (strtolower($column['Field']) == 'status') {
        $has_status_column = true;
    }
    
    if (strtolower($column['Field']) == 'reference_id') {
        $has_reference_id_column = true;
    }
}
echo "</ul>";

if (!$has_status_column) {
    echo "<p style='color:red'>The 'status' column is missing!</p>";
}

if (!$has_reference_id_column) {
    echo "<p style='color:red'>The 'reference_id' column is missing!</p>";
}

// Check if the reference ID exists
echo "<h2>Step 3: Checking Reference ID</h2>";
$reference_id = "CCIS-2025-64341"; // The reference ID that's failing
$check_query = "SELECT * FROM register_studentsqe WHERE reference_id = ?";
$check_stmt = $conn->prepare($check_query);

if (!$check_stmt) {
    echo "<p style='color:red'>Prepare statement failed: " . $conn->error . "</p>";
    exit;
}

$check_stmt->bind_param("s", $reference_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color:red'>Reference ID '$reference_id' not found in database!</p>";
} else {
    echo "<p style='color:green'>Reference ID '$reference_id' exists in database.</p>";
    
    // Show current record data
    $row = $result->fetch_assoc();
    echo "<h3>Current Record Data:</h3>";
    echo "<ul>";
    foreach ($row as $key => $value) {
        echo "<li>$key: $value</li>";
    }
    echo "</ul>";
}

$check_stmt->close();

// Test manual update
echo "<h2>Step 4: Testing Manual Update</h2>";
$status = "accepted";
$update_query = "UPDATE register_studentsqe SET status = ? WHERE reference_id = ?";
$update_stmt = $conn->prepare($update_query);

if (!$update_stmt) {
    echo "<p style='color:red'>Prepare update statement failed: " . $conn->error . "</p>";
    exit;
}

$update_stmt->bind_param("ss", $status, $reference_id);
$update_result = $update_stmt->execute();

if ($update_result) {
    echo "<p style='color:green'>Update query executed successfully.</p>";
    echo "<p>Affected rows: " . $update_stmt->affected_rows . "</p>";
    
    if ($update_stmt->affected_rows == 0) {
        echo "<p style='color:orange'>Warning: No rows were updated. This could be because:</p>";
        echo "<ul>";
        echo "<li>The record already has status = 'accepted'</li>";
        echo "<li>There might be a trigger or constraint preventing the update</li>";
        echo "</ul>";
    }
} else {
    echo "<p style='color:red'>Update failed: " . $update_stmt->error . "</p>";
}

$update_stmt->close();

// Check the record after update
echo "<h2>Step 5: Verifying Update</h2>";
$verify_query = "SELECT * FROM register_studentsqe WHERE reference_id = ?";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param("s", $reference_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();
$verify_row = $verify_result->fetch_assoc();

echo "<h3>Record After Update:</h3>";
echo "<ul>";
foreach ($verify_row as $key => $value) {
    echo "<li>$key: $value</li>";
}
echo "</ul>";

$verify_stmt->close();

// Close connection
$conn->close();
echo "<p>Test completed.</p>";
?> 