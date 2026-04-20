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

// Handle Update Status
$msg = "";
if(isset($_POST['new_status'])){
    $pass_id = $_POST['pass_id'];
    $new_status = $_POST['new_status'];
    // Update status
    $stmt = $conn->prepare("UPDATE gate_passes SET status=? WHERE id=?");
    $stmt->bind_param("si", $new_status, $pass_id);
    if($stmt->execute()){
         if($new_status == 'Approved'){
             // Fetch details for immediate notification
             $details_query = "SELECT g.student_name, g.leave_date, g.return_date, s.parent_mobile, g.user_id 
                               FROM gate_passes g 
                               LEFT JOIN students_info s ON g.user_id = s.user_id 
                               WHERE g.id = ?";
             $d_stmt = $conn->prepare($details_query);
             $d_stmt->bind_param("i", $pass_id);
             $d_stmt->execute();
             $details = $d_stmt->get_result()->fetch_assoc();
             
             // Fetch Hindi template from DB
             $tpl_result = $conn->query("SELECT template_text FROM message_templates WHERE template_key='gatepass_approved_hi' LIMIT 1");
             if ($tpl_result && $tpl_result->num_rows > 0) {
                 $tpl = $tpl_result->fetch_assoc()['template_text'];
                 $msg_text = str_replace(
                     ['{student_name}', '{leave_date}', '{return_date}'],
                     [$details['student_name'], date('d-M-Y', strtotime($details['leave_date'])), date('d-M-Y', strtotime($details['return_date']))],
                     $tpl
                 );
             } else {
                 // Fallback English message
                 $msg_text = "Gate pass approved for " . $details['student_name'] . ". Leave from " . $details['leave_date'] . ", return by " . $details['return_date'] . ".";
             }
             
             $phone = preg_replace('/[^0-9]/', '', $details['parent_mobile'] ?? '');
             if (strlen($phone) == 10) $phone = '91' . $phone;
             $wa_url = "https://wa.me/" . $phone . "?text=" . urlencode($msg_text);
             
             $msg = "<div style='background: #e8f5e9; padding: 15px; border-radius: 8px; border: 1px solid #4caf50; margin-bottom: 20px; text-align: center;'>";
             $msg .= "<p style='color:green; font-weight:bold; font-size: 18px; margin-bottom: 10px;'>✔️ Gate pass approved for " . htmlspecialchars($details['student_name']) . "!</p>";
             $msg .= "<p style='color:#555; font-size:13px; margin-bottom:10px;'>Hindi WhatsApp message ready to send to parent.</p>";
             $msg .= "<a href='$wa_url' target='_blank' class='btn-notify' style='padding: 10px 20px; font-size: 14px; text-decoration:none; background:#25D366;' onclick='logNotification(" . $details['user_id'] . ", \"GatePass\", \"" . addslashes($msg_text) . "\", \"Parent\");'>📲 WhatsApp</a>";
             $msg .= "</div>";
         } else {
             $msg = "<p style='color:red; text-align:center; padding: 10px; background: #ffebee;'>Gate pass rejected.</p>";
         }
    } else {
         $msg = "<p style='color:red; text-align:center;'>Error updating status: " . $conn->error . "</p>";
    }
}

// Get Pending Requests (Pending Warden Approval)
$pending_query = "SELECT g.id, g.student_name, g.reason, g.leave_date, g.return_date, g.created_at, g.remarks, s.college_name, s.academic_year, s.parent_mobile 
                  FROM gate_passes g 
                  LEFT JOIN students_info s ON g.user_id = s.user_id 
                  WHERE g.status = 'Pending Warden Approval' 
                  ORDER BY g.created_at ASC";
$pending_result = $conn->query($pending_query);

// Get History
$history_query = "SELECT g.id, g.user_id as student_user_id, g.student_name, g.reason, g.leave_date, g.return_date, g.status, g.created_at, g.remarks, s.course_type, s.academic_year, s.parent_mobile 
                  FROM gate_passes g 
                  LEFT JOIN students_info s ON g.user_id = s.user_id 
                  WHERE g.status IN ('Approved', 'Rejected') 
                  ORDER BY g.created_at DESC LIMIT 100";
$history_result = $conn->query($history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve/Reject Gate Pass - Warden Dashboard</title>
    <link rel="stylesheet" href="warden.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #d84315; color: white; }
        .btn-approve { background: #4caf50; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-reject { background: #f44336; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .status-approved { color: green; font-weight: bold; }
        .status-rejected { color: red; font-weight: bold; }
        .remark-box { font-size: 12px; font-style: italic; color: #555; background: #fff5f2; padding: 8px; border-radius: 5px; border-left: 4px solid #d84315; margin-top: 5px; white-space: pre-wrap; }
        .btn-notify { 
            background: #1a73e8; color: white; padding: 6px 12px; border-radius: 4px; border: none; font-size: 12px; font-weight: bold; cursor: pointer; display: inline-block; margin-top: 5px;
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
            <a href="warden_gatepass.php" style="background: #fbe9e7;">Approve/Reject Gate Pass</a>
            <a href="warden_attendance.php">Mark Attendance</a>
            <a href="warden_complaints.php">Manage Complaints</a>
            <a href="warden_notifications.php">My Notifications</a>
            <a href="warden_reports.php">Monthly Reports</a>
            <a href="warden_profile.php">Warden Profile</a>
            <a href="warden_duties.php">My Duties</a>
            <a href="warden_transport.php">Transport Assistance</a>
        </div>

        <div class="content">
            <h1>Level 3: Final Gate Pass Approval</h1>
            <p>Review and finalize gate pass requests after HOD and Incharge clearance.</p>
            
            <?php if(!empty($msg)) echo $msg; ?>

            <h2>Pending Warden Approval</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Parent Contact</th>
                            <th>Reason</th>
                            <th>Dates</th>
                            <th>Final Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pending_result->num_rows > 0): ?>
                            <?php while($row = $pending_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['student_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($row['college_name']); ?> (<?php echo htmlspecialchars($row['academic_year']); ?>)</small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['parent_mobile'] ?? 'N/A'); ?>
                                        <?php if($row['parent_mobile']): ?>
                                            <br><a href="tel:<?php echo $row['parent_mobile']; ?>" style="text-decoration:none; color:#d84315; font-weight:bold; font-size:11px;">📞 Call Parent</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td>
                                        L: <?php echo date('d-M', strtotime($row['leave_date'])); ?><br>
                                        R: <?php echo date('d-M', strtotime($row['return_date'])); ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($row['remarks'])): ?>
                                            <div style="background:#fff3e0; padding:6px 8px; border-radius:5px; border-left:3px solid #ff9800; margin-bottom:8px;">
                                                <small style="color:#e65100; font-weight:bold;">📝 Previous Remarks:</small><br>
                                                <small style="color:#333; white-space:pre-wrap;"><?php echo htmlspecialchars($row['remarks']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                        <form method="POST" action="" style="display:flex; flex-direction:column; gap:8px;">
                                            <input type="hidden" name="pass_id" value="<?php echo $row['id']; ?>">
                                            <div style="display:flex; gap:5px;">
                                                <button type="submit" name="new_status" value="Approved" class="btn-approve" style="flex:1;">Approve</button>
                                                <button type="submit" name="new_status" value="Rejected" class="btn-reject" style="flex:1;">Reject</button>
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

            <h2 style="margin-top:40px;">Request History</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Reason</th>
                            <th>Status</th>
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
                                            <span class="status-approved">Approved</span>
                                        <?php else: ?>
                                            <span class="status-rejected">Rejected</span>
                                        <?php endif; ?>
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
