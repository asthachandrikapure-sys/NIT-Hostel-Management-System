<?php
include("db.php");

$query = "CREATE TABLE IF NOT EXISTS transport_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    vehicle_type VARCHAR(50) DEFAULT 'Auto Rickshaw',
    location VARCHAR(100) DEFAULT 'Campus Gate',
    availability_status ENUM('Available', 'Not Available') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($query) === TRUE) {
    echo "Table transport_contacts created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
