<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("db.php");

echo "<h3>Standardizing Database Columns...</h3>";

// 1. Rename columns in users table if they exist with old names
$conn->query("ALTER TABLE users CHANGE COLUMN college_type college_name VARCHAR(100)");
$conn->query("ALTER TABLE users CHANGE COLUMN department department_name VARCHAR(100)");

$tables = ['students_info', 'mess_fees', 'gate_passes', 'complaints', 'attendance', 'wardens_info', 'notifications'];

foreach($tables as $table) {
    echo "Processing table: $table... ";
    
    // Check if college_name exists, if not add it
    $check_col = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'college_name'");
    if($check_col->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN college_name VARCHAR(100) AFTER student_name");
        echo "Added college_name. ";
    }
    
    // Check if department_name exists, if not add it
    $check_dept = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'department_name'");
    if($check_dept->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN department_name VARCHAR(100) AFTER college_name");
        echo "Added department_name. ";
    }
    echo "<br>";
}

// 2. Create the HOD Account
$fullname = "Chandrikapure Astha";
$email = "chandrikapure_astha@gmail.com";
$password = "astha2006";
$role = "hod";
$college_name = "Polytechnic";
$department_name = "Computer Technology"; // Mapping "Computer" to seeded name

$stmt = $conn->prepare("INSERT IGNORE INTO users (fullname, email, password, role, college_name, department_name) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $fullname, $email, $password, $role, $college_name, $department_name);

if($stmt->execute()) {
    echo "<h4>HOD Account Created Successfully: $email</h4>";
} else {
    echo "<h4>Error creating HOD account: " . $conn->error . "</h4>";
}

$conn->close();
?>
