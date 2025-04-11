<?php
/**
 * File Upload Handler
 * 
 * This script handles file uploads for the TOR Scanner application.
 * It validates uploaded images and stores them in the uploads directory.
 */

// Define upload directory
$uploadDir = 'uploads/';

// Create upload directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Process file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Check if file was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = isset($_FILES['image']) ? uploadErrorMessage($_FILES['image']['error']) : 'No file uploaded';
        echo json_encode(['error' => $error]);
        exit;
    }
    
    $file = $_FILES['image'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);
    
    if (!in_array($detectedType, $allowedTypes)) {
        echo json_encode(['error' => 'Invalid file type. Only image files are allowed.']);
        exit;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = uniqid('tor_') . '.' . $extension;
    $targetPath = $uploadDir . $newFilename;
    
    // Move uploaded file to target directory
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode([
            'success' => true,
            'path' => $targetPath,
            'filename' => $newFilename
        ]);
    } else {
        echo json_encode(['error' => 'Failed to save uploaded file']);
    }
    exit;
}

/**
 * Get upload error message based on error code
 * 
 * @param int $errorCode PHP file upload error code
 * @return string Human-readable error message
 */
function uploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}
?> 