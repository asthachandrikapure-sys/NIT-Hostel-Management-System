<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("db.php");
include("email_helper.php");

echo "<h3>SMTP Test Diagnostic</h3>";

$test_name = "System Test";
$test_email = "test@example.com";
$college = "Engineering";
$dept = "CSE";
$title = "Diagnostic Test";
$description = "This is a test to check SMTP connectivity.";

echo "Attempting to send email to " . ADMIN_EMAIL_ENGINEERING . "...<br>";

$result = sendComplaintEmail($test_name, $test_email, $college, $dept, $title, $description, $conn);

if ($result) {
    echo "<p style='color:green;'>SUCCESS: Email reported as sent.</p>";
} else {
    echo "<p style='color:red;'>FAILURE: Email could not be sent.</p>";
    // Check if we can get the last error from a global perspective if possible, 
    // but the log file is our best bet.
}

$logFile = __DIR__ . '/smtp_debug.log';
if (file_exists($logFile)) {
    echo "<h4>Debug Log Content:</h4>";
    echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
} else {
    echo "<p style='color:orange;'>Warning: smtp_debug.log was not created. This suggests the script may have crashed or didn't reach the log call.</p>";
}
?>
