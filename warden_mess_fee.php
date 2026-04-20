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

// Handle Record Fee
if(isset($_POST['record_fee'])){
    // Get the selected user details formatted as "id|fullname"
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
         $msg = "<p style='color:red; text-align:center;'>Error adding record: " . $conn->error . "</p>";
    }
}

// Handle Update Status
if(isset($_POST['update_status'])){
    $new_status = $_POST['new_status'];
    $fee_id = $_POST['fee_id']; 

    if($new_status == 'Paid'){
        // Generate Sequential Receipt ID if not already present safely
        $ch_stmt = $conn->prepare("SELECT receipt_id FROM mess_fees WHERE id = ?");
        $ch_stmt->bind_param("i", $fee_id);
        $ch_stmt->execute();
        $row = $ch_stmt->get_result()->fetch_assoc();
        
        $rid = $row['receipt_id'] ?? null;
        if (empty($rid)) {
            $year = date('Y');
            $prefix = "NIT-MESS-$year-";
            $search = $prefix . "%";
            $res_count = $conn->prepare("SELECT MAX(receipt_id) as max_id FROM mess_fees WHERE receipt_id LIKE ?");
            $res_count->bind_param("s", $search);
            $res_count->execute();
            $max_row = $res_count->get_result()->fetch_assoc();
            
            $next_num = 1;
            if ($max_row['max_id']) {
                $last_num = intval(substr($max_row['max_id'], -3));
                $next_num = $last_num + 1;
            }
            $rid = $prefix . str_pad($next_num, 3, '0', STR_PAD_LEFT);
        }

        $stmt = $conn->prepare("UPDATE mess_fees SET status=?, paid_amount=IFNULL(paid_amount, amount), paid_at=IFNULL(paid_at, NOW()), receipt_id=? WHERE id=?");
        $stmt->bind_param("ssi", $new_status, $rid, $fee_id);
    } else {
        $stmt = $conn->prepare("UPDATE mess_fees SET status=?, receipt_id=NULL WHERE id=?");
        $stmt->bind_param("si", $new_status, $fee_id);
    }
    
    if($stmt->execute()){
        $msg = "<p style='color:green; text-align:center;'>Status updated successfully!</p>";
    } else {
        $msg = "<p style='color:red; text-align:center;'>Error updating status: " . $conn->error . "</p>";
    }
}

// Get Students for Dropdown
$students_result = $conn->query("SELECT id, fullname, email FROM users WHERE role = 'student'");

// Get Fee Records
$fees_query = "SELECT m.id, m.user_id as student_user_id, m.student_name, m.month_year, m.amount, m.status, m.paid_at, 
               u.fullname, u.email, s.academic_year, s.college_name, s.department_name, s.parent_mobile, s.student_mobile 
               FROM mess_fees m 
               JOIN users u ON m.user_id = u.id 
               LEFT JOIN students_info s ON u.id = s.user_id 
               ORDER BY m.id DESC";
$fees_result = $conn->query($fees_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Mess Fee - Warden Dashboard</title>
    <link rel="stylesheet" href="warden.css">
    <style>
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; text-align: left; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #d84315; color: white; }
        .btn-submit { background: #d84315; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; font-weight: bold; }
        .btn-submit:hover { background: #bf360c; }
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
            <a href="warden.php">Dashboard Home</a>
            <a href="warden_student_info.php">Update Student Info</a>
            <a href="warden_mess_fee.php" style="background: #fbe9e7;">Update Mess Fee Record</a>
            <a href="warden_gatepass.php">Approve/Reject Gate Pass</a>
            <a href="warden_attendance.php">Mark Attendance</a>
            <a href="warden_complaints.php">Manage Complaints</a>
            <a href="warden_notifications.php">My Notifications</a>
            <a href="warden_reports.php">Monthly Reports</a>
            <a href="warden_profile.php">Warden Profile</a>
            <a href="warden_duties.php">My Duties</a>
            <a href="warden_transport.php">Transport Assistance</a>
        </div>

        <div class="content">
            <h1>Mess Fee Management</h1>
            <p>Record new mess fees and update payment statuses for students.</p>
            
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
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="e.g. 3500" required>
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

            <div class="card">
                <h3>Recent Fee Records</h3>
                <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>College</th>
                            <th>Month-Year</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($fees_result->num_rows > 0): ?>
                            <?php while($row = $fees_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['college_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['month_year']); ?></td>
                                    <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($row['status']); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
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
                                        <form method="POST" action="" style="display:inline-flex; gap:5px;">
                                            <input type="hidden" name="fee_id" value="<?php echo $row['id']; ?>">
                                            <select name="new_status" class="form-control" style="width:auto; padding:2px; font-size:12px;">
                                                <option value="Pending" <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Paid" <?php echo $row['status'] == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                            </select>
                                            <button type="submit" name="update_status" style="background:#0b7a3f; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer; font-size:12px;">Update</button>
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
