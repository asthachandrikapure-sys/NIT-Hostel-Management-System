<?php
include("db.php");

// 1. Update gate_passes status and remarks
$conn->query("ALTER TABLE gate_passes MODIFY COLUMN status ENUM('Pending HOD Approval', 'Pending Admin Approval', 'Pending Hostel In-Charge Approval', 'Pending Warden Approval', 'Approved', 'Rejected') DEFAULT 'Pending HOD Approval'");
$res = $conn->query("SHOW COLUMNS FROM gate_passes LIKE 'remarks'");
if($res->num_rows == 0) {
    $conn->query("ALTER TABLE gate_passes ADD COLUMN remarks TEXT DEFAULT NULL AFTER status");
}

// 2. Update mess_fees for flexible payments
$res = $conn->query("SHOW COLUMNS FROM mess_fees LIKE 'paid_amount'");
if($res->num_rows == 0) {
    $conn->query("ALTER TABLE mess_fees ADD COLUMN paid_amount DECIMAL(10,2) DEFAULT NULL AFTER amount");
}
$conn->query("ALTER TABLE mess_fees MODIFY COLUMN status ENUM('Paid', 'Pending', 'Partial') DEFAULT 'Pending'");
$res = $conn->query("SHOW COLUMNS FROM mess_fees LIKE 'exemption_status'");
if($res->num_rows == 0) {
    $conn->query("ALTER TABLE mess_fees ADD COLUMN exemption_status ENUM('None', 'Pending', 'Approved', 'Rejected') DEFAULT 'None'");
    $conn->query("ALTER TABLE mess_fees ADD COLUMN exemption_reason TEXT DEFAULT NULL");
}

// 3. Standardize students_info column name
$res = $conn->query("SHOW COLUMNS FROM students_info LIKE 'course_type'");
if($res->num_rows > 0) {
    $conn->query("ALTER TABLE students_info CHANGE COLUMN course_type college_type ENUM('Engineering', 'Polytechnic') DEFAULT NULL");
}

echo "Database sync completed successfully.";
?>
