<?php
require_once('fpdf/fpdf.php');
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

// Format the selected student types to show in the header
$typeString = '';
if (in_array('shiftee', $types)) {
    $typeString .= 'Shiftees, ';
}
if (in_array('transferee', $types)) {
    $typeString .= 'Transferees, ';
}
if (in_array('ladderized', $types)) {
    $typeString .= 'Ladderized, ';
}
if (in_array('all', $types) || empty($typeString)) {
    $typeString .= 'All Student Types';
} else {
    // Remove the trailing comma and space
    $typeString = rtrim($typeString,  ',');
}

// Create new PDF instance
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->Image('assets/images/PUPLogo.png', 10, 10, 20);
$pdf->Image('assets/images/CCISLogo.png', 267, 10,20);
$pdf->SetFont('Arial', 'B', 20);
$pdf->Cell(0, 20, 'Qualified Students List', 0, 1, 'C');
$pdf->SetFont('Arial', 'I', 12);
$pdf->Cell(0, 14, "$typeString", 0, 1, 'C'); // Display selected student types in the header

$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 12);
// Set the table headers
$pdf->Cell(40, 10, 'Reference ID', 1, 0, 'C');
$pdf->Cell(65, 10, 'Name', 1, 0, 'C');
$pdf->Cell(40, 10, 'Student Type', 1, 0, 'C');
$pdf->Cell(92, 10, 'Email', 1, 0, 'C');
$pdf->Cell(40, 10, 'Registration Date', 1, 1, 'C');

// Fetch the data with the modified WHERE clause
$query = "SELECT reference_id, first_name, last_name, student_type, email, registration_date FROM register_studentsqe $whereClause ORDER BY registration_date DESC";
$result = $conn->query($query);

$pdf->SetFont('Arial', 'B', 10);
// Output the data in PDF
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(40, 10, $row['reference_id'], 1, 0, 'C');
    $pdf->Cell(65, 10, $row['first_name'] . ' ' . $row['last_name'], 1, 0, 'C');
    $pdf->Cell(40, 10, $row['student_type'], 1, 0, 'C');
    $pdf->Cell(92, 10, $row['email'], 1, 0, 'C');
    $pdf->Cell(40, 10, date('M d, Y', strtotime($row['registration_date'])), 1, 1, 'C');
}

$pdf->Output('qualified_students.pdf', 'D');
exit();
?>
