-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 19, 2026 at 11:07 AM
-- Server version: 8.0.42
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hostel_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `college_name` varchar(100) DEFAULT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Leave') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `colleges`
--

CREATE TABLE `colleges` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `colleges`
--

INSERT INTO `colleges` (`id`, `name`) VALUES
(1, 'Engineering'),
(2, 'Polytechnic');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `college_name` varchar(100) DEFAULT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Open','In Progress','Resolved') DEFAULT 'Open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `concerning` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `user_id`, `student_name`, `college_name`, `department_name`, `title`, `description`, `status`, `created_at`, `concerning`) VALUES
(15, 29, 'Budhhanki Lonare', 'Engineering', 'Civil Engineering', 'our rooms light is off from yesterday', 'From yesterday our rooms lights are goes off and still not on ....', 'In Progress', '2026-03-15 14:40:46', ''),
(16, 38, 'Pragati Ramteke', 'Polytechnic', 'Computer Engineering', 'our room lights are goes off from yesterday', 'our room lights are goes off from yesterday', 'Open', '2026-03-16 04:30:48', ''),
(17, 38, 'Pragati Ramteke', 'Polytechnic', 'Computer Engineering', 'lights goes off', 'lights goes off', 'Open', '2026-03-16 04:51:31', ''),
(18, 29, 'Budhhanki Lonare', 'Engineering College', 'Civil Engineering', 'from yesterday the food is not getting to enough students', 'from yesterday the food is not getting to enough students', 'Open', '2026-03-16 07:51:47', 'Mess Uncle'),
(19, 33, 'Suhani zatale ', 'Engineering', 'Computer Engineering', 'poor hostel management', 'poor cleanliness', 'Open', '2026-03-16 07:53:38', ''),
(20, 29, 'Budhhanki Lonare', 'Engineering College', 'Civil Engineering', 'poor food quality', 'poor food quality', 'Open', '2026-03-16 08:28:52', '');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int NOT NULL,
  `college_id` int NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `college_id`, `name`) VALUES
(1, 1, 'Computer Engineering'),
(2, 1, 'Information Technology'),
(3, 1, 'Civil Engineering'),
(4, 1, 'Electrical Engineering'),
(5, 1, 'Mechanical Engineering'),
(6, 2, 'Computer Engineering'),
(7, 2, 'Electrical Engineering'),
(8, 2, 'Civil Engineering'),
(9, 2, 'Electronics and Telecommunication (EJ)'),
(10, 2, 'Mechanical Engineering');

-- --------------------------------------------------------

--
-- Table structure for table `gate_passes`
--

CREATE TABLE `gate_passes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `college_name` varchar(100) DEFAULT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  `reason` text NOT NULL,
  `leave_date` date NOT NULL,
  `return_date` date NOT NULL,
  `remarks` text,
  `status` enum('Pending HOD Approval','Pending Admin Approval','Pending Hostel In-Charge Approval','Pending Warden Approval','Approved','Rejected') DEFAULT 'Pending HOD Approval',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `gate_passes`
--

INSERT INTO `gate_passes` (`id`, `user_id`, `student_name`, `college_name`, `department_name`, `reason`, `leave_date`, `return_date`, `remarks`, `status`, `created_at`) VALUES
(11, 43, 'Triveni Bisen', 'Polytechnic', 'Electronics and Telecommunication (EJ)', 'Going Home', '2026-03-16', '2026-03-18', 'HOD: yes i talk to her parent', 'Pending Hostel In-Charge Approval', '2026-03-15 15:11:49'),
(12, 30, 'Madhura Suresh Gajbhiye ', 'Engineering', 'Computer Engineering', 'Going Hospital', '2026-03-17', '2026-03-18', NULL, 'Pending HOD Approval', '2026-03-15 15:54:32'),
(13, 38, 'Pragati Ramteke', 'Polytechnic', 'Computer Engineering', 'I have To go hme for bringing my documents', '2026-03-18', '2026-03-20', 'i talk to her parents\nAdmin: Her HOD Has been talk to her parent', 'Approved', '2026-03-15 17:23:38'),
(14, 38, 'Pragati Ramteke', 'Polytechnic', 'Computer Engineering', 'going home', '2026-03-17', '2026-03-18', NULL, 'Pending HOD Approval', '2026-03-16 04:30:02'),
(15, 33, 'Suhani zatale ', 'Engineering', 'Computer Engineering', 'going home', '2026-03-17', '2026-03-19', NULL, 'Pending HOD Approval', '2026-03-16 08:20:11'),
(16, 49, 'Astha Chandrikapure', 'Polytechnic', 'Computer Engineering', 'going home', '2026-03-17', '2026-03-18', 'HOD: bjkhjksdb\nAdmin: tssrg', 'Approved', '2026-03-16 09:00:30');

-- --------------------------------------------------------

--
-- Table structure for table `mess_fees`
--

CREATE TABLE `mess_fees` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `college_name` varchar(100) DEFAULT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  `month_year` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('Paid','Pending','Partial') DEFAULT 'Pending',
  `payment_id` varchar(255) DEFAULT NULL,
  `receipt_id` varchar(50) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `late_fee_added` tinyint(1) DEFAULT '0',
  `base_amount` decimal(10,2) DEFAULT '3500.00',
  `exemption_reason` text,
  `exemption_status` enum('None','Pending','Approved','Rejected') DEFAULT 'None'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mess_fees`
--

INSERT INTO `mess_fees` (`id`, `user_id`, `student_name`, `college_name`, `department_name`, `month_year`, `amount`, `paid_amount`, `status`, `payment_id`, `receipt_id`, `paid_at`, `late_fee_added`, `base_amount`, `exemption_reason`, `exemption_status`) VALUES
(21, 29, 'Budhhanki Lonare', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None'),
(22, 30, 'Madhura Suresh Gajbhiye ', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None'),
(23, 31, 'Pradnya Uttam Dahake', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None'),
(24, 32, 'Pradnya Digambar Walke', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None'),
(25, 33, 'Suhani zatale ', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None'),
(26, 34, 'Asawari Moon', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None'),
(27, 35, 'Riya Ganesh Rathod ', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None'),
(28, 36, 'Aradhana Pohale  ', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None'),
(29, 37, 'Priyanka Landge ', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None'),
(30, 38, 'Pragati Ramteke', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None'),
(31, 43, 'Triveni Bisen', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None'),
(32, 47, 'Pranali vijay bhognle \r\n', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None'),
(33, 49, 'Astha Chandrikapure', NULL, NULL, 'Mar 2026', 100.00, NULL, 'Pending', NULL, NULL, NULL, 1, 0.00, NULL, 'None');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `type` enum('GatePass','Attendance','MessFee','Complaint') NOT NULL,
  `message` text,
  `sent_to` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Sent',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `student_id`, `type`, `message`, `sent_to`, `status`, `created_at`) VALUES
(17, 48, 38, 'GatePass', 'Gate pass approved for Pragati Ramteke. Leave from 2026-03-18, return by 2026-03-20.', 'Parent', 'Sent', '2026-03-15 17:36:57');

-- --------------------------------------------------------

--
-- Table structure for table `students_info`
--

CREATE TABLE `students_info` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `college_name` varchar(100) DEFAULT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  `room_no` varchar(50) DEFAULT NULL,
  `academic_year` varchar(50) DEFAULT NULL,
  `college_type` enum('Polytechnic','Engineering') DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `parent_mobile` varchar(20) DEFAULT NULL,
  `student_mobile` varchar(20) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `course_type` enum('Engineering','Polytechnic') DEFAULT NULL,
  `npoly_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students_info`
--

INSERT INTO `students_info` (`id`, `user_id`, `student_name`, `college_name`, `department_name`, `room_no`, `academic_year`, `college_type`, `department`, `parent_name`, `parent_mobile`, `student_mobile`, `profile_photo`, `course_type`, `npoly_id`) VALUES
(10, 29, 'Budhhanki Lonare', 'Engineering College', 'Civil Engineering', '103', '2nd', NULL, NULL, '', '8830851351', '9322899734', 'profile_29_1773590981.jpg', NULL, ''),
(11, 30, 'Madhura Suresh Gajbhiye ', 'Engineering', 'Computer Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 31, 'Pradnya Uttam Dahake', 'Engineering', 'Electrical Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 32, 'Pradnya Digambar Walke', 'Engineering', 'Civil Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 33, 'Suhani zatale ', 'Engineering', 'Computer Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 34, 'Asawari Moon', 'Engineering', 'Computer Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 35, 'Riya Ganesh Rathod ', 'Polytechnic', 'Computer Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 36, 'Aradhana Pohale  ', 'Polytechnic', 'Electrical Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 37, 'Priyanka Landge ', 'Polytechnic', 'Electrical Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 38, 'Pragati Ramteke', 'Polytechnic', 'Computer Engineering', NULL, NULL, NULL, NULL, NULL, NULL, '84590 42794', 'profile_38_1773589422.jpg', NULL, NULL),
(20, 43, 'Triveni Bisen', 'Polytechnic', 'Electronics and Telecommunication (EJ)', NULL, NULL, NULL, NULL, NULL, NULL, '86027 93669', 'profile_43_1773589012.jpeg', NULL, NULL),
(21, 49, 'Astha Chandrikapure', 'Polytechnic', 'Computer Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `role` enum('admin','warden','student','hod','principal_poly','principal_engg','incharge_poly','incharge_engg') NOT NULL,
  `college_name` varchar(100) DEFAULT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `role`, `college_name`, `department_name`, `created_at`) VALUES
(29, 'Budhhanki Lonare', 'buddhankilonare00@gmail.com', '@Buddhanki', 'student', 'Engineering College', 'Civil Engineering', '2026-03-16 05:15:58'),
(30, 'Madhura Suresh Gajbhiye ', 'madhuragajbhiye19@gmail.com', '@Madhura', 'student', 'Engineering', 'Computer Engineering', '2026-03-16 05:15:58'),
(31, 'Pradnya Uttam Dahake', 'pradnyadahake12@gmail.com', '@pradnya', 'student', 'Engineering', 'Electrical Engineering', '2026-03-16 05:15:58'),
(32, 'Pradnya Digambar Walke', 'walkepradnya323@gmail.com', '@Pradnyaa', 'student', 'Engineering College', 'Civil Engineering', '2026-03-16 05:15:58'),
(33, 'Suhani zatale ', 'suhanizatale25@gmail.com', '@suhani', 'student', 'Engineering', 'Computer Engineering', '2026-03-16 05:15:58'),
(34, 'Asawari Moon', 'asawarimoon507@gmail.com', '@asawari', 'student', 'Engineering', 'Computer Engineering', '2026-03-16 05:15:58'),
(35, 'Riya Ganesh Rathod ', 'riyarathod749896@gmail.com', '@riyaa', 'student', 'Polytechnic', 'Computer Engineering', '2026-03-16 05:15:58'),
(36, 'Aradhana Pohale  ', 'pohalea@gmail.com', '@Aradhana', 'student', 'Polytechnic', 'Electrical Engineering', '2026-03-16 05:15:58'),
(37, 'Priyanka Landge ', 'priyankarajeshlandge@gmail.com', '@priyanka', 'student', 'Polytechnic', 'Electrical Engineering', '2026-03-16 05:15:58'),
(38, 'Pragati Ramteke', 'ramtekeruchi991@gmail.com', '@pragati', 'student', 'Polytechnic', 'Computer Engineering', '2026-03-16 05:15:58'),
(39, 'Vidya Umrey', 'vidyaumrey@gmail.com', '12345', 'admin', 'Polytechnic', NULL, '2026-03-16 05:15:58'),
(40, 'Satyajit Deshmukh', 'satyajitdeshmukh@gmail.com', '12345', 'hod', 'Polytechnic', 'Computer Engineering', '2026-03-16 05:15:58'),
(41, 'Payal Suramwar', 'payalsuramwar@gmail.com', '12345', 'hod', 'Polytechnic', 'Electrical Engineering', '2026-03-16 05:15:58'),
(42, 'Reeta Pawre', 'reetapawre@gmail.com', '12345', 'hod', 'Polytechnic', 'Electronics and Telecommunication (EJ)', '2026-03-16 05:15:58'),
(43, 'Triveni Bisen', 'thbisen2006@gmail.com', '@triveni', 'student', 'Polytechnic', 'Electronics and Telecommunication (EJ)', '2026-03-16 05:15:58'),
(44, 'Divya Lande', 'divyalande@gmail.com', '12345', 'admin', 'Engineering', NULL, '2026-03-16 05:15:58'),
(46, 'Srikant Zade', 'srikant@gmail.com', '12345', 'hod', 'Engineering', 'Computer Engineering', '2026-03-16 05:15:58'),
(47, 'Pranali vijay bhognle \r\n', 'pranalibhognle@gmail.com \r\n', '@pranali', 'student', 'Engineering', 'Civil Engineering', '2026-03-16 05:15:58'),
(48, 'Pratibha Bamnote', 'pratibhabamnote@gmail.com', '12345', 'warden', NULL, NULL, '2026-03-16 05:15:58'),
(49, 'Astha Chandrikapure', 'asthachandrikapure@gmail.com', '2006', 'student', 'Polytechnic', 'Computer Engineering', '2026-03-16 08:43:00');

-- --------------------------------------------------------

--
-- Table structure for table `wardens_info`
--

CREATE TABLE `wardens_info` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `hostel_name` varchar(100) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warden_duties`
--

CREATE TABLE `warden_duties` (
  `id` int NOT NULL,
  `warden_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `gate_passes`
--
ALTER TABLE `gate_passes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `mess_fees`
--
ALTER TABLE `mess_fees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_id` (`receipt_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students_info`
--
ALTER TABLE `students_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wardens_info`
--
ALTER TABLE `wardens_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `warden_duties`
--
ALTER TABLE `warden_duties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warden_id` (`warden_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `colleges`
--
ALTER TABLE `colleges`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `gate_passes`
--
ALTER TABLE `gate_passes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `mess_fees`
--
ALTER TABLE `mess_fees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `students_info`
--
ALTER TABLE `students_info`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `wardens_info`
--
ALTER TABLE `wardens_info`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warden_duties`
--
ALTER TABLE `warden_duties`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gate_passes`
--
ALTER TABLE `gate_passes`
  ADD CONSTRAINT `gate_passes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mess_fees`
--
ALTER TABLE `mess_fees`
  ADD CONSTRAINT `mess_fees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students_info`
--
ALTER TABLE `students_info`
  ADD CONSTRAINT `students_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wardens_info`
--
ALTER TABLE `wardens_info`
  ADD CONSTRAINT `wardens_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warden_duties`
--
ALTER TABLE `warden_duties`
  ADD CONSTRAINT `warden_duties_ibfk_1` FOREIGN KEY (`warden_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
