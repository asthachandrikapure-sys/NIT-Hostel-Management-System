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

// Monthly Attendance Summary
$att_query = "SELECT DATE_FORMAT(date, '%M %Y') as month, 
                     SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as presents,
                     SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absents,
                     SUM(CASE WHEN status = 'Leave' THEN 1 ELSE 0 END) as leaves
              FROM attendance 
              WHERE user_id = ? 
              GROUP BY month 
              ORDER BY MAX(date) DESC";
$att_stmt = $conn->prepare($att_query);
$att_stmt->bind_param("i", $user_id);
$att_stmt->execute();
$att_result = $att_stmt->get_result();

// Mess Fee Summary
$mess_query = "SELECT month_year, amount, status, paid_at 
               FROM mess_fees 
               WHERE user_id = ? 
               ORDER BY id DESC";
$mess_stmt = $conn->prepare($mess_query);
$mess_stmt->bind_param("i", $user_id);
$mess_stmt->execute();
$mess_result = $mess_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - NIT Hostel</title>
    <link rel="stylesheet" href="student.css">
    <style>
        .report-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .report-section h2 {
            border-bottom: 2px solid #0b7a3f;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #0b7a3f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        th { background: #f8f9fa; font-weight: 600; }
        .stat-p { color: #2e7d32; font-weight: bold; }
        .stat-a { color: #c62828; font-weight: bold; }
        .stat-l { color: #ef6c00; font-weight: bold; }
        .status-paid { background: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 4px; font-size: 0.9em; }
        .status-pending { background: #ffebee; color: #c62828; padding: 4px 8px; border-radius: 4px; font-size: 0.9em; }
        
        .btn-download {
            background: #1a73e8;
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
        .btn-download:hover { background: #1557b0; }
        
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
            <a href="student.php">Dashboard Home</a>
            <a href="student_profile.php">View Profile</a>
            <a href="student_attendance.php">View Attendance Record</a>
            <a href="student_gatepass.php">Gate Pass Request</a>
            <a href="student_complaints.php">Register Complaint</a>
            <a href="student_mess_fee.php">View Mess Fee Record</a>
            <a href="student_notifications.php">Notifications</a>
            <a href="student_rules.php">Hostel Rules 📋</a>
            <a href="student_reports.php" style="background: #e7f3ea;">View My Reports</a>
        </div>

        <div class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h1>Personal Monthly Reports</h1>
                <div class="no-print">
                    <button onclick="window.print()" class="btn-download">🖨️ Print PDF</button>
                </div>
            </div>
            <p>A summarized view of your attendance and mess fee history.</p>

            <div class="report-section" style="overflow-x: auto;">
                <h2>Monthly Attendance Summary</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Presents</th>
                            <th>Absents</th>
                            <th>Leaves</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($att_result->num_rows > 0): ?>
                            <?php while($row = $att_result->fetch_assoc()): 
                                $total = $row['presents'] + $row['absents'] + $row['leaves'];
                                $percent = ($total > 0) ? round(($row['presents'] / $total) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><strong><?php echo $row['month']; ?></strong></td>
                                    <td class="stat-p"><?php echo $row['presents']; ?></td>
                                    <td class="stat-a"><?php echo $row['absents']; ?></td>
                                    <td class="stat-l"><?php echo $row['leaves']; ?></td>
                                    <td><?php echo $percent; ?>%</td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">No attendance summary available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section" style="overflow-x: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Mess Fee Summary</h2>
                    <?php
                        $mess_data = [];
                        $mess_result->data_seek(0);
                        while($r = $mess_result->fetch_assoc()) $mess_data[] = $r;
                    ?>
                    <form action="export_csv.php" method="POST" class="no-print">
                        <input type="hidden" name="filename" value="My_Mess_Fees">
                        <input type="hidden" name="data_json" value='<?php echo json_encode($mess_data); ?>'>
                        <button type="submit" class="btn-download" style="background: #0b7a3f;">📊 Export CSV</button>
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Month / Year</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($mess_result->num_rows > 0): ?>
                            <?php while($row = $mess_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['month_year']); ?></td>
                                    <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                    <td>
                                        <span class="<?php echo ($row['status'] == 'Paid') ? 'status-paid' : 'status-pending'; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['paid_at'] ? date('d-M-Y', strtotime($row['paid_at'])) : '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No mess fee records available.</td></tr>
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
