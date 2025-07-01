<?php
// Connect to the database
$link = mysqli_connect("localhost", "root", "", "database1");
if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the form is submitted
if (isset($_POST['roll'])) {
    // Get the roll number from the form
    $roll = mysqli_real_escape_string($link, $_POST['roll']);

    // Fetch the file path from the database for the given roll number
    $sql = "SELECT file FROM student WHERE roll = '$roll'";
    $result = mysqli_query($link, $sql);

    if (mysqli_num_rows($result) > 0) {
        // Fetch the file path
        $row = mysqli_fetch_assoc($result);
        $filePath = $row['file'];

        if (!empty($filePath) && file_exists($filePath)) {
            // Get file extension to determine how to display the file
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            // Display the file based on its type (e.g., PDF, image, audio, video)
//             switch ($fileExtension) {
//                 case 'pdf':
//                     echo "<embed src='$filePath' type='application/pdf' width='100%' height='600px' />";
//                     break;
//                 case 'jpg':
//                 case 'jpeg':
//                 case 'png':
//                     echo "<img src='$filePath' alt='Image' style='max-width: 200; height: 150;' />";
//                     break;
//                 case 'mp3':
//                     echo "<audio controls><source src='$filePath' type='audio/mpeg'>Your browser does not support the audio element.</audio>";
//                     break;
//                 case 'mp4':
//                     echo "<video width='400' height='300' controls><source src='$filePath' type='video/mp4'>Your browser does not support the video tag.</video>";
//                     break;
//                 default:
//                     echo "<a href='$filePath' download>Download File </a>";
//                     break;
//             }
//         } else {
//             echo "No file found for Roll Number: $roll";
//         }
//     } else {
//         echo "No record found for Roll Number: $roll";
//     }
// } 

// Close the database connection
mysqli_close($link);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display File by Roll Number</title>
</head>
<body>
    <h2>Enter Roll Number to Display the Uploaded File</h2>
    <form method="POST">
        <label for="roll">Enter Roll Number:</label>
        <input type="number" name="roll" id="roll" required>
        <input type="submit" value="View File">

        <img src="" <?php echo fileExtension ?> alt="Profile pic">
        
    </form>
</body>
</html>
