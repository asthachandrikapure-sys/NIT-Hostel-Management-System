<?php
$conn = mysqli_connect("localhost", "root", "root", "hostel_db");
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$sql = "ALTER TABLE students_info ADD COLUMN course_type ENUM('Engineering', 'Polytechnic') DEFAULT NULL";
if (mysqli_query($conn, $sql)) { echo "course_type column added."; } else { echo "Error: " . mysqli_error($conn); }
?>
