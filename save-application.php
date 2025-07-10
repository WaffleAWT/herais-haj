<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

try {
    // Basic validation
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['folder']) || !isset($_FILES['pdfFile'])) {
        throw new Exception('Missing required data');
    }

    // Get and validate folder
    $folder = strtolower(trim($_POST['folder']));
    if (!in_array($folder, ['visa', 'hajj', 'umrah'])) {
        throw new Exception('Invalid folder type: ' . $folder);
    }

    // Set up paths - try multiple possible locations
    $possiblePaths = [
        __DIR__ . '/applications',
        dirname(__DIR__) . '/applications',
        'applications'
    ];

    $baseDir = null;
    foreach ($possiblePaths as $path) {
        if (is_dir($path) || mkdir($path, 0777, true)) {
            $baseDir = $path;
            break;
        }
    }

    if (!$baseDir) {
        throw new Exception('Could not create or find applications directory');
    }

    // Create subfolder
    $folderPath = $baseDir . '/' . $folder;
    if (!is_dir($folderPath) && !mkdir($folderPath, 0777, true)) {
        throw new Exception('Failed to create subfolder: ' . $folder);
    }

    // Handle file upload
    $file = $_FILES['pdfFile'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed with error code: ' . $file['error']);
    }

    $fileName = basename($file['name']);
    $targetPath = $folderPath . '/' . $fileName;

    // Ensure it's a PDF
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($fileType !== 'pdf') {
        throw new Exception('Invalid file type. Only PDF files are allowed.');
    }

    // Try to move the file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        // If move_uploaded_file fails, try direct file writing
        if (isset($_POST['pdfData'])) {
            $pdfData = $_POST['pdfData'];
            // Remove data URI prefix if present
            if (strpos($pdfData, 'data:application/pdf;base64,') === 0) {
                $pdfData = substr($pdfData, 28);
            }
            if (!file_put_contents($targetPath, base64_decode($pdfData))) {
                throw new Exception('Failed to write PDF file');
            }
        } else {
            throw new Exception('Failed to save file and no backup data provided');
        }
    }

    // Set permissions
    chmod($targetPath, 0644);

    // Return success with path info
    echo json_encode([
        'success' => true,
        'message' => 'File saved successfully',
        'path' => $targetPath,
        'folder' => $folder
    ]);

} catch (Exception $e) {
    error_log('PDF Save Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 