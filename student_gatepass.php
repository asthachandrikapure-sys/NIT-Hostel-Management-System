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
$student_name = $_SESSION['username'];

// Handle Gate Pass Request
$msg = "";
if(isset($_POST['submit_request'])){
    $reason = trim($_POST['reason']);
    $leave_date = $_POST['leave_date'];
    $return_date = $_POST['return_date'];

    if(!empty($reason) && !empty($leave_date) && !empty($return_date)){
        if(strtotime($leave_date) > strtotime($return_date)) {
            $msg = "<p style='color:red;'>Return date must be after leave date.</p>";
        } else {
            // Fetch college and department directly from users table to ensure match with HOD
            $user_stmt = $conn->prepare("SELECT college_name, department_name FROM users WHERE id=?");
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_row = $user_stmt->get_result()->fetch_assoc();
            $college = trim($user_row['college_name'] ?? '');
            $dept = trim($user_row['department_name'] ?? '');

            $stmt = $conn->prepare("INSERT INTO gate_passes (user_id, student_name, college_name, department_name, reason, leave_date, return_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $user_id, $student_name, $college, $dept, $reason, $leave_date, $return_date);
            
            if($stmt->execute()){
                $msg = "<p style='color:green;'>Gate Pass request submitted successfully!</p>";
            } else {
                $msg = "<p style='color:red;'>Failed to submit request. Try again.</p>";
            }
        }
    } else {
        $msg = "<p style='color:red;'>Please fill all fields.</p>";
    }
}

// Fetch Past Requests
$query = "SELECT id, reason, leave_date, return_date, status, created_at, remarks FROM gate_passes WHERE user_id = ? ORDER BY created_at DESC";
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
    <title>Gate Pass Request - Student Dashboard</title>
    <link rel="stylesheet" href="student.css">
    <style>
        .form-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin: 20px auto; text-align: left; max-width: 600px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .form-group input[type="date"], .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background: #0b7a3f; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #0b7a3f; color: white; }
        .status-pending { color: orange; font-weight: bold; font-size: 11px; }
        .status-approved { color: green; font-weight: bold; }
        .status-rejected { color: red; font-weight: bold; }
        .remark-text { font-size: 11px; font-style: italic; color: #666; display: block; margin-top: 5px; white-space: pre-wrap; background: #f9f9f9; padding: 5px; border-radius: 3px; }
        .btn-viewpass { background: linear-gradient(135deg, #0b7a3f, #14a85e); color: white; border: none; padding: 6px 14px; border-radius: 5px; font-size: 12px; font-weight: bold; text-decoration: none; display: inline-block; }
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
            <a href="student_gatepass.php" style="background: #e7f3ea;">Gate Pass Request</a>
            <a href="student_complaints.php">Register Complaint</a>
            <a href="student_mess_fee.php">View Mess Fee Record</a>
        </div>

        <div class="content">
            <h1>Gate Pass Request</h1>
            <p>Submit a request for leaving the hostel premises.</p>
            
            <?php echo $msg; ?>

            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="reason">Reason for Leave</label>
                        <textarea id="reason" name="reason" rows="3" required placeholder="State your reason..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="leave_date">Leave Date</label>
                        <input type="date" id="leave_date" name="leave_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="return_date">Return Date</label>
                        <input type="date" id="return_date" name="return_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button type="submit" name="submit_request" class="btn-submit">Submit Request</button>
                </form>
            </div>

            <h2>Tracking Your Requests</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Requested On</th>
                            <th>Dates</th>
                            <th>Status & Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d-M-Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <?php echo date('d-M', strtotime($row['leave_date'])); ?> to <?php echo date('d-M', strtotime($row['return_date'])); ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $sClass = '';
                                            if(strpos($row['status'], 'Pending') !== false) $sClass = 'status-pending';
                                            elseif($row['status'] == 'Approved') $sClass = 'status-approved';
                                            elseif($row['status'] == 'Rejected') $sClass = 'status-rejected';
                                        ?>
                                        <span class="<?php echo $sClass; ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                        <?php if($row['remarks']): ?>
                                            <div class="remark-text"><?php echo htmlspecialchars($row['remarks']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 'Approved'): ?>
                                            <a href="view_gatepass.php?id=<?php echo $row['id']; ?>" class="btn-viewpass" target="_blank">🎫 View Pass</a>
                                        <?php elseif(strpos($row['status'], 'Pending') !== false): ?>
                                            <small style="color:#999;">In Approval Path</small>
                                        <?php else: ?>
                                            <small style="color:#999;">—</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No past gate pass requests found.</td></tr>
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
