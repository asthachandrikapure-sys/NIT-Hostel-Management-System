<?php
include('c:/xampp/htdocs/nit_project/db.php');
echo "HODs:\n";
$res = $conn->query("SELECT id, fullname, role, college_name, department_name FROM users WHERE role='hod'");
while($r = $res->fetch_assoc()) print_r($r);

echo "Students in Polytechnic Electrical Engineering:\n";
$res = $conn->query("SELECT id, fullname, role, college_name, department_name FROM users WHERE role='student' AND department_name LIKE '%Elect%'");
while($r = $res->fetch_assoc()) print_r($r);
