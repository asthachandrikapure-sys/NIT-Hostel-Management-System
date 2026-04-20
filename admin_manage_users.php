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

$msg = "";

// Handle User Creation
if(isset($_POST['create_user'])){
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    $college = !empty($_POST['college_type']) ? $_POST['college_type'] : null;
    $dept = !empty($_POST['department']) ? $_POST['department'] : null;

    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, college_name, department_name) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $fullname, $email, $password, $role, $college, $dept);
    
    if($stmt->execute()){
        $msg = "<p style='color:green; text-align:center;'>User created successfully!</p>";
    } else {
        $msg = "<p style='color:red; text-align:center;'>Error: " . $conn->error . "</p>";
    }
}

// Handle User Deletion safely
if(isset($_POST['delete_user'])){
    $id = $_POST['id'];
    if($id != $_SESSION['user_id']){
        $d_stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $d_stmt->bind_param("i", $id);
        $d_stmt->execute();
        $msg = "<p style='color:orange; text-align:center;'>User deleted.</p>";
    }
}

// Fetch All Users (Staff ONLY - excluding students)
$result = $conn->query("SELECT * FROM users WHERE role != 'student' ORDER BY role, fullname");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .form-container, .table-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 25px; }
        input, select { padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 6px; width: 100%; box-sizing: border-box; }
        .btn-create { background: #0b7a3f; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        th { background: #0b7a3f; color: white; }
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
        Manage System Users
    </header>

    <div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar">
            <h2>Modules</h2>
            <a href="admin.php">Dashboard Home</a>
            <a href="admin_student_info.php">Student List</a>
            <a href="admin_manage_users.php" style="background: #e7f3ea;">Manage Staff Users</a>
            <a href="admin_gatepass.php">Gate Pass Approvals</a>
        </div>

        <div class="content">
            <h1>Staff & Role Management</h1>
            <?php echo $msg; ?>

            <div class="form-container">
                <h3>Create New User</h3>
                <form method="POST" action="">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <input type="text" name="fullname" placeholder="Full Name" required>
                        <input type="email" name="email" placeholder="Email (Login ID)" required>
                        <input type="text" name="password" placeholder="Password" required>
                        <select name="role" required onchange="toggleFields(this.value)">
                            <option value="">-- Select Role --</option>
                            <option value="hod">HOD</option>
                            <option value="principal_poly">Polytechnic Principal</option>
                            <option value="principal_engg">Engineering Principal</option>
                            <option value="incharge_poly">Polytechnic Hostel Incharge</option>
                            <option value="incharge_engg">Engineering Hostel Incharge</option>
                            <option value="warden">Warden</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div id="extraFields" style="display:none; grid-template-columns: 1fr 1fr; gap: 15px; margin-top:10px;">
                        <select name="college_type" id="collegeSelect" onchange="updateDepartments()">
                            <option value="">-- Select College --</option>
                            <option value="Polytechnic">Polytechnic</option>
                            <option value="Engineering">Engineering</option>
                        </select>
                        <select name="department" id="deptSelect">
                            <option value="">-- Select Department --</option>
                        </select>
                    </div>

                    <button type="submit" name="create_user" class="btn-create">Create User Account</button>
                </form>
            </div>

            <div class="table-container">
                <h3>Existing Staff Users</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>College</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Password</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                <td><span style="font-weight:bold; font-size:11px;"><?php echo strtoupper($row['role']); ?></span></td>
                                <td><?php echo $row['college_name'] ?? '-'; ?></td>
                                <td><?php echo $row['department_name'] ?? '-'; ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><code style="background: #f5f5f5; padding: 2px 5px; border-radius: 3px;"><?php echo htmlspecialchars($row['password']); ?></code></td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <form action="admin_view_staff.php" method="GET">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" style="background:#2196F3; color:white; border:none; padding:5px 8px; border-radius:4px; cursor:pointer;">VIEW</button>
                                        </form>
                                        <?php if($row['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" action="" onsubmit="return confirm('Delete this user?');">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="delete_user" style="background:#f44336; color:white; border:none; padding:5px 8px; border-radius:4px; cursor:pointer;">DLT</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        const depts = {
            'Polytechnic': ['Civil', 'Computer', 'Electrical', 'E&TC', 'Mechanical'],
            'Engineering': ['Civil', 'Computer', 'Electrical', 'E&TC', 'IT', 'Mechanical']
        };

        function toggleFields(role) {
            const extra = document.getElementById('extraFields');
            if(role === 'hod' || role.includes('principal') || role.includes('incharge')) {
                extra.style.display = 'grid';
            } else {
                extra.style.display = 'none';
            }
        }

        function updateDepartments() {
            const college = document.getElementById('collegeSelect').value;
            const deptSelect = document.getElementById('deptSelect');
            deptSelect.innerHTML = '<option value="">-- Select Department --</option>';
            if (depts[college]) {
                depts[college].forEach(dept => {
                    const opt = document.createElement('option');
                    opt.value = dept;
                    opt.textContent = dept;
                    deptSelect.appendChild(opt);
                });
            }
        }
    </script>
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
