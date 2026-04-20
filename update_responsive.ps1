$projectDir = "c:\xampp\htdocs\nit_project"

# List of all files that need the responsive.css link and updated sidebar toggle
$files = @(
    # HTML files
    "admin.html",
    "student.html",
    "warden.html",
    "hod.html",
    "principal.html",
    "hostel_incharge.html",
    # PHP dashboard/sub-pages
    "admin_add_department.php",
    "admin_attendance.php",
    "admin_bharosa.php",
    "admin_complaints.php",
    "admin_gatepass.php",
    "admin_manage_users.php",
    "admin_mess_fee.php",
    "admin_reports.php",
    "admin_student_info.php",
    "admin_warden_duties.php",
    "student_attendance.php",
    "student_bharosa.php",
    "student_complaints.php",
    "student_gatepass.php",
    "student_mess_fee.php",
    "student_notifications.php",
    "student_profile.php",
    "student_reports.php",
    "student_rules.php",
    "student_transport.php",
    "warden_attendance.php",
    "warden_complaints.php",
    "warden_duties.php",
    "warden_gatepass.php",
    "warden_mess_fee.php",
    "warden_notifications.php",
    "warden_profile.php",
    "warden_reports.php",
    "warden_student_info.php",
    "warden_transport.php",
    "hod_gatepass.php",
    "hod_student_info.php",
    "management_dashboard.php",
    "management_complaints.php",
    "management_staff_actions.php",
    "management_students.php",
    "principal_student_info.php",
    "hostel_incharge_gatepass.php",
    "college_admin_gatepass.php",
    "credentials_list.php",
    "admin_view_staff.php",
    "view_student_details.php",
    "view_gatepass.php",
    "warden_edit_student.php"
)

$updatedCount = 0
$skippedCount = 0

foreach ($file in $files) {
    $path = Join-Path $projectDir $file
    if (-not (Test-Path $path)) {
        Write-Host "SKIP (not found): $file"
        $skippedCount++
        continue
    }
    
    $content = Get-Content $path -Raw
    $changed = $false
    
    # 1. Add responsive.css link if not already present
    if ($content -notmatch 'responsive\.css') {
        # Find the last CSS link or the </head> tag and insert before </head>
        $content = $content -replace '(</head>)', "    <link rel=`"stylesheet`" href=`"responsive.css`">`n`$1"
        $changed = $true
    }
    
    # 2. Add sidebar overlay div right after <div class="dashboard"> if not already present
    if ($content -notmatch 'sidebar-overlay' -and $content -match 'class="dashboard"') {
        $content = $content -replace '(<div class="dashboard">)', "`$1`n    <div class=`"sidebar-overlay`" id=`"sidebarOverlay`"></div>"
        $changed = $true
    }
    
    # 3. Replace old sidebar toggle script with new one that includes overlay + no-scroll
    # Match various patterns of the old script
    $oldScriptPatterns = @(
        # Pattern 1: Multi-line version
        '<script>\s*document\.querySelectorAll\(''.sidebar a''\)\.forEach\(link\s*=>\s*\{\s*link\.addEventListener\(''click'',\s*\(\)\s*=>\s*document\.querySelector\(''.sidebar''\)\.classList\.remove\(''open''\)\);\s*\}\);\s*</script>',
        # Pattern 2: Compact version
        '<script>document\.querySelectorAll\(''.sidebar a''\)\.forEach\(l=>l\.addEventListener\(''click'',\(\)=>document\.querySelector\(''.sidebar''\)\.classList\.remove\(''open''\)\)\);</script>',
        # Pattern 3: With newlines
        '<script>\r?\n\s*document\.querySelectorAll\(''.sidebar a''\)\.forEach\(link\s*=>\s*\{\r?\n\s*link\.addEventListener\(''click'',\s*\(\)\s*=>\s*document\.querySelector\(''.sidebar''\)\.classList\.remove\(''open''\)\);\r?\n\s*\}\);\r?\n\s*</script>'
    )
    
    $newScript = @'
<script>
    function toggleSidebar() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('active');
        document.body.classList.toggle('no-scroll');
    }
    function closeSidebar() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.classList.remove('no-scroll');
    }
    document.querySelectorAll('.sidebar a').forEach(function(link) {
        link.addEventListener('click', closeSidebar);
    });
    var overlay = document.getElementById('sidebarOverlay');
    if (overlay) overlay.addEventListener('click', closeSidebar);
    </script>
'@
    
    # Replace the old sidebar script
    foreach ($pattern in $oldScriptPatterns) {
        if ($content -match $pattern) {
            $content = $content -replace $pattern, $newScript
            $changed = $true
            break
        }
    }
    
    # If the old script wasn't matched by regex, try a simpler string replacement
    if ($content -match "document\.querySelector\('\.sidebar'\)\.classList\.remove\('open'\)" -and $content -notmatch 'closeSidebar') {
        # Find and replace the script block containing the old toggle code
        $content = $content -replace "(?s)<script>\s*document\.querySelectorAll.*?classList\.remove\('open'\).*?</script>", $newScript
        $changed = $true
    }
    
    # 4. Update the hamburger button onclick to use toggleSidebar()
    if ($content -match 'menu-toggle' -and $content -notmatch 'toggleSidebar') {
        $content = $content -replace "onclick=""document\.querySelector\('\.sidebar'\)\.classList\.toggle\('open'\)""", 'onclick="toggleSidebar()"'
        # Also handle single-quote onclick attribute
        $content = $content -replace "onclick='document\.querySelector\(`"\.sidebar`"\)\.classList\.toggle\(`"open`"\)'", 'onclick="toggleSidebar()"'
        $changed = $true
    }
    
    if ($changed) {
        Set-Content -Path $path -Value $content -NoNewline
        Write-Host "UPDATED: $file"
        $updatedCount++
    } else {
        Write-Host "NO CHANGE: $file"
        $skippedCount++
    }
}

Write-Host "`n--- Summary ---"
Write-Host "Updated: $updatedCount files"
Write-Host "Skipped: $skippedCount files"
