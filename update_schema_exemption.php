<?php
include("db.php");

// Add exemption columns to mess_fees
$conn->query("ALTER TABLE mess_fees ADD COLUMN exemption_reason TEXT DEFAULT NULL");
$conn->query("ALTER TABLE mess_fees ADD COLUMN exemption_status ENUM('None', 'Pending', 'Approved', 'Rejected') DEFAULT 'None'");

echo "Exemption columns added successfully.";
?>
