<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function test_dir($path) {
    echo "Testing $path:\n";
    
    // Test directory creation
    $testDir = $path . '/test_' . time();
    $mkdirResult = mkdir($testDir, 0777, true);
    echo "Create directory: " . ($mkdirResult ? "Success" : "Failed") . "\n";
    
    if ($mkdirResult) {
        // Test file creation
        $testFile = $testDir . '/test.txt';
        $writeResult = file_put_contents($testFile, 'test');
        echo "Create file: " . ($writeResult !== false ? "Success" : "Failed") . "\n";
        
        // Clean up
        if (file_exists($testFile)) {
            unlink($testFile);
        }
        if (file_exists($testDir)) {
            rmdir($testDir);
        }
    }
    
    echo "Current permissions: " . substr(sprintf('%o', fileperms($path)), -4) . "\n";
    echo "PHP user: " . get_current_user() . "\n\n";
}

// Test main applications directory
test_dir(__DIR__ . '/applications');

// Test each subdirectory
foreach (['hajj', 'umrah', 'visa'] as $type) {
    test_dir(__DIR__ . '/applications/' . $type);
}
?> 