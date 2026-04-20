-- Consolidated Database Schema for NIT Hostel Management
-- This file reflects the current production structure including all role-based and functional updates.

CREATE DATABASE IF NOT EXISTS hostel_db;
USE hostel_db;

-- 0. Colleges & Departments (Metadata)
CREATE TABLE IF NOT EXISTS colleges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 1. Users Table (Core Auth & Roles)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'warden', 'student', 'hod', 'principal_poly', 'principal_engg', 'incharge_poly', 'incharge_engg') NOT NULL,
    college_name VARCHAR(100) DEFAULT NULL,
    department_name VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Student Information
CREATE TABLE IF NOT EXISTS students_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_name VARCHAR(100),
    college_name VARCHAR(100),
    department_name VARCHAR(100),
    room_no VARCHAR(50),
    academic_year VARCHAR(50),
    parent_name VARCHAR(100),
    parent_mobile VARCHAR(20),
    student_mobile VARCHAR(20),
    npoly_id VARCHAR(50) UNIQUE DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Mess Fees Table (Updated for Flexible Payments & Issues)
CREATE TABLE IF NOT EXISTS mess_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_name VARCHAR(100),
    college_name VARCHAR(100),
    department_name VARCHAR(100),
    month_year VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL, -- The bill amount
    paid_amount DECIMAL(10,2) DEFAULT NULL, -- The actual paid amount via scanner
    status ENUM('Paid', 'Pending', 'Partial') DEFAULT 'Pending',
    payment_id VARCHAR(255) DEFAULT NULL, -- UTR / Transaction ID
    paid_at DATETIME DEFAULT NULL,
    late_fee_added TINYINT(1) DEFAULT 0,
    base_amount DECIMAL(10,2) DEFAULT 3500.00,
    exemption_status ENUM('None', 'Pending', 'Approved', 'Rejected') DEFAULT 'None',
    exemption_reason TEXT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Gate Passes Table (Updated for 3-Level Sequential Approval)
CREATE TABLE IF NOT EXISTS gate_passes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    college_name VARCHAR(100),
    department_name VARCHAR(100),
    reason TEXT NOT NULL,
    leave_date DATE NOT NULL,
    return_date DATE NOT NULL,
    status ENUM('Pending HOD Approval', 'Pending Admin Approval', 'Pending Hostel In-Charge Approval', 'Pending Warden Approval', 'Approved', 'Rejected') DEFAULT 'Pending HOD Approval',
    remarks TEXT DEFAULT NULL, -- Cumulative remarks from all approval levels
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Complaints Table
CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_name VARCHAR(100),
    college_name VARCHAR(100),
    department_name VARCHAR(100),
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    concerning VARCHAR(255) DEFAULT NULL,
    status ENUM('Open', 'In Progress', 'Resolved') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    college_name VARCHAR(100),
    department_name VARCHAR(100),
    date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Leave') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 7. Wardens Info Table
CREATE TABLE IF NOT EXISTS wardens_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hostel_name VARCHAR(100),
    college_name VARCHAR(100),
    department_name VARCHAR(100),
    contact_no VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 8. Notifications Log Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT, -- Triggered by
    student_id INT, -- Related to
    college_name VARCHAR(100),
    department_name VARCHAR(100),
    type ENUM('GatePass', 'Attendance', 'MessFee', 'Complaint', 'StudentInfo') NOT NULL,
    message TEXT,
    sent_to VARCHAR(50), -- 'Parent', 'Student'
    status VARCHAR(20) DEFAULT 'Sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
