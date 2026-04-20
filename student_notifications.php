<?php
session_start();

// Prevent browser caching for security
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
include("db.php");

// Protect page
if(!isset($_SESSION['username']) || $_SESSION['role'] != "student"){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get Students Notifications
$query = "SELECT n.*, u.fullname as sender_name 
          FROM notifications n 
          LEFT JOIN users u ON n.user_id = u.id 
          WHERE n.student_id = ? 
          ORDER BY n.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications - NIT Hostel</title>
    <link rel="stylesheet" href="student.css">
    <style>
        .notification-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            border-left: 5px solid #1a73e8;
        }
        .notif-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
            font-size: 0.9em;
        }
        .notif-type {
            font-weight: bold;
            color: #1a73e8;
            text-transform: uppercase;
        }
        .notif-message {
            font-size: 1.1em;
            color: #333;
            line-height: 1.4;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            color: #666;
        }
    </style>
    <link rel="stylesheet" href="responsive.css">
<script>
    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>
</head>
<body>

    <header>
        <img src="nit_logo.png.jpg" alt="NIT Logo">
        Welcome, <?php echo $_SESSION['username']; ?> 👋
    </header>

    <div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar">
            <h2>Modules</h2>
            <a href="student.php">Dashboard Home</a>
            <a href="student_profile.php">View Profile</a>
            <a href="student_attendance.php">View Attendance Record</a>
            <a href="student_gatepass.php">Gate Pass Request</a>
            <a href="student_complaints.php">Register Complaint</a>
            <a href="student_mess_fee.php">View Mess Fee Record</a>
            <a href="student_notifications.php" style="background: #e8f0fe;">Notifications</a>
            <a href="student_rules.php">Hostel Rules 📋</a>
            <a href="student_reports.php">View My Reports</a>
            <a href="student_transport.php">Transport Assistance</a>
            <a href="student_bharosa.php">Bharosa Cell Support</a>
        </div>

        <div class="content">
            <h1>Notifications</h1>
            <p>Recent updates regarding your hostel status.</p>

            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="notification-card">
                        <div class="notif-header">
                            <span class="notif-type"><?php echo htmlspecialchars($row['type']); ?></span>
                            <span class="notif-date"><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></span>
                        </div>
                        <div class="notif-message">
                            <?php echo htmlspecialchars($row['message']); ?>
                        </div>
                        <div style="margin-top: 10px; font-size: 0.85em; color: #888;">
                            From: <?php echo htmlspecialchars($row['sender_name'] ?? 'System/Admin'); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash" style="font-size: 3em; margin-bottom: 10px; opacity: 0.5;"></i>
                    <p>No notifications yet.</p>
                </div>
            <?php endif; ?>

        </div>

    </div>

<button class="menu-toggle" onclick="toggleSidebar()">☰</button>
<script>
    function toggleSidebar() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('active');
        document.body.classList.toggle('no-scroll');
    }
    function closeSidebar() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.classList.remove('no-scroll');
    }
    document.querySelectorAll('.sidebar a').forEach(function(link) {
        link.addEventListener('click', closeSidebar);
    });
    var overlay = document.getElementById('sidebarOverlay');
    if (overlay) overlay.addEventListener('click', closeSidebar);
    </script>

</body>
</html>
