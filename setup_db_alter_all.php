<?php
$conn = mysqli_connect("localhost", "root", "root", "hostel_db");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$queries = [
    "ALTER TABLE students_info ADD COLUMN student_name VARCHAR(100) NOT NULL AFTER user_id",
    "ALTER TABLE mess_fees ADD COLUMN student_name VARCHAR(100) NOT NULL AFTER user_id",
    "ALTER TABLE complaints ADD COLUMN student_name VARCHAR(100) NOT NULL AFTER user_id"
];

foreach ($queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Table updated successfully.\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>
