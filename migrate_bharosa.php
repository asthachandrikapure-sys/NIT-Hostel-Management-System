<?php
include("db.php");

// Create bharosa_contacts table
$conn->query("CREATE TABLE IF NOT EXISTS bharosa_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "bharosa_contacts table ready.\n";

// Insert default entries if empty
$check = $conn->query("SELECT COUNT(*) as c FROM bharosa_contacts");
$count = $check->fetch_assoc()['c'];
if ($count == 0) {
    $conn->query("INSERT INTO bharosa_contacts (name, phone, email) VALUES 
        ('Bharosa Cell Nagpur (Landline)', '0712-2233638', ''),
        ('Bharosa Cell Nagpur (Helpline)', '0712-2561222', ''),
        ('Bharosa Cell Nagpur (Mobile 1)', '8055472422', ''),
        ('Bharosa Cell Nagpur (Mobile 2)', '8055876773', ''),
        ('Police Emergency', '100', '')
    ");
    echo "Bharosa Cell contacts added.\n";
}

// Update old placeholder data if it exists
$conn->query("DELETE FROM bharosa_contacts WHERE phone = '1800-XXX-XXXX'");

echo "Done.";
?>
