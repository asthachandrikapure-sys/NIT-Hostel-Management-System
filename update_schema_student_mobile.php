<?php
include("db.php");

// Add student_mobile to students_info
$sql = "ALTER TABLE students_info ADD COLUMN student_mobile VARCHAR(20) DEFAULT NULL AFTER parent_mobile";

if ($conn->query($sql)) {
    echo "Successfully added student_mobile to students_info table.";
} else {
    echo "Error updating schema: " . $conn->error;
}
?>
