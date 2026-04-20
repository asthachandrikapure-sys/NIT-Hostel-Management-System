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

// Handle Update Request
if(isset($_POST['update_student'])){
    $user_id = $_POST['user_id'];
    $fullname = trim($_POST['fullname']);
    $npoly_id = trim($_POST['npoly_id']);
    $room_no = trim($_POST['room_no']);
    $dept = trim($_POST['department_name']);
    $academic_year = trim($_POST['academic_year']);
    $college = trim($_POST['college_name']);
    $parent_name = trim($_POST['parent_name']);
    $parent_mobile = trim($_POST['parent_mobile']);
    $student_mobile = trim($_POST['student_mobile']);

    // Update users table for fullname, college, dept
    $stmt_u = $conn->prepare("UPDATE users SET fullname=?, college_name=?, department_name=? WHERE id=?");
    $stmt_u->bind_param("sssi", $fullname, $college, $dept, $user_id);
    $stmt_u->execute();

    // Check if record exists in students_info
    $check = $conn->query("SELECT id FROM students_info WHERE user_id='$user_id'");
    if($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE students_info SET student_name=?, npoly_id=?, room_no=?, department_name=?, academic_year=?, college_name=?, parent_name=?, parent_mobile=?, student_mobile=? WHERE user_id=?");
        $stmt->bind_param("sssssssssi", $fullname, $npoly_id, $room_no, $dept, $academic_year, $college, $parent_name, $parent_mobile, $student_mobile, $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO students_info (user_id, student_name, npoly_id, room_no, department_name, academic_year, college_name, parent_name, parent_mobile, student_mobile) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssssss", $user_id, $fullname, $npoly_id, $room_no, $dept, $academic_year, $college, $parent_name, $parent_mobile, $student_mobile);
    }
    
    if($stmt->execute()){
         $msg_text = "Student profile for $fullname has been updated (Room: $room_no, College: $college).";
         $phone = preg_replace('/[^0-9]/', '', $parent_mobile);
         if (strlen($phone) == 10) $phone = '91' . $phone;
         $wa_url = "https://wa.me/" . $phone . "?text=" . urlencode($msg_text);

         $msg = "<div style='background: #e8f5e9; padding: 15px; border-radius: 8px; border: 1px solid #4caf50; margin-bottom: 20px; text-align: center;'>";
         $msg .= "<p style='color:green; font-weight:bold;'>✔️ Student info updated successfully!</p>";
         $msg .= "<a href='$wa_url' target='_blank' class='btn-notify' style='text-decoration:none;' onclick='logNotification($user_id, \"StudentInfo\", \"" . addslashes($msg_text) . "\", \"Parent\");'>🔔 Notify Parent (WhatsApp)</a>";
         $msg .= "</div>";
    } else {
         $msg = "<p style='color:red; text-align:center;'>Error updating info: " . $conn->error . "</p>";
    }
}

// Fetch Student Info
$stmt = $conn->prepare("SELECT u.fullname, u.email, u.college_name as u_college, u.department_name as u_dept, s.* 
                        FROM users u 
                        LEFT JOIN students_info s ON u.id = s.user_id 
                        WHERE u.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if(!$student){
    die("Student not found.");
}
?>

<?php
$dashboard_link = ($_SESSION['role'] == 'warden') ? 'warden.php' : 'admin.php';
$list_link = ($_SESSION['role'] == 'warden') ? 'warden_student_info.php' : 'admin_student_info.php';
$css_file = ($_SESSION['role'] == 'warden') ? 'warden.css' : 'admin.css';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - <?php echo ucfirst($_SESSION['role']); ?> Dashboard</title>
    <link rel="stylesheet" href="<?php echo $css_file; ?>">
    <style>
        .edit-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .form-control:focus { border-color: #d84315; outline: none; box-shadow: 0 0 5px rgba(216, 67, 21, 0.2); }
        .btn-save { background: #d84315; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; width: 100%; margin-top: 20px; }
        .btn-save:hover { background: #bf360c; }
        .btn-notify { background: #1a73e8; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; display: inline-block; cursor: pointer; }
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
        Edit Profile: <?php echo htmlspecialchars($student['fullname']); ?>
    </header>

    <div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar">
            <h2>Navigation</h2>
            <a href="<?php echo $dashboard_link; ?>">Dashboard Home</a>
            <a href="<?php echo $list_link; ?>">Back to List</a>
            <a href="view_student_details.php?id=<?php echo $id; ?>">Back to Profile</a>
        </div>

        <div class="content">
            <div class="edit-card">
                <h1>Edit Student Information</h1>
                <p>Update student's academic and contact details below.</p>

                <?php if(isset($msg)) echo $msg; ?>

                <form method="POST" action="">
                    <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($student['fullname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address (Read Only)</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" readonly style="background: #f5f5f5;">
                        </div>
                        <div class="form-group">
                            <label>N-Poly ID</label>
                            <input type="text" name="npoly_id" class="form-control" value="<?php echo htmlspecialchars($student['npoly_id'] ?? ''); ?>" placeholder="Enter ID">
                        </div>
                        <div class="form-group">
                            <label>Room Number</label>
                            <input type="text" name="room_no" class="form-control" value="<?php echo htmlspecialchars($student['room_no'] ?? ''); ?>" placeholder="e.g. 101">
                        </div>
                        <div class="form-group">
                            <label>College Name</label>
                            <select name="college_name" class="form-control" required>
                                <option value="Engineering College" <?php echo ($student['college_name'] ?? $student['u_college']) == 'Engineering College' ? 'selected' : ''; ?>>Engineering College</option>
                                <option value="Polytechnic College" <?php echo ($student['college_name'] ?? $student['u_college']) == 'Polytechnic College' ? 'selected' : ''; ?>>Polytechnic College</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department_name" class="form-control" value="<?php echo htmlspecialchars($student['department_name'] ?? $student['u_dept'] ?? ''); ?>" placeholder="e.g. Computer Science">
                        </div>
                        <div class="form-group">
                            <label>Academic Year</label>
                            <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars($student['academic_year'] ?? ''); ?>" placeholder="e.g. 2025-26">
                        </div>
                        <div class="form-group">
                            <label>Student Mobile</label>
                            <input type="text" name="student_mobile" class="form-control" value="<?php echo htmlspecialchars($student['student_mobile'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Parent Name</label>
                            <input type="text" name="parent_name" class="form-control" value="<?php echo htmlspecialchars($student['parent_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Parent Mobile</label>
                            <input type="text" name="parent_mobile" class="form-control" value="<?php echo htmlspecialchars($student['parent_mobile'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" name="update_student" class="btn-save">Save All Changes</button>
                </form>
            </div>
        </div>

    </div>

<script>
function logNotification(studentId, type, message, sentTo) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "log_notification.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send("student_id=" + studentId + "&type=" + type + "&message=" + encodeURIComponent(message) + "&sent_to=" + sentTo);
}
</script>

</body>
</html>
