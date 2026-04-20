<?php
include("db.php");
$sql = "ALTER TABLE students_info DROP COLUMN branch";
if ($conn->query($sql) === TRUE) {
    echo "Column branch dropped successfully";
} else {
    echo "Error dropping column: " . $conn->error;
}
$conn->close();
?>
