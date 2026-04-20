<?php
include("db.php");

// Fix 1: Trim whitespace (including \r\n) from all gate_passes department/college values
$conn->query("UPDATE gate_passes SET department_name = TRIM(REPLACE(REPLACE(department_name, CHAR(13), ''), CHAR(10), '')), college_name = TRIM(REPLACE(REPLACE(college_name, CHAR(13), ''), CHAR(10), ''))");
echo "Trimmed whitespace from gate_passes: " . $conn->affected_rows . " rows affected.\n";

// Fix 2: Sync gate_passes dept/college with users table values
$res = $conn->query("SELECT g.id, g.department_name as g_dept, g.college_name as g_college, u.department_name as u_dept, u.college_name as u_college 
                      FROM gate_passes g 
                      JOIN users u ON g.user_id = u.id 
                      WHERE TRIM(g.department_name) != TRIM(u.department_name) OR TRIM(g.college_name) != TRIM(u.college_name)");
echo "\nMismatched records found: " . $res->num_rows . "\n";
while($r = $res->fetch_assoc()) {
    echo "  GP#{$r['id']}: GP_DEPT='{$r['g_dept']}' vs USER_DEPT='{$r['u_dept']}' | GP_COL='{$r['g_college']}' vs USER_COL='{$r['u_college']}'\n";
    // Fix them
    $conn->query("UPDATE gate_passes SET department_name='" . $conn->real_escape_string(trim($r['u_dept'])) . "', college_name='" . $conn->real_escape_string(trim($r['u_college'])) . "' WHERE id=" . $r['id']);
}

echo "\nDone. All gate pass records now match users table.\n";

// Verify
echo "\n=== VERIFICATION ===\n";
$res = $conn->query("SELECT g.id, g.student_name, g.department_name, g.college_name, g.status FROM gate_passes g ORDER BY id DESC LIMIT 5");
while($r = $res->fetch_assoc()) {
    echo "GP#{$r['id']} {$r['student_name']} DEPT='{$r['department_name']}' COL='{$r['college_name']}' STATUS={$r['status']}\n";
}
?>
