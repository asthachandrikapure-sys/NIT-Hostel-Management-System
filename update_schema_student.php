<?php
include("db.php");

// 1. Add npoly_id to students_info
$conn->query("ALTER TABLE students_info ADD COLUMN npoly_id VARCHAR(50) UNIQUE DEFAULT NULL");

// 2. Alter status in gate_passes
$conn->query("ALTER TABLE gate_passes MODIFY COLUMN status ENUM('Pending HOD Approval', 'Pending Warden Approval', 'Approved', 'Rejected') DEFAULT 'Pending HOD Approval'");

// 3. Add late_fee_added and base_amount to mess_fees
$conn->query("ALTER TABLE mess_fees ADD COLUMN late_fee_added TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE mess_fees ADD COLUMN base_amount DECIMAL(10,2) DEFAULT 3500.00");

echo "Schema updated successfully!";
?>
