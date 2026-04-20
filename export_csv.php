<?php
session_start();
include("db.php");

// Protect page
if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

if(isset($_POST['data_json']) && isset($_POST['filename'])){
    $data = json_decode($_POST['data_json'], true);
    $filename = $_POST['filename'] . "_" . date('Y-m-d') . ".csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if (!empty($data)) {
        // Output headers
        fputcsv($output, array_keys($data[0]));
        
        // Output rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit();
}
?>
