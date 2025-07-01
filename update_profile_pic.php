<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

// Database connection function
function getDatabaseConnection() {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'social_media_db';

    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'Database connection failed']));
    }
    
    return $conn;
}

// Function to validate image
function validateImage($file) {
    $errors = [];
    
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Error uploading file. Code: " . $file['error'];
        return $errors;
    }

    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = "File is too large. Maximum size is 5MB.";
    }

    // Check if image file is a actual image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        $errors[] = "File is not an image.";
    }

    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        $errors[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    }

    return $errors;
}

// Main process
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_FILES['profile_pic'])) {
        throw new Exception('No file uploaded');
    }

    $user_id = $_SESSION['user_id'];
    $file = $_FILES['profile_pic'];
    
    // Validate image
    $errors = validateImage($file);
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(" ", $errors)]);
        exit();
    }

    // Create upload directory if it doesn't exist
    $target_dir = "profile_pics/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Generate unique filename
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid('profile_') . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Get database connection
    $conn = getDatabaseConnection();

    // Get current profile picture
    $stmt = $conn->prepare("SELECT profile_pic FROM signup WHERE userid = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Delete old profile picture if it exists and is not the default
    if ($user && $user['profile_pic'] && $user['profile_pic'] !== 'profile_pics/default.jpg') {
        if (file_exists($user['profile_pic'])) {
            unlink($user['profile_pic']);
        }
    }

    // Upload new file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Update database
        $stmt = $conn->prepare("UPDATE signup SET profile_pic = ? WHERE userid = ?");
        $stmt->bind_param("si", $target_file, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'profile_pic' => $target_file
            ]);
        } else {
            // If database update fails, delete the uploaded file
            unlink($target_file);
            throw new Exception('Failed to update database');
        }
    } else {
        throw new Exception('Failed to upload file');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 