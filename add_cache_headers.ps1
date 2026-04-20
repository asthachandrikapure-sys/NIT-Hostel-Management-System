$projectDir = "c:\xampp\htdocs\nit_project"
$count = 0

$headers = @"

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
"@

$files = Get-ChildItem -Path "$projectDir\*.php"
$utf8NoBom = New-Object System.Text.UTF8Encoding $false

foreach ($f in $files) {
    # Skip these files as they might be API endpoints or already handled
    if ($f.Name -in "db.php", "login.php", "signup.php", "logout.php", "export_csv.php", "download_receipt.php", "process_payment.php", "check_late_fees.php") { continue }
    
    $content = [System.IO.File]::ReadAllText($f.FullName)
    
    # Only add headers if they don't already exist and session_start() is present
    if ($content.Contains("session_start();") -and -not $content.Contains("Cache-Control: no-cache")) {
        $content = $content -replace 'session_start\(\);', ("session_start();`r`n" + $headers)
        [System.IO.File]::WriteAllText($f.FullName, $content, $utf8NoBom)
        Write-Host "Added headers to: $($f.Name)"
        $count++
    }
}

Write-Host "`nFixed $count files."
