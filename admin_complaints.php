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
if(isset($_POST['update_status'])){
    $complaint_id = $_POST['complaint_id'];
    $new_status = $_POST['new_status'];
    $new_remark = trim($_POST['new_admin_remark'] ?? '');
    $action_taken = ($new_status == 'Resolved' || $new_status == 'Pending' || $new_status == 'In Progress') ? 'yes' : 'no';
    $handled_by = $_SESSION['username'] . ' (Admin)';
    
    // Fetch existing remarks and append new one with attribution
    $existing = '';
    $fetch_stmt = $conn->prepare("SELECT admin_remarks FROM complaints WHERE id=?");
    $fetch_stmt->bind_param("i", $complaint_id);
    $fetch_stmt->execute();
    $fetch_stmt->bind_result($existing);
    $fetch_stmt->fetch();
    $fetch_stmt->close();
    
    if(!empty($new_remark)) {
        $timestamp = date('d M Y, h:i A');
        $attribution = "[" . $timestamp . "] " . $_SESSION['username'] . " (Admin): " . $new_remark;
        $admin_remarks = !empty($existing) ? $existing . "\n" . $attribution : $attribution;
    } else {
        $admin_remarks = $existing;
    }
    
    $stmt = $conn->prepare("UPDATE complaints SET status=?, action_taken=?, handled_by=?, admin_remarks=? WHERE id=?");
    $stmt->bind_param("ssssi", $new_status, $action_taken, $handled_by, $admin_remarks, $complaint_id);
    
    if($stmt->execute()){
         $msg = "<p style='color:green; text-align:center; font-weight:bold; background:#e8f5e9; padding:10px; border-radius:6px;'>✅ Complaint status and remark updated successfully!</p>";
         $show_alert = "success";
         
         // Notify Super Admin if Pending or Open (No Action)
         if($new_status == 'Pending' || $new_status == 'Open') {
             require_once('email_helper.php');
             $sa_res = $conn->query("SELECT email FROM users WHERE role='management' LIMIT 1");
             $sa_email = ($sa_res && $sa_res->num_rows > 0) ? $sa_res->fetch_assoc()['email'] : SMTP_USER;
             
             if(class_exists('SimpleSMTP')) {
                 $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
                 $subject = "Action Required: Complaint #$complaint_id marked as $new_status";
                 $body = "<h3>Complaint Alert</h3>
                          <p>Complaint ID: <b>$complaint_id</b> requires your attention.</p>
                          <p>Status: <b>$new_status</b></p>
                          <p>Admin Remark: " . nl2br(htmlspecialchars($admin_remarks)) . "</p>
                          <p>Please log into the Super Admin Dashboard to review.</p>";
                 $smtp->send($sa_email, $subject, $body, "Super Admin", "");
             }
         }
         
    } else {
         $msg = "<p style='color:red; text-align:center;'>Error updating status: " . $conn->error . "</p>";
    }
}

// Fetch Complaints - filtered by admin's college_type
$college_type = $_SESSION['college_type'] ?? null;
if ($college_type) {
    $query = "SELECT c.id, c.user_id as student_user_id, u.fullname, u.email, s.room_no, s.course_type, s.academic_year, s.parent_mobile, c.title, c.concerning, c.description, c.status, c.action_taken, c.handled_by, c.admin_remarks, c.created_at 
              FROM complaints c 
              JOIN users u ON c.user_id = u.id 
              LEFT JOIN students_info s ON u.id = s.user_id 
              WHERE c.college_type = ?
              ORDER BY c.created_at DESC";
    $stmt_q = $conn->prepare($query);
    $stmt_q->bind_param("s", $college_type);
    $stmt_q->execute();
    $result = $stmt_q->get_result();
} else {
    $query = "SELECT c.id, c.user_id as student_user_id, u.fullname, u.email, s.room_no, s.course_type, s.academic_year, s.parent_mobile, c.title, c.concerning, c.description, c.status, c.action_taken, c.handled_by, c.admin_remarks, c.created_at 
              FROM complaints c 
              JOIN users u ON c.user_id = u.id 
              LEFT JOIN students_info s ON u.id = s.user_id 
              ORDER BY c.created_at DESC";
    $result = $conn->query($query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Complaints - Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #0b7a3f; color: white; }
        .badge-open { background: #f44336; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-prog { background: #ff9800; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-res { background: #4caf50; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .btn-notify { 
            background: #1a73e8; 
            color: white; 
            padding: 6px 12px; 
            border-radius: 4px; 
            border: none;
            font-size: 11px; 
            font-weight: bold;
            cursor: pointer;
            display: inline-block;
            margin-top: 5px;
        }
        .btn-notify:hover { background: #1557b0; }
        .btn-notify:disabled { background: #a0a0a0; cursor: default; }
        .btn-view-remarks { background: #0b7a3f; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold; }
        .btn-view-remarks:hover { background: #096131; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); position: relative; }
        .modal-header { font-size: 16px; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; color: #333; }
        .modal-body { font-size: 14px; color: #555; max-height: 60vh; overflow-y: auto; background: #f9f9f9; padding: 10px; border-left: 3px solid #0b7a3f; border-radius: 4px; }
        .close-modal { position: absolute; top: 15px; right: 15px; cursor: pointer; font-size: 18px; color: #888; border: none; background: none; }
        .close-modal:hover { color: #f44336; }
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

            <a href="admin_complaints.php" style="background: #e7f3ea;">View Complaints</a>
            <a href="admin_attendance.php">View Attendance Record</a>

            <a href="admin_reports.php">Global Reports</a>
            <a href="admin_warden_duties.php">Assign Duties</a>
</div>

        <div class="content">
            <h1>Hostel Complaints</h1>
            <p>Review and act upon complaints submitted by students.</p>
            
            <?php if(isset($msg)) echo $msg; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date Raised</th>
                            <th>Student (Room)</th>
                            <th>Issue Title</th>
                            <th>Concerning</th>
                            <th>Description & Remarks</th>
                            <th>Status & Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['fullname']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($row['room_no'] ?? 'Unassigned'); ?></small><br>
                                        <small><?php echo htmlspecialchars($row['course_type'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($row['academic_year'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                                    <td><small><?php echo htmlspecialchars($row['concerning'] ?? 'N/A'); ?></small></td>
                                    <td>
                                        <small><?php echo nl2br(htmlspecialchars($row['description'])); ?></small>
                                        <?php if(!empty($row['admin_remarks'])): ?>
                                            <div style="margin-top:8px;">
                                                <button class="btn-view-remarks" type="button" onclick="openRemarksModal(this.nextElementSibling.innerHTML)">View Remarks</button>
                                                <div style="display:none;"><?php echo nl2br(htmlspecialchars($row['admin_remarks'])); ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="margin-bottom: 8px;">
                                            <?php if($row['status'] == 'Open'): ?>
                                                <span class="badge-open">Open</span>
                                            <?php elseif($row['status'] == 'In Progress' || $row['status'] == 'Pending'): ?>
                                                <span class="badge-prog"><?php echo htmlspecialchars($row['status']); ?></span>
                                            <?php else: ?>
                                                <span class="badge-res">Resolved</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if($row['status'] != 'Open'): ?>
                                            <?php 
                                                $msg_text = "Hello " . $row['fullname'] . ", your complaint regarding '" . $row['title'] . "' has been marked as " . strtoupper($row['status']) . ". Please check your dashboard.";
                                                $phone = preg_replace('/[^0-9]/', '', $row['parent_mobile'] ?? '');
                                                if (strlen($phone) == 10) $phone = '91' . $phone;
                                                $wa_url = "https://wa.me/" . $phone . "?text=" . urlencode($msg_text);
                                            ?>
                                            <a href="<?php echo $wa_url; ?>" target="_blank" class="btn-notify" style="text-decoration:none;"
                                                    onclick="logNotification(<?php echo $row['student_user_id']; ?>, 'Complaint', '<?php echo addslashes($msg_text); ?>', 'Student');">
                                                📲 WhatsApp
                                            </a>
                                        <?php endif; ?>
                                        <div style="margin-top:10px;">
                                            <form method="POST" action="admin_complaints.php">
                                                <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                                <select name="new_status" style="padding: 4px; width: 100%; margin-bottom:5px; border:1px solid #ccc; border-radius:4px;">
                                                    <option value="Open" <?php echo $row['status']=='Open'?'selected':''; ?>>Open</option>
                                                    <option value="Pending" <?php echo ($row['status']=='Pending' || $row['status']=='In Progress')?'selected':''; ?>>Pending</option>
                                                    <option value="Resolved" <?php echo $row['status']=='Resolved'?'selected':''; ?>>Resolved</option>
                                                </select>
                                                <textarea name="new_admin_remark" placeholder="Write your remark here..." style="width:100%; padding:4px; font-size:11px; margin-bottom:5px; border:1px solid #ccc; border-radius:4px;" rows="2"></textarea>
                                                <button type="submit" name="update_status" style="background:#2196F3; color:white; border:none; padding:5px 8px; border-radius:4px; cursor:pointer; width:100%;">Set Status & Remark</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">No complaints found. Everything is awesome!</td></tr>
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

<div id="remarksModalOverlay" class="modal-overlay" onclick="if(event.target === this) closeRemarksModal()">
    <div class="modal-content">
        <button class="close-modal" onclick="closeRemarksModal()">✖</button>
        <div class="modal-header">All Admin Remarks</div>
        <div id="modalRemarkText" class="modal-body"></div>
    </div>
</div>

</body>
</html>
