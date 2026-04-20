<?php
include("db.php");
$sql = "ALTER TABLE students_info ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL AFTER student_mobile";
if ($conn->query($sql) === TRUE) {
    echo "Column profile_photo added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}
$conn->close();
?>
