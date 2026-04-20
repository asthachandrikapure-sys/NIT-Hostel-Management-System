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

$user_id = $_SESSION['user_id'];

// Handle Profile Update
if(isset($_POST['update_profile'])){
    $new_mobile = trim($_POST['student_mobile']);
    
    // Ensure students_info record exists for this user safely
    $check_stmt = $conn->prepare("SELECT id FROM students_info WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check = $check_stmt->get_result();
    
    if($check->num_rows == 0) {
        // Fetch user details to create the record
        $u_stmt = $conn->prepare("SELECT fullname, college_name, department_name FROM users WHERE id = ?");
        $u_stmt->bind_param("i", $user_id);
        $u_stmt->execute();
        $u = $u_stmt->get_result()->fetch_assoc();
        
        $ins = $conn->prepare("INSERT INTO students_info (user_id, student_name, college_name, department_name) VALUES (?, ?, ?, ?)");
        $ins->bind_param("isss", $user_id, $u['fullname'], $u['college_name'], $u['department_name']);
        $ins->execute();
    }
    
    // Update mobile number
    $stmt = $conn->prepare("UPDATE students_info SET student_mobile=? WHERE user_id=?");
    $stmt->bind_param("si", $new_mobile, $user_id);
    $stmt->execute();
    
    // Handle Photo Upload separately
    if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0){
        $target_dir = "uploads/profile_photos/";
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if(in_array($file_ext, $allowed_ext)) {
            $file_name = "profile_" . $user_id . "_" . time() . "." . $file_ext;
            $target_file = $target_dir . $file_name;
            
            $check = getimagesize($_FILES["profile_photo"]["tmp_name"]);
            if($check !== false) {
                if(move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                    $photo_stmt = $conn->prepare("UPDATE students_info SET profile_photo=? WHERE user_id=?");
                    $photo_stmt->bind_param("si", $file_name, $user_id);
                    $photo_stmt->execute();
                } else {
                    $error_msg = "Failed to upload photo. Check folder permissions.";
                }
            } else {
                $error_msg = "File is not a valid image.";
            }
        } else {
            $error_msg = "Invalid file type. Allowed: jpg, jpeg, png, gif, webp.";
        }
    }
    // Handle cropped photo (base64 data from Cropper.js)
    elseif(!empty($_POST['cropped_photo_data'])) {
        $target_dir = "uploads/profile_photos/";
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $data = $_POST['cropped_photo_data'];
        // Remove data URL prefix
        if(preg_match('/^data:image\/(\w+);base64,/', $data, $matches)) {
            $ext = $matches[1];
            if($ext == 'jpeg') $ext = 'jpg';
            $data = substr($data, strpos($data, ',') + 1);
            $data = base64_decode($data);
            
            if($data !== false) {
                $file_name = "profile_" . $user_id . "_" . time() . "." . $ext;
                $target_file = $target_dir . $file_name;
                
                if(file_put_contents($target_file, $data)) {
                    $photo_stmt = $conn->prepare("UPDATE students_info SET profile_photo=? WHERE user_id=?");
                    $photo_stmt->bind_param("si", $file_name, $user_id);
                    $photo_stmt->execute();
                } else {
                    $error_msg = "Failed to save cropped photo.";
                }
            }
        }
    }
    
    if(!isset($error_msg)) {
        $success_msg = "Profile updated successfully!";
    }
}

// Fetch Student Profile Data
$query = "SELECT u.fullname, u.email, s.room_no, s.academic_year, s.college_name, s.student_mobile, s.npoly_id, s.profile_photo 
          FROM users u 
          LEFT JOIN students_info s ON u.id = s.user_id 
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile - Student Dashboard</title>
    <link rel="stylesheet" href="student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <style>
        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            text-align: left;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .profile-card h3 {
            color: #0b7a3f;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .profile-info {
            display: flex;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .profile-label {
            font-weight: 600;
            width: 150px;
            color: #555;
        }
        .profile-value {
            color: #333;
        }
        /* Crop Modal */
        .crop-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .crop-modal.active { display: flex; }
        .crop-box {
            background: white;
            border-radius: 16px;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow: auto;
        }
        .crop-box h3 {
            margin: 0 0 15px 0;
            color: #0b7a3f;
            text-align: center;
        }
        .crop-container {
            max-height: 350px;
            overflow: hidden;
            border-radius: 8px;
            background: #f0f0f0;
        }
        .crop-container img {
            display: block;
            max-width: 100%;
        }
        .crop-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .crop-actions button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-crop-save { background: #0b7a3f; color: white; }
        .btn-crop-save:hover { background: #096a35; }
        .btn-crop-cancel { background: #eee; color: #333; }
        .btn-crop-cancel:hover { background: #ddd; }
        .btn-crop-rotate { background: #1a73e8; color: white; font-size: 12px !important; }
        .crop-preview-circle {
            width: 120px; height: 120px;
            border-radius: 50%;
            overflow: hidden;
            margin: 15px auto 0;
            border: 3px solid #0b7a3f;
        }
        .photo-upload-area {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
            background: #fafafa;
        }
        .photo-upload-area:hover { border-color: #0b7a3f; }
        .photo-upload-area p { margin: 5px 0; color: #888; font-size: 14px; }
        .photo-upload-area .icon { font-size: 36px; }
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
            <a href="student_profile.php" style="background: #e7f3ea;">View Profile</a>
            <a href="student_attendance.php">View Attendance Record</a>
            <a href="student_gatepass.php">Gate Pass Request</a>
            <a href="student_complaints.php">Register Complaint</a>
            <a href="student_mess_fee.php">View Mess Fee Record</a>
        </div>

        <div class="content">
            <h1>My Profile</h1>
            <p>Your academic and hostel details.</p>

            <?php if(isset($success_msg)): ?>
                <div style="background: #e7f3ea; color: #0b7a3f; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #0b7a3f;"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if(isset($error_msg)): ?>
                <div style="background: #fdecea; color: #d32f2f; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #d32f2f;"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="profile-card">
                <div style="text-align: center; margin-bottom: 25px;">
                    <?php if(!empty($profile['profile_photo'])): ?>
                        <img src="uploads/profile_photos/<?php echo $profile['profile_photo']; ?>" alt="Profile Photo" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #0b7a3f; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <?php else: ?>
                        <div style="width: 150px; height: 150px; border-radius: 50%; background: #eee; display: flex; align-items: center; justify-content: center; margin: 0 auto; color: #999; border: 4px solid #ddd;">
                            No Photo
                        </div>
                    <?php endif; ?>
                </div>

                <h3>Personal Information</h3>
                <div class="profile-info">
                    <div class="profile-label">Department:</div>
                    <div class="profile-value"><?php echo htmlspecialchars($profile['department_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="profile-info">
                    <div class="profile-label">Full Name:</div>
                    <div class="profile-value"><?php echo htmlspecialchars($profile['fullname']); ?></div>
                </div>
                <div class="profile-info">
                    <div class="profile-label">Email:</div>
                    <div class="profile-value"><?php echo htmlspecialchars($profile['email']); ?></div>
                </div>
                
                <form method="POST" action="" enctype="multipart/form-data" id="profileForm" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
                    <input type="hidden" name="cropped_photo_data" id="croppedPhotoData">
                    <div class="profile-info" style="flex-direction: column; gap: 10px;">
                        <label class="profile-label" style="width: 100%;">Update Profile Photo:</label>
                        <div class="photo-upload-area" onclick="document.getElementById('photoInput').click()">
                            <div class="icon">📷</div>
                            <p>Click to choose a photo</p>
                            <p style="font-size:12px; color:#aaa;">You can crop & adjust after selecting</p>
                        </div>
                        <input type="file" id="photoInput" name="profile_photo" accept="image/*" style="display:none;">
                        <div id="selectedFileName" style="color:#0b7a3f; font-weight:600; text-align:center; display:none;">✅ Photo cropped and ready!</div>
                    </div>
                    <div class="profile-info" style="flex-direction: column; gap: 10px;">
                        <label class="profile-label" style="width: 100%;">My Mobile (WhatsApp):</label>
                        <input type="text" name="student_mobile" value="<?php echo htmlspecialchars($profile['student_mobile'] ?? ''); ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;" required>
                    </div>
                    <button type="submit" name="update_profile" style="background: #0b7a3f; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; width: 100%; margin-top: 10px;">Update Profile</button>
                </form>
                
                <h3 style="margin-top: 30px;">Hostel & Academic Details</h3>
                <div class="profile-info">
                    <div class="profile-label">Room No:</div>
                    <div class="profile-value"><?php echo isset($profile['room_no']) && $profile['room_no'] != '' ? htmlspecialchars($profile['room_no']) : '<span style="color:#999;">Not Assigned</span>'; ?></div>
                </div>
                <div class="profile-info">
                    <div class="profile-label">College:</div>
                    <div class="profile-value"><?php echo isset($profile['college_name']) && $profile['college_name'] != '' ? htmlspecialchars($profile['college_name']) : '<span style="color:#999;">Not Updated</span>'; ?></div>
                </div>
                <div class="profile-info">
                    <div class="profile-label">Academic Year:</div>
                    <div class="profile-value"><?php echo isset($profile['academic_year']) && $profile['academic_year'] != '' ? htmlspecialchars($profile['academic_year']) : '<span style="color:#999;">Not Updated</span>'; ?></div>
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

<!-- Crop Modal -->
<div class="crop-modal" id="cropModal">
    <div class="crop-box">
        <h3>✂️ Adjust Your Photo</h3>
        <div class="crop-container">
            <img id="cropImage" src="" alt="Crop Preview">
        </div>
        <div class="crop-preview-circle" id="cropPreview"></div>
        <div class="crop-actions">
            <button type="button" class="btn-crop-cancel" onclick="closeCropModal()">Cancel</button>
            <button type="button" class="btn-crop-rotate" onclick="cropper.rotate(90)">🔄 Rotate</button>
            <button type="button" class="btn-crop-save" onclick="saveCrop()">✅ Crop & Use</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
let cropper = null;

document.getElementById('photoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
        alert('Please select a valid image file.');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(ev) {
        const img = document.getElementById('cropImage');
        img.src = ev.target.result;
        
        // Open crop modal
        document.getElementById('cropModal').classList.add('active');
        
        // Destroy previous cropper if exists
        if (cropper) cropper.destroy();
        
        // Initialize Cropper.js
        cropper = new Cropper(img, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.8,
            cropBoxResizable: true,
            cropBoxMovable: true,
            preview: '#cropPreview',
            responsive: true,
            guides: true
        });
    };
    reader.readAsDataURL(file);
});

function closeCropModal() {
    document.getElementById('cropModal').classList.remove('active');
    if (cropper) { cropper.destroy(); cropper = null; }
    document.getElementById('photoInput').value = '';
}

function saveCrop() {
    if (!cropper) return;
    
    const canvas = cropper.getCroppedCanvas({
        width: 400,
        height: 400,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });
    
    // Convert to base64 and set in hidden field
    const base64 = canvas.toDataURL('image/jpeg', 0.9);
    document.getElementById('croppedPhotoData').value = base64;
    
    // Clear the file input so it doesn't send the raw file
    document.getElementById('photoInput').value = '';
    
    // Show confirmation
    document.getElementById('selectedFileName').style.display = 'block';
    document.querySelector('.photo-upload-area .icon').textContent = '✅';
    document.querySelector('.photo-upload-area p').textContent = 'Photo cropped! Click to choose a different one.';
    
    closeCropModal();
}
</script>

</body>
</html>
