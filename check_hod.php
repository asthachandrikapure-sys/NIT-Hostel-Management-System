<?php
include("db.php");

echo "HOD Users:\n";
$res = $conn->query("SELECT id, fullname, email, role, college_name, department_name FROM users WHERE role='hod'");
while($r = $res->fetch_assoc()) print_r($r);

echo "\nStudent Users:\n";
$res2 = $conn->query("SELECT id, fullname, email, role, college_name, department_name FROM users WHERE role='student' LIMIT 5");
while($r = $res2->fetch_assoc()) print_r($r);

echo "\nGate Passes:\n";
$res3 = $conn->query("SELECT id, user_id, student_name, college_name, department_name, status FROM gate_passes ORDER BY id DESC LIMIT 5");
while($r = $res3->fetch_assoc()) print_r($r);
