<?php
$dir = __DIR__;
$files = scandir($dir);
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php' || pathinfo($file, PATHINFO_EXTENSION) === 'html') {
        $content = file_get_contents($dir . '/' . $file);
        $changed = false;
        
        // Skip our new files
        if (in_array($file, ['student_bharosa.php', 'admin_bharosa.php', 'update_sidebar_bharosa.php', 'migrate_bharosa.php'])) continue;
        
        // Add to student sidebars (after transport or reports)
        if (strpos($content, 'student_transport.php') !== false && strpos($content, 'student_bharosa.php') === false) {
            $content = str_replace(
                '<a href="student_transport.php">Transport Assistance</a>',
                '<a href="student_transport.php">Transport Assistance</a>' . "\n" . '            <a href="student_bharosa.php">Bharosa Cell Support</a>',
                $content
            );
            $changed = true;
        } elseif (strpos($content, 'student_reports.php') !== false && strpos($content, 'student_bharosa.php') === false && strpos($content, 'student_transport.php') === false) {
            $content = str_replace(
                '<a href="student_reports.php">View My Reports</a>',
                '<a href="student_reports.php">View My Reports</a>' . "\n" . '            <a href="student_bharosa.php">Bharosa Cell Support</a>',
                $content
            );
            $changed = true;
        }
        
        // Add to admin sidebars (after assign duties)
        if (strpos($content, 'admin_warden_duties.php') !== false && strpos($content, 'admin_bharosa.php') === false) {
            $content = str_replace(
                '<a href="admin_warden_duties.php">Assign Duties</a>',
                '<a href="admin_warden_duties.php">Assign Duties</a>' . "\n" . '            <a href="admin_bharosa.php">Bharosa Cell</a>',
                $content
            );
            $changed = true;
        }
        
        if ($changed) {
            file_put_contents($dir . '/' . $file, $content);
            echo "Updated: $file\n";
        }
    }
}
echo "Done.\n";
?>
