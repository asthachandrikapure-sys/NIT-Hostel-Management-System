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
if(!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['warden', 'admin'])){
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;
if(!$id){
    die("Student ID required.");
}

$student_info_link = ($_SESSION['role'] == 'warden') ? 'warden_student_info.php' : 'admin_student_info.php';

// Handle Delete Request from within Profile
$error_msg = "";
if(isset($_POST['delete_student'])){
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()){
         header("Location: " . $student_info_link . "?msg=deleted");
         exit();
    } else {
         $error_msg = "Error deleting student: " . $conn->error;
    }
}

// Fetch Student Info
$stmt = $conn->prepare("SELECT u.fullname, u.email, u.password, s.* 
                        FROM users u 
                        LEFT JOIN students_info s ON u.id = s.user_id 
                        WHERE u.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if(!$student){
    die("Student not found.");
}

// Fetch Gate Pass History
$stmt = $conn->prepare("SELECT * FROM gate_passes WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$gate_passes = $stmt->get_result();

$css_file = ($_SESSION['role'] == 'warden') ? 'warden.css' : 'admin.css';
$dashboard_link = ($_SESSION['role'] == 'warden') ? 'warden.php' : 'admin.php';
// $student_info_link defined above
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - NIT Hostel</title>
    <link rel="stylesheet" href="<?php echo $css_file; ?>">
    <style>
        .details-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .details-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .detail-item { border-bottom: 1px solid #eee; padding-bottom: 8px; }
        .detail-label { font-size: 12px; color: #666; text-transform: uppercase; font-weight: bold; }
        .detail-value { font-size: 16px; color: #333; margin-top: 4px; }
        .history-section { border-top: 2px solid #eee; padding-top: 20px; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        th { background: #f9f9f9; color: #555; font-size: 13px; text-transform: uppercase; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-Approved { background: #e8f5e9; color: #2e7d32; }
        .status-Rejected { background: #ffebee; color: #c62828; }
        .status-Pending { background: #fff3e0; color: #ef6c00; }
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
        Student Profile: <?php echo htmlspecialchars($student['fullname']); ?>
    </header>

    <div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar">
            <h2>Navigation</h2>
            <a href="<?php echo $dashboard_link; ?>">Dashboard Home</a>
            <a href="<?php echo $student_info_link; ?>" style="background: #e7f3ea;">Back to List</a>
        </div>

        <div class="content">
                <div style="display: flex; gap: 30px; align-items: flex-start; margin-bottom: 30px;">
                    <div class="photo-container">
                        <?php if(!empty($student['profile_photo'])): ?>
                            <img src="uploads/profile_photos/<?php echo $student['profile_photo']; ?>" alt="Profile" style="width: 180px; height: 180px; border-radius: 12px; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <?php else: ?>
                            <div style="width: 180px; height: 180px; border-radius: 12px; background: #eee; display: flex; align-items: center; justify-content: center; color: #999; border: 2px dashed #ccc;">No Photo</div>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h1 style="margin:0;">Complete Information</h1>
                            <div style="display: flex; gap: 10px;">
                                <?php if(in_array($_SESSION['role'], ['warden', 'admin'])): ?>
                                    <a href="warden_edit_student.php?id=<?php echo $id; ?>" class="status-badge" style="background: #1a73e8; color: white; text-decoration: none; padding: 10px 20px; font-size: 14px;">✏️ Update Profile</a>
                                    
                                    <?php if($_SESSION['role'] == 'admin'): ?>
                                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to permanently delete this student account?');" style="margin: 0;">
                                        <button type="submit" name="delete_student" class="status-badge" style="background: #f44336; color: white; border: none; padding: 10px 20px; font-size: 14px; cursor: pointer;">🗑️ Delete Profile</button>
                                    </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if($error_msg) echo "<p style='color:red; font-weight:bold;'>$error_msg</p>"; ?>
                        <div class="details-grid">
                            <div class="detail-item">
                                <div class="detail-label">Email (Login ID)</div>
                                <div class="detail-value"><?php echo htmlspecialchars($student['email']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Password</div>
                                <div class="detail-value"><code style="background: #f5f5f5; padding: 2px 5px;"><?php echo htmlspecialchars($student['password']); ?></code></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">N-Poly ID</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['npoly_id'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['fullname']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">College Type</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['college_name'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Department</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['department_name'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Room Number</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['room_no'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Academic Year</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['academic_year'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact (Student)</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['student_mobile'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Parent Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['parent_name'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Parent Contact</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['parent_mobile'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>

            <div class="history-section">
                <h2>Gate Pass History</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Reason</th>
                                <th>Leave Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th>Applied On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($gate_passes->num_rows > 0): ?>
                                <?php while($row = $gate_passes->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                        <td><?php echo date('d-M-Y', strtotime($row['leave_date'])); ?></td>
                                        <td><?php echo date('d-M-Y', strtotime($row['return_date'])); ?></td>
                                        <td>
                                            <?php 
                                            $status_class = (strpos($row['status'], 'Pending') !== false) ? 'status-Pending' : 'status-'.$row['status'];
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d-M-Y H:i', strtotime($row['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center;">No gate pass history found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
