<?php
/**
 * SMTP Email Helper for NIT Hostel Management System
 */
require_once('smtp_client.php');

// Configuration - PLEASE UPDATE THESE WITH COMPLETED GMAIL DETAILS
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'asthachandrikapure@gmail.com'); // The gmail account used to send notifications
define('SMTP_PASS', 'eznjkpypugqfrgcr'); // Gmail App Password (NOT regular password)

// Admin Recipients
define('ADMIN_EMAIL_ENGINEERING', 'vaishnaviwagare417@gmail.com');
define('ADMIN_EMAIL_POLYTECHNIC', 'chandrikapureastha@gmail.com');

/**
 * Sends an email notification when a new complaint is submitted.
 * Recipient email is fetched dynamically from registered admins/principals in the database.
 */
function sendComplaintEmail($student_name, $student_email, $college, $dept, $title, $description, $conn)
{
    // Determine target roles based on college
    $target_roles = [];
    if (strpos($college, 'Engineering') !== false) {
        $target_roles = ['principal_engg'];
    }
    elseif (strpos($college, 'Polytechnic') !== false) {
        $target_roles = ['principal_poly'];
    }

    // Priority 1: Query DB for specific roles matching the college
    $to = null;
    
    $source = "None";

    if (!empty($target_roles)) {
        $role_list = "'" . implode("','", $target_roles) . "'";
        $query = "SELECT email FROM users WHERE role IN ($role_list) LIMIT 1";
        $res = $conn->query($query);
        if ($res && $res->num_rows > 0) {
            $to = $res->fetch_assoc()['email'];
            $source = "DB (Specific Role)";
        }
    }

    // Priority 2: Query DB for general 'admin' assigned to this college
    if (!$to) {
        $stmt = $conn->prepare("SELECT email FROM users WHERE role = 'admin' AND (college_name = ? OR college_name LIKE ?) LIMIT 1");
        $college_wildcard = "%" . trim($college) . "%";
        $stmt->bind_param("ss", $college, $college_wildcard);
        $stmt->execute();
        $res2 = $stmt->get_result();
        if ($row = $res2->fetch_assoc()) {
            $to = $row['email'];
            $source = "DB (General Admin)";
        }
    }

    // Priority 3: Use hardcoded constants as fallback
    if (!$to) {
        if (strpos($college, 'Engineering') !== false) {
            $to = defined('ADMIN_EMAIL_ENGINEERING') ? ADMIN_EMAIL_ENGINEERING : null;
        } elseif (strpos($college, 'Polytechnic') !== false) {
            $to = defined('ADMIN_EMAIL_POLYTECHNIC') ? ADMIN_EMAIL_POLYTECHNIC : null;
        }
        if ($to) $source = "Hardcoded Constant";
    }

    // Priority 4: Final system fallback
    if (!$to) {
        $to = SMTP_USER;
        $source = "System Default (SMTP_USER)";
    }

    // Diagnostic Log
    if (class_exists('SimpleSMTP')) {
        file_put_contents(__DIR__ . '/smtp_debug.log', date('Y-m-d H:i:s') . " - [ROUTING] To: $to | Source: $source | College: $college\n", FILE_APPEND);
    }

    $subject = "New Complaint from " . $student_name . ": " . $title;

    $message = "
    <div style='font-family: Arial, sans-serif; padding: 25px; border: 1px solid #eee; border-top: 6px solid #d32f2f;'>
        <h2 style='color: #333;'>New Student Complaint</h2>
        <p style='background: #fdf2f2; padding: 10px; border-left: 4px solid #d32f2f;'><strong>Title:</strong> {$title}</p>
        
        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 150px;'>Student Name:</td>
                <td style='padding: 8px; border-bottom: 1px solid #eee;'>{$student_name}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;'>Email ID:</td>
                <td style='padding: 8px; border-bottom: 1px solid #eee;'>{$student_email}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;'>College:</td>
                <td style='padding: 8px; border-bottom: 1px solid #eee;'>{$college}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;'>Department:</td>
                <td style='padding: 8px; border-bottom: 1px solid #eee;'>{$dept}</td>
            </tr>
        </table>
        
        <div style='background: #f9f9f9; padding: 15px; border-radius: 5px;'>
            <strong>Description:</strong><br>
            " . nl2br(htmlspecialchars($description)) . "
        </div>
        
        <p style='font-size: 11px; color: #888; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px;'>
            Submitted on: " . date('d-M-Y H:i') . "<br>
            This is an automated notification from <a href='#' style='color: #0b7a3f;'>NIT Hostel Management System</a>.
        </p>
    </div>
    ";

    // Use custom SMTP client for reliable delivery via Gmail
    $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
    return $smtp->send($to, $subject, $message, $student_name . " via NIT Hostel", $student_email);
}
?>
