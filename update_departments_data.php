<?php
include("db.php");

// 1. Define the fresh department lists
$depts = [
    1 => [ // Engineering
        'Computer Engineering',
        'Information Technology',
        'Civil Engineering',
        'Electrical Engineering',
        'Mechanical Engineering'
    ],
    2 => [ // Polytechnic
        'Computer Engineering',
        'Electrical Engineering',
        'Civil Engineering',
        'Electronics and Telecommunication (EJ)',
        'Mechanical Engineering'
    ]
];

// 2. Clear existing departments (optional, but ensures clean state as per user request)
$conn->query("DELETE FROM departments");
$conn->query("ALTER TABLE departments AUTO_INCREMENT = 1");

// 3. Insert new departments
$stmt = $conn->prepare("INSERT INTO departments (college_id, name) VALUES (?, ?)");

foreach ($depts as $college_id => $list) {
    foreach ($list as $name) {
        $stmt->bind_param("is", $college_id, $name);
        $stmt->execute();
    }
}

echo "Departments updated successfully!\n";
?>
