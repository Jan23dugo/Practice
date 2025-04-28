<?php
include('config/config.php');

// Get selected student types from URL parameter
$types = isset($_GET['types']) ? explode(',', $_GET['types']) : ['all']; // Default to 'all' if no type selected

// Prepare the WHERE clause based on selected types
$whereClause = "WHERE status = 'accepted'";
if (in_array('shiftee', $types)) {
    $whereClause .= " AND student_type = 'shiftee'";
}
if (in_array('transferee', $types)) {
    $whereClause .= " AND student_type = 'transferee'";
}
if (in_array('ladderized', $types)) {
    $whereClause .= " AND student_type = 'ladderized'";
}

// Set the headers for the CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="qualified_students.csv"');

$output = fopen('php://output', 'w');

// Write the CSV headers
fputcsv($output, ['Reference ID', 'Name', 'Student Type', 'Email', 'Registration Date', 'Status']);

// Fetch the data with the modified WHERE clause
$query = "SELECT reference_id, first_name, last_name, student_type, email, registration_date, status FROM register_studentsqe $whereClause ORDER BY registration_date DESC";
$result = $conn->query($query);

// Write the data to CSV
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['reference_id'],
        $row['first_name'] . ' ' . $row['last_name'],
        $row['student_type'],
        $row['email'],
        date('M d, Y', strtotime($row['registration_date'])),
        $row['status']
    ]);
}

fclose($output);
exit();
?>
