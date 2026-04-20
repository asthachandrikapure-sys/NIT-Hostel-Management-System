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
include("email_helper.php");

// Protect page
if(!isset($_SESSION['username']) || $_SESSION['role'] != "student"){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Complaint Registration
$msg = "";
if(isset($_POST['submit_complaint'])){
    $title = trim($_POST['title']);
    $concerning = trim($_POST['concerning']);
    $description = trim($_POST['description']);

    if(!empty($title) && !empty($description)){
        // Fetch fresh details from DB safely
        $query_student = "SELECT u.fullname as name, u.email, s.college_name as college, s.department_name as dept 
                          FROM users u 
                          LEFT JOIN students_info s ON u.id = s.user_id 
                          WHERE u.id = ?";
        $stmt_student = $conn->prepare($query_student);
        $stmt_student->bind_param("i", $user_id);
        $stmt_student->execute();
        $student_data = $stmt_student->get_result()->fetch_assoc();
        
        $student_name = $student_data['name'] ?? $_SESSION['username'];
        $student_email = $student_data['email'] ?? 'N/A';
        $college = $student_data['college'] ?? 'N/A';
        $dept = $student_data['dept'] ?? 'N/A';

        // Determine college_type
        $college_type = null;
        if(stripos($college, 'Polytechnic') !== false || stripos($college, 'poly') !== false) $college_type = 'poly';
        elseif(stripos($college, 'Engineering') !== false || stripos($college, 'engg') !== false) $college_type = 'engineering';

        $stmt = $conn->prepare("INSERT INTO complaints (user_id, student_name, college_name, department_name, college_type, title, concerning, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssssss", $user_id, $student_name, $college, $dept, $college_type, $title, $concerning, $description);
            if($stmt->execute()){
                // Trigger Email Notification
                sendComplaintEmail($student_name, $student_email, $college, $dept, $title, $description, $conn);
                
                $msg = "<p style='color:green;'>Complaint registered successfully! (Admin Notified)</p>";
            } else {
                $msg = "<p style='color:red;'>Failed to register complaint. Try again. Error: " . htmlspecialchars($stmt->error) . "</p>";
            }
            $stmt->close();
        } else {
            $msg = "<p style='color:red;'>Database error: " . htmlspecialchars($conn->error) . "</p>";
        }
    } else {
        $msg = "<p style='color:red;'>Please fill all fields.</p>";
    }
}

// Fetch Past Complaints
$query = "SELECT title, concerning, description, status, created_at FROM complaints WHERE user_id = ? ORDER BY created_at DESC";
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
    <title>Register Complaint - Student Dashboard</title>
    <link rel="stylesheet" href="student.css">
    <style>
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            text-align: left;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input[type="text"], .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: inherit;
        }
        .btn-submit {
            background: #0b7a3f;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-submit:hover {
            background: #095d30;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #0b7a3f;
            color: white;
        }
        .status-open { color: red; font-weight: bold; }
        .status-inprogress { color: orange; font-weight: bold; }
        .status-resolved { color: green; font-weight: bold; }
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
            <a href="student_gatepass.php">Gate Pass Request</a>
            <a href="student_complaints.php" style="background: #e7f3ea;">Register Complaint</a>
            <a href="student_mess_fee.php">View Mess Fee Record</a>
        </div>

        <div class="content">
            <h1>Register Complaint</h1>
            <p>Submit a new complaint regarding hostel issues.</p>
            
            <?php echo $msg; ?>

            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Complaint Subject/Title</label>
                        <input type="text" id="title" name="title" required placeholder="e.g. WiFi not working in Room 102">
                    </div>
                    <div class="form-group">
                        <label for="concerning">Concerning (Target Person/Entity)</label>
                        <input type="text" id="concerning" name="concerning" placeholder="e.g. Mess Manager, Roommate (Optional)">
                    </div>
                    <div class="form-group">
                        <label for="description">Detailed Description</label>
                        <textarea id="description" name="description" rows="4" required placeholder="Describe your issue..."></textarea>
                    </div>
                    <button type="submit" name="submit_complaint" class="btn-submit">Submit Complaint</button>
                </form>
            </div>

            <h2>Past Complaints</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Concerning</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d-M-Y', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><small><?php echo htmlspecialchars($row['concerning'] ?? 'N/A'); ?></small></td>
                                    <td>
                                        <?php 
                                            $sClass = '';
                                            if($row['status'] == 'Open') $sClass = 'status-open';
                                            elseif($row['status'] == 'In Progress') $sClass = 'status-inprogress';
                                            elseif($row['status'] == 'Resolved') $sClass = 'status-resolved';
                                        ?>
                                        <span class="<?php echo $sClass; ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center;">No past complaints found.</td></tr>
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
