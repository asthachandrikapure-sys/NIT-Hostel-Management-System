<?php
$files = [
    'admin_attendance.php',
    'admin_reports.php',
    'warden_reports.php',
    'warden_mess_fee.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // 1. Rename college_type to college_name
    $content = str_replace(['college_type', 's.college_type', 'u.college_type'], ['college_name', 's.college_name', 'u.college_name'], $content);
    
    // 2. Rename department to department_name
    $content = str_replace(['department', 's.department', 'u.department'], ['department_name', 's.department_name', 'u.department_name'], $content);
    
    // 3. Fix admin_attendance.php insertion logic if present
    if ($file == 'admin_attendance.php') {
        $old_insert = '$stmt = $conn->prepare("INSERT INTO attendance (user_id, student_name, date, status) VALUES (?, ?, ?, ?)");';
        $new_insert = '// Standardized insertion logic by Antigravity';
        if (strpos($content, $old_insert) !== false) {
             // We need more context for a safe replacement here, but since I've seen the file, I'll do a more targeted one.
             $content = preg_replace(
                 '/if\(isset\(\$_POST\[\'status\'\]\) && is_array\(\$_POST\[\'status\'\]\)\) \{.*?\$stmt = \$conn->prepare\("INSERT INTO attendance \(user_id, student_name, date, status\) VALUES \(\?, \?, \?, \?\)"\);.*?foreach\(\$_POST\[\'status\'\] as \$user_id => \$status\) \{.*?\$student_name = \$_POST\[\'student_name\'\]\[\$user_id\];.*?\$stmt->bind_param\("isss", \$user_id, \$student_name, \$att_date, \$status\);.*?\$stmt->execute\(\);.*?\}/s',
                 'if(isset($_POST[\'status\']) && is_array($_POST[\'status\'])) {
            foreach($_POST[\'status\'] as $user_id => $status) {
                $student_name = $_POST[\'student_name\'][$user_id]; 
                $user_res = $conn->query("SELECT college_name, department_name FROM users WHERE id = $user_id");
                $user_row = $user_res->fetch_assoc();
                $college = $user_row[\'college_name\'] ?? null;
                $dept = $user_row[\'department_name\'] ?? null;

                $stmt = $conn->prepare("INSERT INTO attendance (user_id, student_name, college_name, department_name, date, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $user_id, $student_name, $college, $dept, $att_date, $status);
                $stmt->execute();
            }
        }',
                 $content
             );
        }
    }
    
    file_put_contents($file, $content);
    echo "Processed $file\n";
}
?>
