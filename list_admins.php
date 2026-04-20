<?php
include("db.php");
$res = $conn->query("SELECT id, fullname, email, role, college_name FROM users WHERE role != 'student'");
echo "ID | Name | Email | Role | College\n";
echo "---|------|-------|------|--------\n";
while($row = $res->fetch_assoc()) {
    echo "{$row['id']} | {$row['fullname']} | {$row['email']} | {$row['role']} | {$row['college_name']}\n";
}
?>
