<?php
$conn = new mysqli('localhost', 'root', 'root', 'hostel_db');
if($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$cols = ['course_type', 'college_type', 'parent_mobile', 'department', 'branch', 'npoly_id'];
echo "Checking students_info table...\n";
foreach($cols as $col) {
    $res = $conn->query("SHOW COLUMNS FROM students_info LIKE '$col'");
    if($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        echo "Column '$col' EXISTS as " . $row['Type'] . "\n";
    } else {
        echo "Column '$col' MISSING\n";
    }
}

echo "\nChecking gate_passes table...\n";
$res = $conn->query("SHOW COLUMNS FROM gate_passes LIKE 'remarks'");
if($res->num_rows > 0) echo "Column 'remarks' EXISTS\n";
else echo "Column 'remarks' MISSING\n";

$conn->close();
?>
