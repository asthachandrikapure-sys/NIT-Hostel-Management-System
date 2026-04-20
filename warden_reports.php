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
if(!isset($_SESSION['username']) || $_SESSION['role'] != "warden"){
    header("Location: login.php");
    exit();
}

// Monthly Attendance Overview (All Students)
$att_query = "SELECT DATE_FORMAT(date, '%M %Y') as month, 
                     COUNT(DISTINCT user_id) as total_students,
                     SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as presents,
                     SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absents
              FROM attendance 
              GROUP BY month 
              ORDER BY MAX(date) DESC";
$att_result = $conn->query($att_query);

// Pending Mess Fees (All Students)
$pending_query = "SELECT u.fullname, u.email, m.month_year, m.amount, s.parent_mobile 
                  FROM mess_fees m 
                  JOIN users u ON m.user_id = u.id 
                  LEFT JOIN students_info s ON u.id = s.user_id 
                  WHERE m.status = 'Pending' 
                  ORDER BY m.id DESC";
$pending_result = $conn->query($pending_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warden Reports - NIT Hostel</title>
    <link rel="stylesheet" href="warden.css">
    <style>
        .report-section { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .report-section h2 { border-bottom: 2px solid #d84315; padding-bottom: 10px; margin-bottom: 20px; color: #d84315; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        .stat-p { color: #2e7d32; font-weight: bold; }
        .stat-a { color: #c62828; font-weight: bold; }

        .btn-download {
            background: #d84315;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            margin-bottom: 15px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        .btn-download:hover { background: #bf360c; }
        
        @media print {
            .sidebar, header, .menu-toggle, .no-print, .btn-download { display: none !important; }
            .dashboard { display: block !important; }
            .content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .report-section { box-shadow: none !important; border: 1px solid #eee !important; page-break-inside: avoid; }
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
            <a href="warden.php">Dashboard Home</a>
            <a href="warden_student_info.php">Update Student Info</a>
            <a href="warden_mess_fee.php">Update Mess Fee Record</a>
            <a href="warden_gatepass.php">Approve/Reject Gate Pass</a>
            <a href="warden_attendance.php">Mark Attendance</a>
            <a href="warden_complaints.php">Manage Complaints</a>
            <a href="warden_notifications.php">My Notifications</a>
            <a href="warden_reports.php" style="background: #fbe9e7;">Monthly Reports</a>
            <a href="warden_profile.php">Warden Profile</a>
            <a href="warden_duties.php">My Duties</a>
            <a href="warden_transport.php">Transport Assistance</a>
        </div>

        <div class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h1>Hostel Monthly Statistics</h1>
                <div class="no-print">
                    <button onclick="window.print()" class="btn-download">🖨️ Print Report</button>
                </div>
            </div>
            
            <div class="report-section" style="overflow-x: auto;">
                <h2>Attendance Overview (All Students)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total Students Captured</th>
                            <th>Total Presents</th>
                            <th>Total Absents</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($att_result->num_rows > 0): ?>
                            <?php while($row = $att_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $row['month']; ?></strong></td>
                                    <td><?php echo $row['total_students']; ?></td>
                                    <td class="stat-p"><?php echo $row['presents']; ?></td>
                                    <td class="stat-a"><?php echo $row['absents']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No attendance summary available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section" style="overflow-x: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Pending Mess Fees List</h2>
                    <?php
                        $pending_data = [];
                        $pending_result->data_seek(0);
                        while($r = $pending_result->fetch_assoc()) $pending_data[] = $r;
                    ?>
                    <form action="export_csv.php" method="POST" class="no-print">
                        <input type="hidden" name="filename" value="Pending_Mess_Fees">
                        <input type="hidden" name="data_json" value='<?php echo json_encode($pending_data); ?>'>
                        <button type="submit" class="btn-download" style="background: #0b7a3f;">📊 Export CSV</button>
                    </form>
                </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Month</th>
                            <th>Amount</th>
                            <th>Parent Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pending_result->num_rows > 0): ?>
                            <?php while($row = $pending_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['month_year']); ?></td>
                                    <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['parent_mobile'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">All mess fees are clear!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div>

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
