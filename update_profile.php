<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

try {
    // Get database connection
    $conn = getDatabaseConnection();
    
    // Get form data
    $education = $_POST['education'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $achievements = $_POST['achievements'] ?? '';
    $enrolled_courses = $_POST['enrolled_courses'] ?? '';
    $user_id = $_SESSION['user_id'];

    // Prepare SQL statement
    $sql = "UPDATE signup SET 
            education = ?, 
            skills = ?, 
            achievements = ?, 
            enrolled_courses = ? 
            WHERE userid = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("ssssi", $education, $skills, $achievements, $enrolled_courses, $user_id);
    
    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        throw new Exception("Error executing statement: " . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 