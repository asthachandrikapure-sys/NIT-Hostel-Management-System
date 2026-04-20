<?php
function robust_replace($file, $pattern, $replacement) {
    if (!file_exists($file)) return;
    $content = file_get_contents($file);
    $new_content = preg_replace($pattern, $replacement, $content);
    if ($new_content !== null && $new_content !== $content) {
        file_put_contents($file, $new_content);
        echo "Fixed $file\n";
    }
}

// 1. Fix admin_attendance.php
robust_replace(
    'admin_attendance.php',
    '/if\s*\(isset\(\$_POST\[\'status\'\]\)\s*&&\s*is_array\(\$_POST\[\'status\'\]\)\)\s*\{.*?\}(?:\s*\$msg\s*=\s*".*?";\s*\}\s*\}\s*\})/s',
    'if(isset($_POST[\'status\']) && is_array($_POST[\'status\'])) {
            foreach($_POST[\'status\'] as $user_id => $status) {
                $student_name = $_POST[\'student_name\'][$user_id]; 
                $user_res = $conn->query("SELECT college_name, department_name FROM users WHERE id = $user_id");
                $user_row = $user_res->fetch_assoc();
                $college = $user_row[\'college_name\'] ?? null;
                $dept = $user_row[\'department_name\'] ?? null;

                $stmt = $conn->prepare("INSERT INTO attendance (user_id, student_name, college_name, department_name, date, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $user_id, $student_name, $college, $dept, $att_date, $status);
                $stmt->execute();
            }
            $msg = "<p style=\'color:green; text-align:center;\'>Attendance recorded for $att_date</p>";
        }
    }
}'
);

// 2. Fix admin_reports.php Query
robust_replace(
    'admin_reports.php',
    '/\$student_query\s*=\s*"SELECT\s*u\.fullname,\s*m\.month_year,\s*m\.status\s*as\s*fee_status,\s*att\.presents,\s*att\.total_days\s*FROM\s*users\s*u\s*JOIN\s*mess_fees\s*m\s*ON\s*u\.id\s*=\s*m\.user_id\s*LEFT\s*JOIN\s*\(\s*SELECT\s*user_id,\s*DATE_FORMAT\(date,\s*\'%M\s*%Y\'\)\s*as\s*att_month,\s*SUM\(CASE\s*WHEN\s*status\s*=\s*\'Present\'\s*THEN\s*1\s*ELSE\s*0\s*END\)\s*as\s*presents,\s*COUNT\(\*\)\s*as\s*total_days\s*FROM\s*attendance\s*GROUP\s*BY\s*user_id,\s*att_month\s*\)\s*att\s*ON\s*u\.id\s*=\s*att\.user_id\s*AND\s*m\.month_year\s*=\s*att\.att_month\s*WHERE\s*u\.role\s*=\s*\'student\'\s*ORDER\s*BY\s*m\.id\s*DESC,\s*u\.fullname\s*ASC";/s',
    '$student_query = "SELECT u.fullname, 
                         s.college_name,
                         s.department_name,
                         m.month_year, 
                         m.status as fee_status,
                         att.presents,
                         att.total_days
                  FROM users u
                  LEFT JOIN students_info s ON u.id = s.user_id
                  JOIN mess_fees m ON u.id = m.user_id
                  LEFT JOIN (
                      SELECT user_id, DATE_FORMAT(date, \'%M %Y\') as att_month,
                             SUM(CASE WHEN status = \'Present\' THEN 1 ELSE 0 END) as presents,
                             COUNT(*) as total_days
                      FROM attendance
                      GROUP BY user_id, att_month
                  ) att ON u.id = att.user_id AND m.month_year = att.att_month
                  WHERE u.role = \'student\'
                  ORDER BY m.id DESC, u.fullname ASC";'
);

// 3. Fix admin_reports.php Table Headings
robust_replace(
    'admin_reports.php',
    '/<tr>\s*<th>Student Name<\/th>\s*<th>Month<\/th>\s*<th>Attendance %<\/th>\s*<th>Mess Fee Status<\/th>\s*<\/tr>/s',
    '<tr>
                            <th>Student Name</th>
                            <th>College</th>
                            <th>Dept</th>
                            <th>Month</th>
                            <th>Attendance %</th>
                            <th>Mess Fee Status</th>
                        </tr>'
);

// 4. Fix admin_reports.php Table Body
robust_replace(
    'admin_reports.php',
    '/<tr>\s*<td><\?php echo htmlspecialchars\(\$row\[\'fullname\'\]\); \?><\/td>\s*<td><\?php echo htmlspecialchars\(\$row\[\'month_year\'\]\); \?><\/td>\s*<td>\s*<span class="<\?php echo \(\$att_percent !== "N\/A" && \$att_percent < 75\) \? \'stat-a\' : \'\'; \?>">\s*<\?php echo \$att_percent; \?><\?php echo \(\$att_percent !== "N\/A"\) \? "%" : ""; \?>\s*<\/span>\s*<\/td>\s*<td>\s*<span style="color: <\?php echo \(\$row\[\'fee_status\'\] == \'Paid\'\) \? \'#2e7d32\' : \'#c62828\'; \?>; font-weight: bold;">\s*<\?php echo htmlspecialchars\(\$row\[\'fee_status\'\]\); \?>\s*<\/span>\s*<\/td>\s*<\/tr>/s',
    '<tr>
                                    <td><?php echo htmlspecialchars($row[\'fullname\']); ?></td>
                                    <td><?php echo htmlspecialchars($row[\'college_name\'] ?? \'N/A\'); ?></td>
                                    <td><?php echo htmlspecialchars($row[\'department_name\'] ?? \'N/A\'); ?></td>
                                    <td><?php echo htmlspecialchars($row[\'month_year\']); ?></td>
                                    <td>
                                        <span class="<?php echo ($att_percent !== "N/A" && $att_percent < 75) ? \'stat-a\' : \'\'; ?>">
                                            <?php echo $att_percent; ?><?php echo ($att_percent !== "N/A") ? "%" : ""; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: <?php echo ($row[\'fee_status\'] == \'Paid\') ? \'#2e7d32\' : \'#c62828\'; ?>; font-weight: bold;">
                                            <?php echo htmlspecialchars($row[\'fee_status\']); ?>
                                        </span>
                                    </td>
                                </tr>'
);
?>
