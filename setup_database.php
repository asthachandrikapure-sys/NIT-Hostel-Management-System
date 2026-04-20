<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = mysqli_connect("localhost", "root", "root");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS hostel_db";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully or already exists.<br>";
} else {
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}

mysqli_select_db($conn, "hostel_db");

$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fullname VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'warden', 'student') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS students_info (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        student_name VARCHAR(100),
        room_no VARCHAR(50),
        branch VARCHAR(100),
        academic_year VARCHAR(50),
        parent_name VARCHAR(100),
        parent_mobile VARCHAR(20),
        course_type ENUM('Engineering', 'Polytechnic') DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS mess_fees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        student_name VARCHAR(100),
        month_year VARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status ENUM('Paid', 'Pending') DEFAULT 'Pending',
        payment_id VARCHAR(255) DEFAULT NULL,
        paid_at DATETIME DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS complaints (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        student_name VARCHAR(100),
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        concerning VARCHAR(255) DEFAULT NULL,
        status ENUM('Open', 'In Progress', 'Resolved') DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        student_name VARCHAR(100) NOT NULL,
        date DATE NOT NULL,
        status ENUM('Present', 'Absent', 'Leave') NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS gate_passes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        student_name VARCHAR(100) NOT NULL,
        reason TEXT NOT NULL,
        leave_date DATE NOT NULL,
        return_date DATE NOT NULL,
        status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS wardens_info (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        hostel_name VARCHAR(100),
        contact_no VARCHAR(20),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        student_id INT,
        type ENUM('GatePass', 'Attendance', 'MessFee', 'Complaint', 'StudentInfo') NOT NULL,
        message TEXT,
        sent_to VARCHAR(50),
        status VARCHAR(20) DEFAULT 'Sent',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $index => $query) {
    if (mysqli_query($conn, $query)) {
        echo "Table query ".($index+1)." executed successfully.<br>";
    } else {
        echo "Error in query ".($index+1).": " . mysqli_error($conn) . "<br>";
    }
}

echo "<br><h2>All tables initialized perfectly for NIT Project.</h2>";
echo "<a href='login.php'>Go to Login</a>";
?>
