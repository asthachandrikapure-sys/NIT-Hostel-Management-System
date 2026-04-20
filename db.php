<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn = mysqli_connect("localhost","root","root","hostel_db");

if(!$conn){
    die("Connection Failed: " . mysqli_connect_error());
}
?>