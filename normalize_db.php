<?php
$conn = new mysqli('localhost', 'root', 'root', 'hostel_db');
if($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

// 1. Create Colleges table
$conn->query("CREATE TABLE IF NOT EXISTS colleges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
)");

// 2. Create Departments table
$conn->query("CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
)");

// 3. Seed Colleges
$conn->query("INSERT IGNORE INTO colleges (id, name) VALUES (1, 'Engineering'), (2, 'Polytechnic')");

// 4. Seed Departments (Engineering - 6 depts)
$depts_engg = ['Computer Science', 'Electronic & Telecomm', 'Civil Engineering', 'Mechanical Engineering', 'Electrical Engineering', 'Information Technology'];
foreach($depts_engg as $d) {
    $conn->query("INSERT IGNORE INTO departments (college_id, name) VALUES (1, '$d')");
}

// 5. Seed Departments (Polytechnic - 5 depts)
$depts_poly = ['Computer Technology', 'Electronics & Video', 'Civil Polytechnic', 'Mechanical Polytechnic', 'Electrical Polytechnic'];
foreach($depts_poly as $d) {
    $conn->query("INSERT IGNORE INTO departments (college_id, name) VALUES (2, '$d')");
}

echo "Colleges and Departments tables created and seeded successfully.";
$conn->close();
?>
