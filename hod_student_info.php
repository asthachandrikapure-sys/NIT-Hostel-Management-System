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

// Fetch Students Data filtered by department and college safely
$query = "SELECT u.fullname, u.email, s.room_no, s.academic_year, s.parent_name, s.parent_mobile, s.student_mobile
          FROM users u
          JOIN students_info s ON u.id = s.user_id
          WHERE u.role = 'student' AND s.department_name = ? AND s.college_name = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $dept, $college);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List - HOD Dashboard</title>
    <link rel="stylesheet" href="hod.css">
    <style>
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
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
            <a href="hod_student_info.php" style="background: #e7f3ea;">View Students</a>
            <a href="hod_gatepass.php">Approve/Reject Gate Pass</a>
        </div>

        <div class="content">
            <h1>Students List (<?php echo $dept; ?>)</h1>
            <p>Viewing all students in your department.</p>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Room No.</th>
                            <th>Academic Year</th>
                            <th>Parent Mobile</th>
                            <th>Student Mobile</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['room_no'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['academic_year'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['parent_mobile'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_mobile'] ?? 'N/A'); ?></td>
                                    </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">No students found in your department.</td></tr>
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
