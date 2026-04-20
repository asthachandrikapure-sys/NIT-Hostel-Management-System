<?php
/**
 * Migration: Add Management role, college_type, and enhanced complaint fields
 * Safe to run multiple times - handles duplicates gracefully.
 */
$conn = new mysqli("localhost", "root", "root", "hostel_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Suppress exceptions so we can handle errors manually
mysqli_report(MYSQLI_REPORT_OFF);

$successes = [];
$errors = [];

function safe_query($conn, $sql, $desc) {
    global $errors, $successes;
    if ($conn->query($sql)) {
        $successes[] = "✅ $desc";
    } else {
        $err = $conn->error;
        if (strpos($err, 'Duplicate column') !== false || strpos($err, 'already exists') !== false) {
            $successes[] = "⚠️ $desc (already exists, skipped)";
        } else {
            $errors[] = "❌ $desc: $err";
        }
    }
}

// 1. Update users ENUM to include 'management'
safe_query($conn, "ALTER TABLE users MODIFY COLUMN role ENUM('admin','warden','student','hod','principal_poly','principal_engg','incharge_poly','incharge_engg','management') NOT NULL", "Update users role ENUM to include 'management'");

// 2. Add college_type to relevant tables
$tables = ['users', 'students_info', 'complaints', 'gate_passes', 'mess_fees', 'attendance', 'notifications'];
foreach ($tables as $t) {
    safe_query($conn, "ALTER TABLE $t ADD COLUMN college_type ENUM('poly','engineering') DEFAULT NULL", "Add college_type to $t");
}

// 3. Add enhanced complaint fields
safe_query($conn, "ALTER TABLE complaints ADD COLUMN action_taken ENUM('yes','no') DEFAULT 'no'", "Add action_taken to complaints");
safe_query($conn, "ALTER TABLE complaints ADD COLUMN handled_by VARCHAR(100) DEFAULT NULL", "Add handled_by to complaints");

// 4. Create management_alerts table
safe_query($conn, "CREATE TABLE IF NOT EXISTS management_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    alert_type ENUM('unresolved','no_action') DEFAULT 'unresolved',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE
)", "Create management_alerts table");

// 5. Backfill college_type from college_name for existing data
foreach ($tables as $t) {
    $conn->query("UPDATE $t SET college_type = 'poly' WHERE college_type IS NULL AND (college_name LIKE '%Polytechnic%' OR college_name LIKE '%poly%')");
    $conn->query("UPDATE $t SET college_type = 'engineering' WHERE college_type IS NULL AND (college_name LIKE '%Engineering%' OR college_name LIKE '%engg%')");
    $successes[] = "✅ Backfilled college_type in $t";
}

// 6. Insert a default Management user (only if not exists)
$check = $conn->query("SELECT id FROM users WHERE role='management'");
if ($check && $check->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, 'management')");
    $name = "Super Admin";
    $email = "management@nit.edu";
    $pass = "admin123";
    $stmt->bind_param("sss", $name, $email, $pass);
    if ($stmt->execute()) {
        $successes[] = "✅ Created default Management user (management@nit.edu / admin123)";
    } else {
        $errors[] = "❌ Create management user: " . $stmt->error;
    }
} else {
    $successes[] = "⚠️ Management user already exists, skipped";
}

echo "<h2>Migration Results</h2>";
echo "<h3 style='color:green;'>Successes (" . count($successes) . ")</h3>";
foreach ($successes as $s) echo "<p>$s</p>";
if (!empty($errors)) {
    echo "<h3 style='color:red;'>Errors (" . count($errors) . ")</h3>";
    foreach ($errors as $e) echo "<p>$e</p>";
} else {
    echo "<p style='color:green; font-weight:bold;'>🎉 All migrations completed successfully!</p>";
}
$conn->close();
?>
