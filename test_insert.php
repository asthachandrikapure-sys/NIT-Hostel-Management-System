<?php
include("db.php");
$user_id = 999; // Assume id 999 doesn't exist
$student_name = "New Student";
$room_no = "202";
$branch = "ME";
$academic_year = "3rdYear";
$course_type = "Polytechnic";
$parent_name = "New Parent";
$parent_mobile = "0987654321";

$stmt = $conn->prepare("INSERT INTO students_info (user_id, student_name, room_no, branch, academic_year, course_type, parent_name, parent_mobile) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if(!$stmt){
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("isssssss", $user_id, $student_name, $room_no, $branch, $academic_year, $course_type, $parent_name, $parent_mobile);
if($stmt->execute()){
    echo "Insert successful";
} else {
    echo "Insert failed: " . $stmt->error;
}
// Clean up testing data would be good but I'll just check success
?>
