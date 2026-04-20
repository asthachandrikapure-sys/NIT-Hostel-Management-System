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

// Handle Update Status
$msg = "";
if(isset($_POST['new_status'])){
    $pass_id = $_POST['pass_id'];
    $new_status = $_POST['new_status'];
    $current_status = $_POST['current_status'];
    $remark = trim($_POST['remark'] ?? '');
    
    if($new_status == 'Rejected'){
        $status_to_update = 'Rejected';
    } else {
        // Admin approves, moves to Warden
        $status_to_update = 'Pending Warden Approval';
    }
    
    // Admin override/update with identifying prefix
    $stmt = $conn->prepare("UPDATE gate_passes SET status=?, remarks=CONCAT(COALESCE(remarks,''), '\nAdmin: ', ?) WHERE id=?");
    $stmt->bind_param("ssi", $status_to_update, $remark, $pass_id);
    if($stmt->execute()){
         $msg = "<p style='color:green; text-align:center; padding: 10px; background: #e8f5e9;'>Gate pass status updated successfully!</p>";
    } else {
         $msg = "<p style='color:red; text-align:center;'>Error updating status: " . $conn->error . "</p>";
    }
}

// Get Pending Requests - filtered by admin's college_type
$college_type = $_SESSION['college_type'] ?? null;
if ($college_type) {
    $pending_query = "SELECT g.*, s.college_name, s.academic_year, s.department_name, s.parent_mobile, s.profile_photo 
                      FROM gate_passes g 
                      LEFT JOIN students_info s ON g.user_id = s.user_id 
                      WHERE g.status NOT IN ('Approved', 'Rejected') AND g.college_type = ?
                      ORDER BY FIELD(g.status, 'Pending HOD Approval', 'Pending Hostel In-Charge Approval', 'Pending Admin Approval', 'Pending Warden Approval'), g.created_at ASC";
    $p_stmt = $conn->prepare($pending_query);
    $p_stmt->bind_param("s", $college_type);
} else {
    $pending_query = "SELECT g.*, s.college_name, s.academic_year, s.department_name, s.parent_mobile, s.profile_photo 
                      FROM gate_passes g 
                      LEFT JOIN students_info s ON g.user_id = s.user_id 
                      WHERE g.status NOT IN ('Approved', 'Rejected') 
                      ORDER BY FIELD(g.status, 'Pending HOD Approval', 'Pending Hostel In-Charge Approval', 'Pending Admin Approval', 'Pending Warden Approval'), g.created_at ASC";
    $p_stmt = $conn->prepare($pending_query);
}
$p_stmt->execute();
$pending_result = $p_stmt->get_result();

// Get History - filtered by college_type
if ($college_type) {
    $history_query = "SELECT g.*, s.college_name, s.academic_year, s.department_name 
                      FROM gate_passes g 
                      LEFT JOIN students_info s ON g.user_id = s.user_id 
                      WHERE g.status IN ('Approved', 'Rejected') AND g.college_type = ?
                      ORDER BY g.created_at DESC LIMIT 100";
    $h_stmt = $conn->prepare($history_query);
    $h_stmt->bind_param("s", $college_type);
} else {
    $history_query = "SELECT g.*, s.college_name, s.academic_year, s.department_name 
                      FROM gate_passes g 
                      LEFT JOIN students_info s ON g.user_id = s.user_id 
                      WHERE g.status IN ('Approved', 'Rejected') 
                      ORDER BY g.created_at DESC LIMIT 100";
    $h_stmt = $conn->prepare($history_query);
}
$h_stmt->execute();
$history_result = $h_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Pass Control - Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #0b7a3f; color: white; }
        .btn-approve { background: #4caf50; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .status-w { color: orange; font-weight: bold; font-size: 11px; }
        .status-approved { color: green; font-weight: bold; }
        .status-rejected { color: red; font-weight: bold; }
        .remark-text { font-size: 11px; font-style: italic; color: #666; display: block; background: #f5f5f5; padding: 4px; border-radius: 3px; margin-top: 5px; white-space: pre-wrap; }
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
        Admin Gate Pass Control Hub
    </header>

    <div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar">
            <h2>Modules</h2>
            <a href="admin.php">Dashboard Home</a>
            <a href="admin_student_info.php">Manage Students</a>
            <a href="admin_manage_users.php">Manage Staff Users</a>
            <a href="admin_gatepass.php" style="background: #e7f3ea;">Gate Pass Monitoring</a>
            <a href="admin_reports.php">System Reports</a>
        </div>

        <div class="content">
            <h1>Global Gate Pass Monitor</h1>
            <p>Admin can oversee and intervene in all gate pass approval stages.</p>
            
            <?php if(!empty($msg)) echo $msg; ?>

            <h2>Ongoing Approval Process</h2>
            <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Student Name</th>
                        <th>Parent Contact</th>
                        <th>Reason</th>
                        <th>Dates</th>
                        <th>Status & Action</th>
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
                                    <small><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['parent_mobile'] ?? 'N/A'); ?>
                                    <?php if($row['parent_mobile']): ?>
                                        <br><a href="tel:<?php echo $row['parent_mobile']; ?>" style="text-decoration:none; color:#0b7a3f; font-weight:bold; font-size:11px;">📞 Call Parent</a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td>
                                    <?php echo date('d-M', strtotime($row['leave_date'])); ?> to <?php echo date('d-M', strtotime($row['return_date'])); ?>
                                </td>
                                <td>
                                    <div style="margin-bottom:5px;"><span class="status-w"><?php echo $row['status']; ?></span></div>
                                    <?php if(!empty($row['remarks'])): ?>
                                        <div style="background:#e3f2fd; padding:6px 8px; border-radius:5px; border-left:3px solid #1a73e8; margin-bottom:8px;">
                                            <small style="color:#1a73e8; font-weight:bold;">📝 Previous Remarks:</small><br>
                                            <small style="color:#333; white-space:pre-wrap;"><?php echo htmlspecialchars($row['remarks']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                    <form method="POST" action="" style="display:flex; flex-direction:column; gap:5px;" onsubmit="return true;">
                                        <input type="hidden" name="pass_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $row['status']; ?>">
                                        <textarea name="remark" placeholder="Admin remark..." style="width: 100%; min-height: 40px; padding: 4px; font-size: 10px; border: 1px solid #ccc; border-radius: 4px;" required oninput="const btns = this.parentElement.querySelectorAll('button'); const val = this.value.trim(); btns.forEach(b => b.disabled = val === '');"></textarea>
                                        <div style="display:flex; gap:3px;">
                                            <button type="submit" name="new_status" value="Approved" class="btn-approve" style="flex:1; padding:5px; font-size:11px; font-weight:bold; cursor:pointer;" disabled>Accept</button>
                                            <button type="submit" name="new_status" value="Rejected" style="flex:1; padding:5px; font-size:11px; background:#f44336; color:white; border:none; border-radius:4px; font-weight:bold; cursor:pointer;" disabled>Deny</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No active gate passes.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>

            <h2 style="margin-top:40px;">Completed Gate Passes</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Status</th>
                            <th>Final Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($history_result->num_rows > 0): ?>
                            <?php while($row = $history_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td>
                                        <?php if($row['status'] == 'Approved'): ?>
                                            <span class="status-approved">Approved</span>
                                        <?php else: ?>
                                            <span class="status-rejected">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><div class="remark-text"><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></div></td>
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
<script>
function toggleButtons(textarea) {
    const form = textarea.form;
    const buttons = form.querySelectorAll('button');
    const hasValue = textarea.value.trim().length > 0;
    buttons.forEach(btn => btn.disabled = !hasValue);
}

function validateRemark(form) {
    const remark = form.querySelector('textarea[name="remark"]').value.trim();
    if (remark === '') {
        alert('Please provide a remark before proceeding.');
        return false;
    }
    return true;
}
</script>
</body>
</html>
