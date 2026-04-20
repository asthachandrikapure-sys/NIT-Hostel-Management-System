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
include("check_late_fees.php");

// Protect page
if(!isset($_SESSION['username']) || $_SESSION['role'] != "admin"){
    header("Location: login.php");
    exit();
}

// Handle Record Fee safely
if(isset($_POST['record_fee'])){
    $user_data = explode("|", $_POST['user_data']);
    $user_id = $user_data[0];
    $student_name = $user_data[1];
    $month_year = trim($_POST['month_year']);
    $amount = trim($_POST['amount']);
    $status = $_POST['status'];

    $u_stmt = $conn->prepare("SELECT college_name, department_name FROM users WHERE id = ?");
    $u_stmt->bind_param("i", $user_id);
    $u_stmt->execute();
    $user_row = $u_stmt->get_result()->fetch_assoc();
    $college = $user_row['college_name'] ?? null;
    $dept = $user_row['department_name'] ?? null;

    $stmt = $conn->prepare("INSERT INTO mess_fees (user_id, student_name, college_name, department_name, month_year, amount, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssds", $user_id, $student_name, $college, $dept, $month_year, $amount, $status);
    if($stmt->execute()){
         $msg = "<p style='color:green; text-align:center;'>Fee record added successfully!</p>";
    } else {
         $msg = "<p style='color:red; text-align:center;'>Error: " . $conn->error . "</p>";
    }
}

// Handle Update Status safely
if(isset($_POST['update_status'])){
    $new_status = $_POST['new_status'];
    $fee_id = $_POST['fee_id']; // Captured missing fee_id
    if($new_status == 'Paid'){
        $stmt = $conn->prepare("UPDATE mess_fees SET status=?, paid_amount=IFNULL(paid_amount, amount), paid_at=IFNULL(paid_at, NOW()) WHERE id=?");
        $stmt->bind_param("si", $new_status, $fee_id);
    } else {
        $stmt = $conn->prepare("UPDATE mess_fees SET status=? WHERE id=?");
        $stmt->bind_param("si", $new_status, $fee_id);
    }
    
    if($stmt->execute()){
        $msg = "<p style='color:green; text-align:center;'>Status updated successfully!</p>";
    } else {
        $msg = "<p style='color:red; text-align:center;'>Error updating status: " . $conn->error . "</p>";
    }
}

// Handle Exemption Approval/Rejection
if(isset($_POST['update_exemption'])){
    $fee_id = $_POST['exemption_fee_id'];
    $new_exemp_status = $_POST['exemption_action'];
    $stmt = $conn->prepare("UPDATE mess_fees SET exemption_status=? WHERE id=?");
    $stmt->bind_param("si", $new_exemp_status, $fee_id);
    
    if($stmt->execute()){
        $msg = "<p style='color:green; text-align:center;'>Exemption status updated to $new_exemp_status!</p>";
    } else {
        $msg = "<p style='color:red; text-align:center;'>Error updating exemption: " . $conn->error . "</p>";
    }
}

// Get Students for Dropdown - filtered by college_type
$college_type_filter = $_SESSION['college_type'] ?? null;
if ($college_type_filter) {
    $students_stmt = $conn->prepare("SELECT u.id, u.fullname, u.email FROM users u LEFT JOIN students_info s ON u.id = s.user_id WHERE u.role = 'student' AND s.college_type = ?");
    $students_stmt->bind_param("s", $college_type_filter);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
} else {
    $students_result = $conn->query("SELECT id, fullname, email FROM users WHERE role = 'student'");
}

// Handle Month Filter
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : date('M Y');

// Get Fee Records (Filtered by selected month for Master List view)
$ct_fee_filter = $college_type_filter ? " AND m.college_type = ?" : "";
$fees_query = "SELECT m.id, m.user_id as student_user_id, m.student_name, m.month_year, m.amount, m.status, m.paid_at, 
               u.fullname, u.email, s.academic_year, s.college_name, s.department_name, s.parent_mobile, s.student_mobile 
               FROM mess_fees m 
               JOIN users u ON m.user_id = u.id 
               LEFT JOIN students_info s ON u.id = s.user_id 
               WHERE m.month_year = ?" . $ct_fee_filter . "
               ORDER BY u.fullname ASC";
$stmt_fees = $conn->prepare($fees_query);
if ($college_type_filter) {
    $stmt_fees->bind_param("ss", $filter_month, $college_type_filter);
} else {
    $stmt_fees->bind_param("s", $filter_month);
}
$stmt_fees->execute();
$fees_result = $stmt_fees->get_result();

// Get Pending Exemptions
$exemptions_query = "SELECT m.id, m.student_name, m.month_year, m.amount, m.exemption_reason, u.fullname 
                     FROM mess_fees m 
                     JOIN users u ON m.user_id = u.id 
                     WHERE m.exemption_status = 'Pending' 
                     ORDER BY m.id ASC";
$exemptions_result = $conn->query($exemptions_query);

// Get available months for filter
$months_res = $conn->query("SELECT month_year FROM mess_fees GROUP BY month_year ORDER BY MAX(id) DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Mess Fee Record - Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; text-align: left; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #0b7a3f; color: white; }
        .btn-submit { background: #0b7a3f; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; }
        .btn-submit:hover { background: #095d30; }
        .badge-paid { background: #4caf50; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-pending { background: #f44336; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .btn-notify { 
            background: #1a73e8; 
            color: white; 
            padding: 6px 12px; 
            border-radius: 4px; 
            border: none;
            font-size: 12px; 
            font-weight: bold;
            cursor: pointer;
            display: inline-block;
            margin-top: 5px;
        }
        .btn-notify:hover { background: #1557b0; }
        .btn-notify:disabled { background: #a0a0a0; cursor: default; }
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
            <a href="admin_mess_fee.php" style="background: #e7f3ea;">Update Mess Fee Record</a>
            <a href="admin_gatepass.php">Approve/Reject Gate Pass</a>

            <a href="admin_complaints.php">View Complaints</a>
            <a href="admin_attendance.php">View Attendance Record</a>
            <a href="admin_reports.php">Global Reports</a>
            <a href="admin_warden_duties.php">Assign Duties</a>
</div>

        <div class="content">
            <h1>Mess Fee Management</h1>
            <p>Record new mess fees and update payment statuses.</p>
            
            <?php if(isset($msg)) echo $msg; ?>

            <div class="card">
                <h3>Record New Fee</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Select Student</label>
                        <select name="user_data" class="form-control" required>
                            <option value="">-- Choose Student --</option>
                            <?php while($s = $students_result->fetch_assoc()): ?>
                                <option value="<?php echo $s['id'] . '|' . htmlspecialchars($s['fullname']); ?>"><?php echo htmlspecialchars($s['fullname']) . " (" . htmlspecialchars($s['email']) . ")"; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Month & Year (e.g. Feb 2026)</label>
                        <input type="text" name="month_year" class="form-control" placeholder="Feb 2026" required>
                    </div>
                    <div class="form-group">
                        <label>Amount Due</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="3500.00" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                    <button type="submit" name="record_fee" class="btn-submit">Record Fee</button>
                </form>
            </div>

            <?php if($exemptions_result->num_rows > 0): ?>
            <div class="card table-container" style="border-left: 5px solid #ff9800;">
                <h3 style="color:#f57c00;">Pending Extension Requests</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Month/Year</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($ex = $exemptions_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ex['fullname']); ?></strong></td>
                                <td><?php echo htmlspecialchars($ex['month_year']); ?></td>
                                <td>₹<?php echo htmlspecialchars($ex['amount']); ?></td>
                                <td><em style="color:#555;"><?php echo htmlspecialchars($ex['exemption_reason']); ?></em></td>
                                <td>
                                    <form method="POST" action="" style="display:inline-flex; gap:10px;">
                                        <input type="hidden" name="exemption_fee_id" value="<?php echo $ex['id']; ?>">
                                        <button type="submit" name="exemption_action" value="Approved" style="background:#4caf50; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">Approve</button>
                                        <button type="submit" name="exemption_action" value="Rejected" style="background:#f44336; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">Reject</button>
                                        <input type="hidden" name="update_exemption" value="1">
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="card table-container">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3 style="margin:0;">Master Fee List</h3>
                    <form method="GET" action="" style="display:flex; gap:10px; align-items:center;">
                        <label style="font-size:14px; font-weight:bold;">Filter Month:</label>
                        <select name="filter_month" class="form-control" style="width:auto; padding:5px 10px;" onchange="this.form.submit()">
                            <?php while($m = $months_res->fetch_assoc()): ?>
                                <option value="<?php echo $m['month_year']; ?>" <?php echo $filter_month == $m['month_year'] ? 'selected' : ''; ?>>
                                    <?php echo $m['month_year']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                </div>
                <table>
                    <thead>
                            <tr>
                                <th>Student</th>
                                <th>College</th>
                                <th>Year</th>
                                <th>Month/Year</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                    </thead>
                    <tbody>
                        <?php if($fees_result->num_rows > 0): ?>
                            <?php while($row = $fees_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['college_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['month_year']); ?></td>
                                    <td>₹<?php echo htmlspecialchars($row['amount']); ?></td>
                                    <td>
                                        <?php if($row['status'] != 'Paid'): ?>
                                            <?php 
                                                $msg_text = "Reminder: Mess fee for " . $row['fullname'] . " for " . $row['month_year'] . " (Amount: ₹" . $row['amount'] . ") is currently PENDING.";
                                                $phone = preg_replace('/[^0-9]/', '', $row['student_mobile'] ?? $row['parent_mobile'] ?? '');
                                                if (strlen($phone) == 10) $phone = '91' . $phone;
                                                $wa_url = "https://wa.me/" . $phone . "?text=" . urlencode($msg_text);
                                            ?>
                                            <a href="<?php echo $wa_url; ?>" target="_blank" style="display:block; font-size:10px; color:#1a73e8; text-decoration:none; margin-top:4px;">🔔 Remind Student</a>
                                        <?php endif; ?>
                                        <form method="POST" action="" style="display:inline-flex; gap:5px; margin-top:5px;">
                                            <input type="hidden" name="fee_id" value="<?php echo $row['id']; ?>">
                                            <select name="new_status" style="padding: 2px; font-size: 11px;">
                                                <option value="Pending" <?php echo $row['status']=='Pending'?'selected':''; ?>>Pending</option>
                                                <option value="Paid" <?php echo $row['status']=='Paid'?'selected':''; ?>>Paid</option>
                                            </select>
                                            <button type="submit" name="update_status" style="background:#0b7a3f; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer; font-size:11px;">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">No fee records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3>Fee Summary</h3>
                <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Month-Year</th>
                        <th>Total Students</th>
                        <th>Paid</th>
                        <th>Pending</th>
                        <th>Total Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($summary_result && $summary_result->num_rows > 0): ?>
                        <?php while($row = $summary_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $row['month_year']; ?></strong></td>
                                <td><?php echo $row['total_students']; ?></td>
                                <td style="color:green; font-weight:bold;"><?php echo $row['paid_count']; ?></td>
                                <td style="color:red; font-weight:bold;"><?php echo $row['pending_count']; ?></td>
                                <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td>
                                    <a href="?month=<?php echo urlencode($row['month_year']); ?>" style="color:#0b7a3f; font-weight:bold; text-decoration:none;">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No fee records found.</td></tr>
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
