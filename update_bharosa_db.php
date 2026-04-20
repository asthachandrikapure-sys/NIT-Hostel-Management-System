<?php
include('db.php');

// Delete all existing contacts
$conn->query("TRUNCATE TABLE bharosa_contacts");

// Insert new official contact
$stmt = $conn->prepare("INSERT INTO bharosa_contacts (name, phone, email) VALUES (?, ?, ?)");
$name = "Kalmeshwar Bharosa Cell";
$phone = "9881400408";
$email = ""; // Not provided
$stmt->bind_param("sss", $name, $phone, $email);
$stmt->execute();

echo "Database updated successfully.";
?>
