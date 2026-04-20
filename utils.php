<?php
/**
 * Utility functions for NIT Hostel Management System
 */

/**
 * Sends a notification to a parent when a student's gate pass is approved.
 * Primarily handles generating formatted messages and can be extended for SMS APIs.
 * 
 * @param string $parent_mobile The parent's mobile number.
 * @param string $student_name The name of the student.
 * @param string $leave_date The date the student is leaving.
 * @param string $return_date The date the student is returning.
 * @return string A formatted WhatsApp URL as a fallback/quick method.
 */
function getParentWhatsAppURL($parent_mobile, $student_name, $leave_date, $return_date) {
    if (empty($parent_mobile)) return "#";
    
    $message = "Hello, this is from NIT Hostel. Your ward " . $student_name . " has been granted a gate pass to leave from " . $leave_date . " and will return by " . $return_date . ".";
    
    // Clean mobile number (keep only digits)
    $clean_mobile = preg_replace('/[^0-9]/', '', $parent_mobile);
    
    // If number starts with 10 digits, assume India and add 91
    if (strlen($clean_mobile) == 10) {
        $clean_mobile = "91" . $clean_mobile;
    }
    
    return "https://wa.me/" . $clean_mobile . "?text=" . urlencode($message);
}

/**
 * Placeholder for automated SMS API integration (e.g., MSG91, Twilio)
 */
function sendAutomatedSMS($mobile, $message) {
    // This is where you would integrate your SMS Gateway API
    // Example:
    /*
    $apiKey = "YOUR_API_KEY";
    $url = "https://api.gateway.com/send?apiKey=$apiKey&to=$mobile&msg=".urlencode($message);
    file_get_contents($url);
    */
    return true; 
}
?>
