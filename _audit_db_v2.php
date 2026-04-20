<?php
include("db.php");

echo "=== DATABASE AUDIT (VERIFIED SCHEMA) ===\n";

$tables = [
    'users' => ['id', 'fullname', 'email', 'password', 'role', 'college_name', 'department_name'],
    'students_info' => ['id', 'user_id', 'room_no', 'academic_year', 'college_name', 'department_name', 'student_mobile', 'parent_mobile', 'profile_photo'],
    'complaints' => ['id', 'user_id', 'title', 'description', 'status'],
    'gate_passes' => ['id', 'user_id', 'student_name', 'college_name', 'department_name', 'status', 'remarks'],
    'mess_fees' => ['id', 'user_id', 'month_year', 'amount', 'status'],
    'attendance' => ['id', 'user_id', 'date', 'status'],
    'departments' => ['id', 'name']
];

foreach ($tables as $table => $required_cols) {
    echo "\n[TABLE: $table]\n";
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    if ($res->num_rows == 0) {
        echo "❌ MISSING TABLE\n";
        continue;
    }
    echo "✅ EXISTS\n";
    
    $cols_res = $conn->query("SHOW COLUMNS FROM `$table` ");
    $existing_cols = [];
    while ($c = $cols_res->fetch_assoc()) $existing_cols[] = $c['Field'];
    
    foreach ($required_cols as $req) {
        if (!in_array($req, $existing_cols)) {
            echo "❌ MISSING COLUMN: $req\n";
        } else {
            echo "  - $req: OK\n";
        }
    }
}

// Check role enum
echo "\n[ROLE ENUM CHECK]\n";
$res = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
$row = $res->fetch_assoc();
echo "Roles: " . $row['Type'] . "\n";

echo "\n=== AUDIT COMPLETE ===\n";
?>
