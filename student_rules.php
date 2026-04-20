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
if(!isset($_SESSION['username']) || $_SESSION['role'] != "student"){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Rules & Regulations - NIT Hostel</title>
    <link rel="stylesheet" href="student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .rules-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-top: 20px;
        }
        .rule-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: transform 0.2s;
        }
        .rule-item:last-child { border-bottom: none; }
        .rule-item:hover {
            transform: translateX(5px);
            background: #fafafa;
        }
        .rule-icon {
            background: #e8f0fe;
            color: #1a73e8;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            flex-shrink: 0;
        }
        .rule-text {
            color: #444;
            line-height: 1.6;
            font-size: 1.05em;
        }
        .rules-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .rules-header h1 {
            color: #1a73e8;
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        .important-note {
            background: #fff3e0;
            border-left: 5px solid #ff9800;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 4px;
            color: #e65100;
            font-weight: 500;
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
            <a href="student.php">Dashboard Home</a>
            <a href="student_profile.php">View Profile</a>
            <a href="student_attendance.php">View Attendance Record</a>
            <a href="student_gatepass.php">Gate Pass Request</a>
            <a href="student_complaints.php">Register Complaint</a>
            <a href="student_mess_fee.php">View Mess Fee Record</a>
            <a href="student_notifications.php">Notifications</a>
            <a href="student_rules.php" style="background: #e8f0fe;">Hostel Rules 📋</a>
        </div>

        <div class="content">
            <div class="rules-header">
                <h1>Girls Hostel Rules & Regulations 📋</h1>
                <p>Please read and follow these guidelines to ensure a safe and disciplined environment.</p>
            </div>

            <div class="important-note">
                <i class="fas fa-exclamation-triangle"></i> Any violation of hostel rules may lead to disciplinary action by hostel authorities.
            </div>

            <div class="rules-container">
                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-broom"></i></div>
                    <div class="rule-text">Students must maintain discipline and cleanliness in the hostel at all times.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-clock"></i></div>
                    <div class="rule-text">All students must return to the hostel before 6:00 PM. Late entry is not allowed without permission.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-bolt"></i></div>
                    <div class="rule-text">Students are not allowed to bring electrical items such as heaters, induction stoves, irons, etc. into their rooms.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="rule-text">Students must attend their college regularly and follow the academic schedule.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-building"></i></div>
                    <div class="rule-text">Students should not stay in rooms during college hours unless they have valid permission.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-door-open"></i></div>
                    <div class="rule-text">If a student wants to go outside the hostel, she must request and get approval through the gate pass system.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-wallet"></i></div>
                    <div class="rule-text">Students must pay their mess fees on time as per hostel rules.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-users"></i></div>
                    <div class="rule-text">Visitors are allowed only during permitted visiting hours and must be registered at the hostel gate.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-handshake"></i></div>
                    <div class="rule-text">Students must respect hostel staff, wardens, and other students.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-volume-mute"></i></div>
                    <div class="rule-text">Silence must be maintained after 10:00 PM to maintain a peaceful environment for study.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-tools"></i></div>
                    <div class="rule-text">Damaging hostel property is strictly prohibited and may result in penalties.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-medkit"></i></div>
                    <div class="rule-text">Students must inform the warden in case of sickness or emergency.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-fire-burner"></i></div>
                    <div class="rule-text">Cooking inside hostel rooms is not allowed.</div>
                </div>

                <div class="rule-item">
                    <div class="rule-icon"><i class="fas fa-sparkles"></i></div>
                    <div class="rule-text">Students must keep their rooms clean and hygienic.</div>
                </div>
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
