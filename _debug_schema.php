<?php
include("db.php");

echo "=== GATE_PASSES TABLE ===\n";
$res = $conn->query("SHOW CREATE TABLE gate_passes");
$r = $res->fetch_assoc();
echo $r['Create Table'] . "\n\n";

echo "=== STUDENTS_INFO COLUMNS ===\n";
$res = $conn->query("SHOW COLUMNS FROM students_info");
while($r = $res->fetch_assoc()) echo $r['Field'] . ' | ' . $r['Type'] . "\n";

echo "\n=== USERS COLUMNS ===\n";
$res = $conn->query("SHOW COLUMNS FROM users");
while($r = $res->fetch_assoc()) echo $r['Field'] . ' | ' . $r['Type'] . "\n";

echo "\n=== SAMPLE GATE PASSES (last 5) ===\n";
$res = $conn->query("SELECT id, user_id, student_name, college_name, department_name, status FROM gate_passes ORDER BY id DESC LIMIT 5");
while($r = $res->fetch_assoc()) {
    echo "ID:{$r['id']} USER:{$r['user_id']} NAME:{$r['student_name']} COLLEGE:{$r['college_name']} DEPT:{$r['department_name']} STATUS:{$r['status']}\n";
}

echo "\n=== HOD USERS ===\n";
$res = $conn->query("SELECT id, fullname, role, college_name, department_name FROM users WHERE role='hod'");
while($r = $res->fetch_assoc()) {
    echo "ID:{$r['id']} NAME:{$r['fullname']} ROLE:{$r['role']} COLLEGE:{$r['college_name']} DEPT:{$r['department_name']}\n";
}

echo "\n=== STUDENTS WITH MISSING students_info ===\n";
$res = $conn->query("SELECT u.id, u.fullname, u.college_name, u.department_name FROM users u LEFT JOIN students_info s ON u.id = s.user_id WHERE u.role='student' AND s.user_id IS NULL LIMIT 10");
while($r = $res->fetch_assoc()) {
    echo "ID:{$r['id']} NAME:{$r['fullname']} COLLEGE:{$r['college_name']} DEPT:{$r['department_name']}\n";
}

echo "\n=== PROFILE_PHOTO column in students_info? ===\n";
$res = $conn->query("SHOW COLUMNS FROM students_info LIKE 'profile_photo'");
echo ($res->num_rows > 0) ? "YES - profile_photo column exists\n" : "NO - profile_photo column MISSING\n";
?>
