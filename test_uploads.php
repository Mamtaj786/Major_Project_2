<?php
// test_uploads.php - Test script to check uploads directory and permissions
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Uploads Directory Test</h2>";

// Check uploads directory
$uploads_dir = 'uploads/';
echo "<h3>Uploads Directory Check:</h3>";
echo "Directory path: $uploads_dir<br>";
echo "Directory exists: " . (is_dir($uploads_dir) ? 'YES' : 'NO') . "<br>";
echo "Directory is readable: " . (is_readable($uploads_dir) ? 'YES' : 'NO') . "<br>";
echo "Directory is writable: " . (is_writable($uploads_dir) ? 'YES' : 'NO') . "<br>";
echo "Directory permissions: " . substr(sprintf('%o', fileperms($uploads_dir)), -4) . "<br>";

// Check current working directory
echo "<h3>Current Working Directory:</h3>";
echo "CWD: " . getcwd() . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// List files in uploads directory
if (is_dir($uploads_dir)) {
    echo "<h3>Files in uploads directory:</h3>";
    $files = scandir($uploads_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $file_path = $uploads_dir . $file;
            echo "File: $file<br>";
            echo "  - Exists: " . (file_exists($file_path) ? 'YES' : 'NO') . "<br>";
            echo "  - Readable: " . (is_readable($file_path) ? 'YES' : 'NO') . "<br>";
            echo "  - Writable: " . (is_writable($file_path) ? 'YES' : 'NO') . "<br>";
            echo "  - Size: " . filesize($file_path) . " bytes<br>";
            echo "  - Permissions: " . substr(sprintf('%o', fileperms($file_path)), -4) . "<br><br>";
        }
    }
} else {
    echo "<p>Uploads directory does not exist!</p>";
}

// Test file deletion
echo "<h3>File Deletion Test:</h3>";
if (is_dir($uploads_dir)) {
    $files = scandir($uploads_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $file_path = $uploads_dir . $file;
            echo "Testing deletion of: $file<br>";
            
            // Try to delete the file
            if (unlink($file_path)) {
                echo "  - SUCCESS: File deleted<br>";
            } else {
                echo "  - FAILED: Could not delete file<br>";
            }
            break; // Only test one file
        }
    }
}

echo "<h3>PHP Info:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "User running PHP: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown') . "<br>";
echo "Safe Mode: " . (ini_get('safe_mode') ? 'ON' : 'OFF') . "<br>";
echo "Open Basedir: " . (ini_get('open_basedir') ?: 'Not set') . "<br>";
?> 