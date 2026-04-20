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
if(!isset($_SESSION['username']) || ($_SESSION['role'] != "incharge_poly" && $_SESSION['role'] != "incharge_engg")){
    header("Location: login.php");
    exit();
}

$college = $_SESSION['college_type'];

// Handle Update Status
$msg = "";
if(isset($_POST['new_status'])){
    $pass_id = $_POST['pass_id'];
    $new_status = $_POST['new_status'];
    $remark = trim($_POST['remark'] ?? '');
    
    if($new_status == 'Rejected'){
        $status_to_update = 'Rejected';
    } else {
        // In-Charge approves, moves to Warden
        $status_to_update = 'Pending Warden Approval';
    }
    
    // Append remark to existing remarks
    $stmt = $conn->prepare("UPDATE gate_passes SET status=?, remarks=CONCAT(COALESCE(remarks,''), '\nIncharge: ', ?) WHERE id=?");
    $stmt->bind_param("ssi", $status_to_update, $remark, $pass_id);
    if($stmt->execute()){
         if($status_to_update == 'Pending Warden Approval'){
              $msg = "<p style='color:green; text-align:center; padding: 10px; background: #e8f5e9;'>Gate pass approved! Sent to Warden.</p>";
         } else {
              $msg = "<p style='color:red; text-align:center; padding: 10px; background: #ffebee;'>Gate pass rejected.</p>";
         }
    } else {
         $msg = "<p style='color:red; text-align:center;'>Error updating status: " . $conn->error . "</p>";
    }
}

// Get Pending Requests for this college
$pending_query = "SELECT g.id, g.student_name, g.reason, g.leave_date, g.return_date, g.created_at, g.remarks, s.academic_year, s.department, s.parent_mobile 
                  FROM gate_passes g 
                  JOIN students_info s ON g.user_id = s.user_id 
                  WHERE g.status = 'Pending Hostel In-Charge Approval' AND s.college_type = '$college'
                  ORDER BY g.created_at ASC";
$pending_result = $conn->query($pending_query);

// Get History for this college
$history_query = "SELECT g.id, g.student_name, g.reason, g.status, g.created_at, g.remarks, s.academic_year, s.department 
                  FROM gate_passes g 
                  JOIN students_info s ON g.user_id = s.user_id 
                  WHERE g.status NOT IN ('Pending HOD Approval', 'Pending Hostel In-Charge Approval') AND s.college_type = '$college'
                  ORDER BY g.created_at DESC LIMIT 50";
$history_result = $conn->query($history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Pass Approvals - Incharge Dashboard</title>
    <link rel="stylesheet" href="hostel_incharge.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #0b7a3f; color: white; }
        .btn-approve { background: #4caf50; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-reject { background: #f44336; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .status-w { color: orange; font-weight: bold; }
        .status-approved { color: green; font-weight: bold; }
        .status-rejected { color: red; font-weight: bold; }
        .remark-box { font-size: 12px; font-style: italic; color: #555; background: #f9f9f9; padding: 5px; border-radius: 4px; border-left: 3px solid #0b7a3f; margin-top: 5px; white-space: pre-wrap; }
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
            <a href="hostel_incharge.php">Dashboard Home</a>
            <a href="principal_student_info.php">Student List</a>
            <a href="hostel_incharge_gatepass.php" style="background: #e7f3ea;">Gate Pass Approvals</a>
            <a href="credentials_list.php">Login Credentials</a>
        </div>

        <div class="content">
            <h1>Gate Pass Management</h1>
            <p>Level 2 Review for <strong><?php echo $college; ?></strong> hostel.</p>
            
            <?php if(!empty($msg)) echo $msg; ?>

            <h2>Pending In-Charge Approval</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Parent Contact</th>
                            <th>Reason</th>
                            <th>Dates</th>
                            <th>HOD Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pending_result->num_rows > 0): ?>
                            <?php while($row = $pending_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['student_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($row['department']); ?> (<?php echo htmlspecialchars($row['academic_year']); ?>)</small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['parent_mobile'] ?? 'N/A'); ?>
                                        <?php if($row['parent_mobile']): ?>
                                            <br><a href="tel:<?php echo $row['parent_mobile']; ?>" style="text-decoration:none; color:#0b7a3f; font-weight:bold; font-size:11px;">📞 Call Parent</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td>
                                        L: <?php echo date('d-M', strtotime($row['leave_date'])); ?><br>
                                        R: <?php echo date('d-M', strtotime($row['return_date'])); ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($row['remarks'])): ?>
                                            <div style="background:#e8f5e9; padding:6px 8px; border-radius:5px; border-left:3px solid #4caf50;">
                                                <small style="color:#2e7d32; font-weight:bold;">📝 HOD Remark:</small><br>
                                                <small style="color:#333; white-space:pre-wrap;"><?php echo htmlspecialchars($row['remarks']); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <small style="color:#999;">No remarks</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="" style="display:flex; flex-direction:column; gap:8px;" onsubmit="return validateRemark(this);">
                                            <input type="hidden" name="pass_id" value="<?php echo $row['id']; ?>">
                                            <textarea name="remark" placeholder="Incharge remark..." style="padding:4px; border-radius:4px; border:1px solid #ccc; font-size:11px;" oninput="toggleButtons(this)"></textarea>
                                            <div style="display:flex; gap:5px;">
                                                <button type="submit" name="new_status" value="Approved" class="btn-approve" style="padding:4px 8px; font-size:12px;" disabled>Approve</button>
                                                <button type="submit" name="new_status" value="Rejected" class="btn-reject" style="padding:4px 8px; font-size:12px;" disabled>Reject</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">No pending requests.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2 style="margin-top:40px;">Recent History</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
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
                                        <?php if($row['status'] == 'Approved'): ?>
                                            <span class="status-approved">Fully Approved</span>
                                        <?php elseif($row['status'] == 'Rejected'): ?>
                                            <span class="status-rejected">Rejected</span>
                                        <?php else: ?>
                                            <span class="status-w"><?php echo $row['status']; ?></span>
                                        <?php endif; ?>
                                        <div class="remark-box" style="font-size:11px;"><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></div>
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
