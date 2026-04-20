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
if(!isset($_SESSION['username']) || $_SESSION['role'] != "hod"){
    header("Location: login.php");
    exit();
}

$dept = $_SESSION['department_name'];
$college = $_SESSION['college_name'];

// Handle Update Status
$msg = "";
if(isset($_POST['action'])){
    $pass_id = $_POST['pass_id'];
    $action = $_POST['action'];
    $remark = trim($_POST['remark'] ?? '');
    
    if($action == 'Reject'){
        $status_to_update = 'Rejected';
    } else {
        // HOD approves, moves to Hostel In-Charge
        $status_to_update = 'Pending Hostel In-Charge Approval';
    }
    
    $stmt = $conn->prepare("UPDATE gate_passes SET status=?, remarks=CONCAT('HOD: ', ?) WHERE id=?");
    $stmt->bind_param("ssi", $status_to_update, $remark, $pass_id);
    if($stmt->execute()){
         if($status_to_update == 'Pending Hostel In-Charge Approval'){
              $msg = "<p style='color:green; text-align:center; padding: 10px; background: #e8f5e9;'>Gate pass approved! Sent to Hostel In-Charge for Level 2 review.</p>";
         } else {
              $msg = "<p style='color:red; text-align:center; padding: 10px; background: #ffebee;'>Gate pass rejected.</p>";
         }
    } else {
         $msg = "<p style='color:red; text-align:center;'>Error updating status: " . $conn->error . "</p>";
    }
}

// Get Pending HOD Requests for this department only
// Match directly on gate_passes columns (which are populated from users table at submission time)
$pending_query = "SELECT g.id, g.student_name, g.reason, g.leave_date, g.return_date, g.created_at, g.status as current_status, s.academic_year, s.parent_mobile, s.profile_photo 
                  FROM gate_passes g 
                  LEFT JOIN students_info s ON g.user_id = s.user_id 
                  WHERE g.status = 'Pending HOD Approval' AND TRIM(g.department_name) = TRIM(?) AND TRIM(g.college_name) = TRIM(?)
                  ORDER BY g.created_at ASC";
$pstmt = $conn->prepare($pending_query);
$pstmt->bind_param("ss", $dept, $college);
$pstmt->execute();
$pending_result = $pstmt->get_result();

// Get History for this department
$history_query = "SELECT g.id, g.student_name, g.reason, g.leave_date, g.return_date, g.status, g.created_at, g.remarks, s.academic_year 
                  FROM gate_passes g 
                  LEFT JOIN students_info s ON g.user_id = s.user_id 
                  WHERE g.status != 'Pending HOD Approval' AND TRIM(g.department_name) = TRIM(?) AND TRIM(g.college_name) = TRIM(?)
                  ORDER BY g.created_at DESC LIMIT 50";
$hstmt = $conn->prepare($history_query);
$hstmt->bind_param("ss", $dept, $college);
$hstmt->execute();
$history_result = $hstmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Pass Approvals - HOD Dashboard</title>
    <link rel="stylesheet" href="hod.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #0b7a3f; color: white; }
        .btn-approve { background: #4caf50; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-approve:hover { background: #388e3c; }
        .btn-reject { background: #f44336; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-reject:hover { background: #d32f2f; }
        .status-w { color: orange; font-weight: bold; }
        .status-approved { color: green; font-weight: bold; }
        .status-rejected { color: red; font-weight: bold; }
        .remark-text { font-size: 12px; font-style: italic; color: #666; display: block; margin-top: 5px; }
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
        Welcome, <?php echo $_SESSION['username']; ?> (HOD - <?php echo $_SESSION['department_name']; ?>) 👋
    </header>

    <div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar">
            <h2>Modules</h2>
            <a href="hod.php">Dashboard Home</a>
            <a href="hod_student_info.php">View Students</a>
            <a href="hod_gatepass.php" style="background: #e7f3ea;">Approve/Reject Gate Pass</a>
        </div>

        <div class="content">
            <h1>Gate Pass Management</h1>
            <p>Review and approve gate passes for <strong><?php echo $dept; ?></strong>.</p>
            
            <?php if(!empty($msg)) echo $msg; ?>

            <h2>Pending Requests</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Student Name</th>
                            <th>Parent Contact</th>
                            <th>Reason</th>
                            <th>Leave Date</th>
                            <th>Return Date</th>
                            <th>Remark & Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pending_result->num_rows > 0): ?>
                            <?php while($row = $pending_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if(!empty($row['profile_photo'])): ?>
                                            <img src="uploads/profile_photos/<?php echo $row['profile_photo']; ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;">
                                        <?php else: ?>
                                            <div style="width: 45px; height: 45px; border-radius: 50%; background: #eee; border: 1px solid #ddd;"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['student_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($row['academic_year'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            $phone = htmlspecialchars($row['parent_mobile'] ?? 'N/A');
                                            echo $phone; 
                                        ?>
                                        <?php if($row['parent_mobile']): ?>
                                            <br><a href="tel:<?php echo $row['parent_mobile']; ?>" style="text-decoration:none; color:#0b7a3f; font-weight:bold; font-size:12px;">📞 Call Parent</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td><?php echo date('d-M-Y', strtotime($row['leave_date'])); ?></td>
                                    <td><?php echo date('d-M-Y', strtotime($row['return_date'])); ?></td>
                                    <td>
                                        <form method="POST" action="" style="display:flex; flex-direction:column; gap:8px;" onsubmit="return true;">
                                            <input type="hidden" name="pass_id" value="<?php echo $row['id']; ?>">
                                            <textarea name="remark" placeholder="Write HOD remark here..." style="width: 100%; min-height: 50px; padding: 5px; font-size: 11px; border: 1px solid #ccc; border-radius: 4px;" required oninput="const btns = this.parentElement.querySelectorAll('button'); const val = this.value.trim(); btns.forEach(b => b.disabled = val === '');"></textarea>
                                        <div style="display:flex; gap:5px; margin-top:5px;">
                                            <button type="submit" name="action" value="Approve" class="btn-approve" style="flex:1;" disabled>Accept</button>
                                            <button type="submit" name="action" value="Reject" style="flex:1; background:#f44336; color:white; border:none; padding:6px; border-radius:4px; font-weight:bold; cursor:pointer;" disabled>Deny</button>
                                        </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">No pending requests.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2 style="margin-top:40px;">Request History</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Reason</th>
                            <th>Status & Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($history_result->num_rows > 0): ?>
                            <?php while($row = $history_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td>
                                        <?php if(strpos($row['status'], 'Approved') !== false || $row['status'] == 'Approved'): ?>
                                            <span class="status-approved"><?php echo $row['status']; ?></span>
                                        <?php elseif($row['status'] == 'Rejected'): ?>
                                            <span class="status-rejected">Rejected</span>
                                        <?php else: ?>
                                            <span class="status-w"><?php echo $row['status']; ?></span>
                                        <?php endif; ?>
                                        <span class="remark-text"><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center;">No history available.</td></tr>
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
