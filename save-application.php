<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Log function
function logMessage($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, 'application_log.txt');
}

try {
    logMessage('Starting application save process...');

    // Verify it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }

    // Get form data
    $type = $_POST['type'] ?? '';
    $formNumber = $_POST['formNumber'] ?? '';
    $pdfData = $_POST['pdfData'] ?? '';

    if (!$type || !$formNumber || !$pdfData) {
        throw new Exception('Missing required fields');
    }

    logMessage("Processing {$type} application #{$formNumber}");

    // Create directory structure
    $baseDir = "applications/{$type}/{$formNumber}";
    $docsDir = "{$baseDir}/documents";

    // Create directories with proper permissions
    if (!file_exists($baseDir)) {
        mkdir($baseDir, 0777, true);
        chmod($baseDir, 0777);
        }
    if (!file_exists($docsDir)) {
        mkdir($docsDir, 0777, true);
        chmod($docsDir, 0777);
    }

    logMessage("Created directories: {$baseDir} and {$docsDir}");

    // Save PDF application
    $pdfPath = "{$baseDir}/application.pdf";
    $pdfData = str_replace('data:application/pdf;base64,', '', $pdfData);
    $pdfData = base64_decode($pdfData);
    file_put_contents($pdfPath, $pdfData);
    chmod($pdfPath, 0777);

    logMessage("Saved PDF application to: {$pdfPath}");

    // Initialize saved files array
    $savedFiles = ['application.pdf'];

    // Handle file uploads
    $fileTypes = [
        'personalPhoto' => ['path' => 'personal-photo', 'required' => true],
        'passportCopy' => ['path' => 'passport-copy', 'required' => true],
        'residencyDoc' => ['path' => 'residency-document', 'required' => false]
    ];

    foreach ($fileTypes as $fileKey => $fileInfo) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fileKey];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newPath = "{$docsDir}/{$fileInfo['path']}.{$ext}";

            if (move_uploaded_file($file['tmp_name'], $newPath)) {
                chmod($newPath, 0777);
                $savedFiles[] = "documents/{$fileInfo['path']}.{$ext}";
                logMessage("Saved {$fileKey} to: {$newPath}");
            } else {
                throw new Exception("Failed to save {$fileKey}");
            }
        } elseif ($fileInfo['required']) {
            throw new Exception("Required file {$fileKey} is missing");
        }
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'applicationType' => $type,
        'applicationNumber' => $formNumber,
        'savedFiles' => $savedFiles,
        'message' => 'Application saved successfully'
    ]);

} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}