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
if(!isset($_SESSION['username']) || $_SESSION['role'] != "admin"){
    header("Location: login.php");
    exit();
}

// College type filter
$college_type = $_SESSION['college_type'] ?? null;
$ct_filter = $college_type ? " WHERE college_type = '$college_type'" : "";
$ct_and = $college_type ? " AND college_type = '$college_type'" : "";

// Monthly Collection Overview
$fee_query = "SELECT month_year, 
                     SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as collected,
                     SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as pending
              FROM mess_fees" . ($college_type ? " WHERE college_type = '$college_type'" : "") . "
              GROUP BY month_year 
              ORDER BY MAX(id) DESC";
$fee_result = $conn->query($fee_query);

// Global Attendance Rate
$att_query = "SELECT DATE_FORMAT(date, '%M %Y') as month, 
                     COUNT(*) as total_records,
                     SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as presents
              FROM attendance" . ($college_type ? " WHERE college_type = '$college_type'" : "") . "
              GROUP BY month 
              ORDER BY MAX(date) DESC";
$att_result = $conn->query($att_query);

// Detailed Student-wise Report
$student_query = "SELECT u.fullname, 
                         s.college_name,
                         s.department_name,
                         m.month_year, 
                         m.status as fee_status,
                         att.presents,
                         att.total_days
                  FROM users u
                  LEFT JOIN students_info s ON u.id = s.user_id
                  JOIN mess_fees m ON u.id = m.user_id
                  LEFT JOIN (
                      SELECT user_id, DATE_FORMAT(date, '%M %Y') as att_month,
                             SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as presents,
                             COUNT(*) as total_days
                      FROM attendance
                      GROUP BY user_id, att_month
                  ) att ON u.id = att.user_id AND m.month_year = att.att_month
                  WHERE u.role = 'student'" . ($college_type ? " AND s.college_type = '$college_type'" : "") . "
                  ORDER BY m.id DESC, u.fullname ASC";
$student_result = $conn->query($student_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin reports - NIT Hostel</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .report-section { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .report-section h2 { border-bottom: 2px solid #0b7a3f; padding-bottom: 10px; margin-bottom: 20px; color: #0b7a3f; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        .stat-p { color: #2e7d32; font-weight: bold; }
        .stat-a { color: #c62828; font-weight: bold; }

        .btn-download {
            background: #0b7a3f;
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
        .btn-download:hover { background: #095d30; }
        
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
            <a href="admin.php">Dashboard Home</a>
            <a href="admin_student_info.php">Update Student Info</a>
            <a href="admin_mess_fee.php">Update Mess Fee Record</a>
            <a href="admin_gatepass.php">Approve/Reject Gate Pass</a>

            <a href="admin_complaints.php">View Complaints</a>
            <a href="admin_attendance.php">View Attendance Record</a>

            <a href="admin_reports.php" style="background: #e7f3ea;">Global Reports</a>
            <a href="admin_warden_duties.php">Assign Duties</a>
</div>

        <div class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h1>Administrative Global Reports</h1>
                <div class="no-print">
                    <button onclick="window.print()" class="btn-download">🖨️ Print Report</button>
                </div>
            </div>

            <div class="report-section" style="overflow-x: auto;">
                <h2>Monthly Mess Fee Collection</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Collected (₹)</th>
                            <th>Pending (₹)</th>
                            <th>Recovery %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($fee_result->num_rows > 0): ?>
                            <?php while($row = $fee_result->fetch_assoc()): 
                                $total = $row['collected'] + $row['pending'];
                                $percent = ($total > 0) ? round(($row['collected'] / $total) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['month_year']); ?></strong></td>
                                    <td class="stat-p">₹<?php echo number_format($row['collected'], 2); ?></td>
                                    <td class="stat-a">₹<?php echo number_format($row['pending'], 2); ?></td>
                                    <td><?php echo $percent; ?>%</td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No financial data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section" style="overflow-x: auto;">
                <h2>Global Attendance Rate</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total Records</th>
                            <th>Presents</th>
                            <th>Avg. Monthly Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($att_result->num_rows > 0): ?>
                            <?php while($row = $att_result->fetch_assoc()): 
                                $percent = ($row['total_records'] > 0) ? round(($row['presents'] / $row['total_records']) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><strong><?php echo $row['month']; ?></strong></td>
                                    <td><?php echo $row['total_records']; ?></td>
                                    <td class="stat-p"><?php echo $row['presents']; ?></td>
                                    <td><?php echo $percent; ?>%</td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No attendance data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="report-section" style="overflow-x: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Detailed Student Status (by Month)</h2>
                    <?php
                        $det_data = [];
                        $student_result->data_seek(0);
                        while($r = $student_result->fetch_assoc()) $det_data[] = $r;
                    ?>
                    <form action="export_csv.php" method="POST" class="no-print">
                        <input type="hidden" name="filename" value="Student_Status_Report">
                        <input type="hidden" name="data_json" value='<?php echo json_encode($det_data); ?>'>
                        <button type="submit" class="btn-download" style="background: #1a73e8;">📊 Export CSV</button>
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>College</th>
                            <th>Dept</th>
                            <th>Month</th>
                            <th>Attendance %</th>
                            <th>Mess Fee Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($student_result && $student_result->num_rows > 0): ?>
                            <?php while($row = $student_result->fetch_assoc()): 
                                $att_percent = ($row['total_days'] > 0) ? round(($row['presents'] / $row['total_days']) * 100, 1) : "N/A";
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['college_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['month_year']); ?></td>
                                    <td>
                                        <span class="<?php echo ($att_percent !== "N/A" && $att_percent < 75) ? 'stat-a' : ''; ?>">
                                            <?php echo $att_percent; ?><?php echo ($att_percent !== "N/A") ? "%" : ""; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: <?php echo ($row['fee_status'] == 'Paid') ? '#2e7d32' : '#c62828'; ?>; font-weight: bold;">
                                            <?php echo htmlspecialchars($row['fee_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No detailed student data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
