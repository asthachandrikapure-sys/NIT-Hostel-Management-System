<?php
include('c:/xampp/htdocs/nit_project/db.php');

$tables = ['users', 'students_info', 'gate_passes', 'complaints', 'mess_fees', 'attendance', 'wardens_info'];

foreach ($tables as $table) {
    try {
        $conn->query("UPDATE $table SET department_name = TRIM(BOTH '\r' FROM TRIM(BOTH '\n' FROM TRIM(department_name))) WHERE department_name IS NOT NULL");
        $conn->query("UPDATE $table SET college_name = TRIM(BOTH '\r' FROM TRIM(BOTH '\n' FROM TRIM(college_name))) WHERE college_name IS NOT NULL");
        
        // Fix specific typso
        $conn->query("UPDATE $table SET department_name = 'Electronics and Telecommunication (EJ)' WHERE department_name LIKE 'Electronic % Telecommunication (EJ)%'");
        $conn->query("UPDATE $table SET department_name = 'Computer Engineering' WHERE department_name = 'Computer Department'");
        $conn->query("UPDATE $table SET department_name = 'Electrical Engineering' WHERE department_name LIKE 'Electrical Engineering%'");
        
        $conn->query("UPDATE $table SET college_name = 'Polytechnic' WHERE college_name = 'Polytechnic College'");
        $conn->query("UPDATE $table SET college_name = 'Engineering' WHERE college_name = 'Engineering College'");
    } catch (Exception $e) { }
}

echo "Database fields standardized!\n";
