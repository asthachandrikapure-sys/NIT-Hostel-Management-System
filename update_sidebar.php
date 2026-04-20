<?php
$dir = __DIR__;
$files = scandir($dir);
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php' || pathinfo($file, PATHINFO_EXTENSION) === 'html') {
        $content = file_get_contents($dir . '/' . $file);
        $changed = false;
        
        // Exclude our new files
        if ($file === 'warden_transport.php' || $file === 'student_transport.php') continue;
        
        // Add to warden
        if (strpos($content, '<a href="warden_duties.php">My Duties</a>') !== false && strpos($content, 'warden_transport.php') === false) {
            $content = str_replace('<a href="warden_duties.php">My Duties</a>', '<a href="warden_duties.php">My Duties</a>' . "\n" . '            <a href="warden_transport.php">Transport Assistance</a>', $content);
            $changed = true;
        }
        // Add to student
        if (strpos($content, '<a href="student_reports.php">View My Reports</a>') !== false && strpos($content, 'student_transport.php') === false) {
            $content = str_replace('<a href="student_reports.php">View My Reports</a>', '<a href="student_reports.php">View My Reports</a>' . "\n" . '            <a href="student_transport.php">Transport Assistance</a>
', $content);
            $changed = true;
        }
        
        if ($changed) {
            file_put_contents($dir . '/' . $file, $content);
            echo "Updated sidebar in $file\n";
        }
    }
}
echo "Done updating sidebars.\n";
?>
