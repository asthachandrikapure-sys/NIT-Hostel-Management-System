<?php
include("db.php");

function addColumn($conn, $table, $column, $definition) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($result) == 0) {
        $query = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if (mysqli_query($conn, $query)) {
            echo "Successfully added $column to $table\n";
        } else {
            echo "Error adding $column to $table: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Column $column already exists in $table\n";
    }
}

// Update roles
$query = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'warden', 'student', 'hod', 'principal_poly', 'principal_engg', 'incharge_poly', 'incharge_engg') NOT NULL";
if (mysqli_query($conn, $query)) {
    echo "Successfully updated roles in users table\n";
} else {
    echo "Error updating roles in users table: " . mysqli_error($conn) . "\n";
}

addColumn($conn, 'users', 'college_type', "ENUM('Polytechnic', 'Engineering') DEFAULT NULL AFTER role");
addColumn($conn, 'users', 'department', "VARCHAR(100) DEFAULT NULL AFTER college_type");
addColumn($conn, 'students_info', 'college_type', "ENUM('Polytechnic', 'Engineering') DEFAULT NULL AFTER academic_year");
addColumn($conn, 'students_info', 'department', "VARCHAR(100) DEFAULT NULL AFTER college_type");
?>
