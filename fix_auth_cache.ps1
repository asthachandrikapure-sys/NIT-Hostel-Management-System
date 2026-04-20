$projectDir = "c:\xampp\htdocs\nit_project"
$count = 0

$headers = @"

// Prevent browser caching for security
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
"@

$files = Get-ChildItem -Path "$projectDir\*.php"
$utf8NoBom = New-Object System.Text.UTF8Encoding $false

foreach ($f in $files) {
    if ($f.Name -in "db.php", "logout.php", "login.php", "signup.php", "export_csv.php", "download_receipt.php") { continue }
    
    $content = [System.IO.File]::ReadAllText($f.FullName)
    
    # Check if this is a protected page (has session_start and checks session)
    if ($content.Contains("session_start();") -and $content.Contains("$_SESSION")) {
        
        # Only add if we haven't already added it
        if (-not $content.Contains("Cache-Control: no-store, no-cache")) {
            $content = $content -replace 'session_start\(\);', ("session_start();`r`n" + $headers)
            [System.IO.File]::WriteAllText($f.FullName, $content, $utf8NoBom)
            Write-Host "Added headers to: $($f.Name)"
            $count++
        }
    }
}

Write-Host "`nFixed $count files."
