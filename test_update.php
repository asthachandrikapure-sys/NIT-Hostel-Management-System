<?php
include("db.php");
$user_id = 1; 
$room_no = "101";
$branch = "CSE";
$academic_year = "2ndYear";
$course_type = ""; // Testing empty string for ENUM
$parent_name = "Test Parent";
$parent_mobile = "1234567890";

$stmt = $conn->prepare("UPDATE students_info SET room_no=?, branch=?, academic_year=?, course_type=?, parent_name=?, parent_mobile=? WHERE user_id=?");
$stmt->bind_param("ssssssi", $room_no, $branch, $academic_year, $course_type, $parent_name, $parent_mobile, $user_id);
if($stmt->execute()){
    echo "Update successful";
} else {
    echo "Update failed: " . $stmt->error;
}
?>
