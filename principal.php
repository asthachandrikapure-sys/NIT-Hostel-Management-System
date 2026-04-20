<?php
session_start();

// Prevent browser caching for security
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/* Protect Page */
if(!isset($_SESSION['username']) || ($_SESSION['role'] != "principal_poly" && $_SESSION['role'] != "principal_engg")){
    header("Location: login.php");
    exit();
}

$college = ($_SESSION['role'] == 'principal_poly') ? 'Polytechnic' : 'Engineering';
$_SESSION['college_type'] = $college; // Ensure it's set correctly

include("principal.html");
?>
