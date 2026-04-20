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
if(!isset($_SESSION['username']) || $_SESSION['role'] != "student"){
    header("Location: login.php");
    exit();
}

$drivers = $conn->query("SELECT * FROM transport_contacts ORDER BY availability_status ASC, name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Assistance - Student Dashboard</title>
    <link rel="stylesheet" href="student.css">
    <style>
        .transport-table { width: 100%; border-collapse: collapse; margin-top: 20px; background:#fff;}
        .transport-table th, .transport-table td { padding: 14px; border: 1px solid #ddd; text-align: left; }
        .transport-table th { background: #f4f4f4; color:#333; }
        .btn-call { background: #4caf50; color: white; padding: 10px 14px; text-decoration: none; border-radius: 4px; display:inline-block; font-weight:bold; }
        .btn-call:hover { background: #45a049; }
        .status-available { color: #4caf50; font-weight: bold; padding: 4px 8px; background: rgba(76,175,80,0.1); border-radius:4px;}
        .status-unavailable { color: #f44336; font-weight: bold; padding: 4px 8px; background: rgba(244,67,54,0.1); border-radius:4px;}
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top:20px; }
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
            <a href="student_profile.php">View Profile</a>
            <a href="student_attendance.php">View Attendance Record</a>
            <a href="student_gatepass.php">Gate Pass Request</a>
            <a href="student_complaints.php">Register Complaint</a>
            <a href="student_mess_fee.php">View Mess Fee Record</a>
            <a href="student_notifications.php">Notifications</a>
            <a href="student_rules.php">Hostel Rules 📋</a>
            <a href="student_reports.php">View My Reports</a>
            <a href="student_transport.php" style="background: #e7f3ea;">Transport Assistance</a>
        </div>
        <div class="content">
            <h1>Transport Assistance</h1>
            <p style="color:#666;">Find available auto drivers for travel outside the campus.</p>

            <div class="card">
                <div style="overflow-x:auto;">
                    <table class="transport-table">
                        <tr>
                            <th>Name</th>
                            <th>Phone Number</th>
                            <th>Vehicle Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <?php if($drivers && $drivers->num_rows > 0): ?>
                            <?php while($row = $drivers->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['vehicle_type']); ?></td>
                                <td>
                                    <?php if($row['availability_status'] == 'Available'): ?>
                                        <span class="status-available">Available</span>
                                    <?php else: ?>
                                        <span class="status-unavailable">Not Available</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['availability_status'] == 'Available'): ?>
                                        <a href="tel:<?php echo htmlspecialchars($row['phone_number']); ?>" class="btn-call">📞 Call Now</a>
                                    <?php else: ?>
                                        <span style="color:#999;font-style:italic;">Currently Unavailable</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;padding:20px;">No drivers available at the moment.</td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <br><br>
            <button onclick="window.location.href='logout.php'">Logout</button>
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
