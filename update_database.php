<?php
include 'config.php';

try {
    // Get database connection
    $conn = getDatabaseConnection();
    
    // Add new columns to signup table
    $sql = "ALTER TABLE signup 
            ADD COLUMN IF NOT EXISTS bio TEXT,
            ADD COLUMN IF NOT EXISTS education TEXT,
            ADD COLUMN IF NOT EXISTS skills TEXT,
            ADD COLUMN IF NOT EXISTS achievements TEXT,
            ADD COLUMN IF NOT EXISTS enrolled_courses TEXT";

    if ($conn->query($sql) === TRUE) {
        echo "Database updated successfully!";
    } else {
        throw new Exception("Error updating database: " . $conn->error);
    }
} catch(Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>