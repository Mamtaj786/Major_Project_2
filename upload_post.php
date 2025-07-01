<?php
// upload_post.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Turn off displaying errors to avoid corrupting JSON response

// Set PHP configuration for file uploads
ini_set('upload_max_filesize', '64M');
ini_set('post_max_size', '64M');
ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');

// Function to log debug information
function logDebug($message, $data = null) {
    $logFile = 'upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        $logMessage .= ": " . print_r($data, true);
    }
    
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

// Function to send JSON response
function sendJsonResponse($data) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Output JSON data
    echo json_encode($data);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'error' => 'Not logged in']);
}

// Log upload attempt
logDebug("Upload attempt by user", $_SESSION['user_id']);

// Database connection
function getDatabaseConnection() {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'social_media_db';

    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        logDebug("Database connection failed", $conn->connect_error);
        sendJsonResponse(['success' => false, 'error' => 'Database connection failed', 'details' => $conn->connect_error]);
    }
    
    return $conn;
}

// Handle file upload
function handleFileUpload($file) {
    logDebug("Processing file upload", ['name' => $file['name'], 'type' => $file['type'], 'size' => $file['size']]);
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        
        $errorMessage = isset($uploadErrors[$file['error']]) ? 
                       $uploadErrors[$file['error']] : 
                       'Unknown upload error';
        
        logDebug("Upload error", $errorMessage);
        throw new Exception($errorMessage);
    }
    
    $uploadDir = 'uploads/';
    
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        logDebug("Creating upload directory", $uploadDir);
        if (!mkdir($uploadDir, 0777, true)) {
            logDebug("Failed to create upload directory", $uploadDir);
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        logDebug("Upload directory is not writable", $uploadDir);
        throw new Exception('Upload directory is not writable');
    }
    
    $allowedTypes = [
        'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4',
        'application/pdf'
    ];
    
    // Check file type more thoroughly
    $fileMimeType = $file['type'];
    
    // Use finfo if available for more accurate MIME detection
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if ($detectedType) {
            $fileMimeType = $detectedType;
        }
    }
    
    logDebug("File MIME type", $fileMimeType);
    
    if (!in_array($fileMimeType, $allowedTypes)) {
        logDebug("Invalid file type", $fileMimeType);
        throw new Exception('Invalid file type: ' . $fileMimeType);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($file['name']));
    $targetFile = $uploadDir . $fileName;
    
    logDebug("Attempting to move uploaded file to", $targetFile);
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        logDebug("Failed to move uploaded file", ['from' => $file['tmp_name'], 'to' => $targetFile]);
        throw new Exception('Failed to move uploaded file. Please check server permissions.');
    }
    
    logDebug("File uploaded successfully", $targetFile);
    
    return [
        'file_url' => $targetFile,
        'file_type' => $fileMimeType
    ];
}

// Start output buffering to capture any unexpected output
ob_start();

try {
    $conn = getDatabaseConnection();
    
    // Get post data
    $userId = $_SESSION['user_id'];
    $caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';
    
    // Log the received data
    logDebug("Post submission data", ['userId' => $userId, 'caption' => $caption, 'FILES' => isset($_FILES) ? count($_FILES) : 0]);
    
    if (isset($_FILES)) {
        logDebug("Files data", $_FILES);
    }
    
    // Check if files array is properly structured
    if (empty($_FILES) || !isset($_FILES['files']) || 
        !isset($_FILES['files']['name']) || !is_array($_FILES['files']['name'])) {
        logDebug("No files or invalid files structure", $_FILES);
        // Don't exit, just log and continue with text-only post
    }
    
    // Handle file upload if present
    $fileData = null;
    if (isset($_FILES['files']) && isset($_FILES['files']['name'][0]) && !empty($_FILES['files']['name'][0])) {
        logDebug("Processing file from form submission", $_FILES['files']['name'][0]);
        
        $fileData = handleFileUpload([
            'name' => $_FILES['files']['name'][0],
            'type' => $_FILES['files']['type'][0],
            'tmp_name' => $_FILES['files']['tmp_name'][0],
            'error' => $_FILES['files']['error'][0],
            'size' => $_FILES['files']['size'][0]
        ]);
    } else {
        // If no file is uploaded, still allow post with just caption
        logDebug("No file uploaded, creating text-only post");
    }
    
    // Insert post into database
    $stmt = $conn->prepare("INSERT INTO posts (user_id, caption, file_url, file_type) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        logDebug("SQL prepare failed", $conn->error);
        throw new Exception('SQL prepare failed: ' . $conn->error);
    }
    
    $fileUrl = $fileData ? $fileData['file_url'] : null;
    $fileType = $fileData ? $fileData['file_type'] : null;
    
    logDebug("Binding parameters for SQL insert", ['userId' => $userId, 'caption' => $caption, 'fileUrl' => $fileUrl, 'fileType' => $fileType]);
    
    $stmt->bind_param("isss", $userId, $caption, $fileUrl, $fileType);
    
    if (!$stmt->execute()) {
        logDebug("SQL execute failed", $stmt->error);
        throw new Exception('Failed to create post: ' . $stmt->error);
    }
    
    $postId = $stmt->insert_id;
    logDebug("Post created successfully", ['postId' => $postId]);
    
    // Clean the output buffer
    ob_end_clean();
    
    // Send success response
    sendJsonResponse(['success' => true, 'message' => 'Post created successfully', 'post_id' => $postId]);
    
} catch (Exception $e) {
    // Clean the output buffer
    ob_end_clean();
    
    logDebug("Exception occurred", $e->getMessage());
    sendJsonResponse(['success' => false, 'error' => $e->getMessage()]);
} catch (Error $e) {
    // Clean the output buffer
    ob_end_clean();
    
    logDebug("Error occurred", $e->getMessage());
    sendJsonResponse(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>