<?php
include("db.php");

echo "=== HOD dept values ===\n";
$res = $conn->query("SELECT id, fullname, department_name, college_name FROM users WHERE role='hod'");
while($r = $res->fetch_assoc()) {
    echo "HOD#{$r['id']} {$r['fullname']} DEPT='{$r['department_name']}' COL='{$r['college_name']}'\n";
}

echo "\n=== Student user dept values (for gate pass users) ===\n";
$res = $conn->query("SELECT u.id, u.fullname, u.department_name, u.college_name FROM users u JOIN gate_passes g ON u.id = g.user_id GROUP BY u.id");
while($r = $res->fetch_assoc()) {
    echo "STU#{$r['id']} {$r['fullname']} DEPT='{$r['department_name']}' COL='{$r['college_name']}'\n";
}

echo "\n=== Current gate pass dept values ===\n";
$res = $conn->query("SELECT id, student_name, department_name, college_name, status FROM gate_passes ORDER BY id DESC LIMIT 10");
while($r = $res->fetch_assoc()) {
    echo "GP#{$r['id']} {$r['student_name']} DEPT='{$r['department_name']}' COL='{$r['college_name']}' STATUS={$r['status']}\n";
}
?>
