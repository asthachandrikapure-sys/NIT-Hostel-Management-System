$dir = "c:\xampp\htdocs\nit_project"
$files = Get-ChildItem -Path "$dir\*.php", "$dir\*.html" | Where-Object { $_.Name -notmatch '^student' }
foreach ($f in $files) {
    $content = [System.IO.File]::ReadAllText($f.FullName)
    $newContent = $content -replace '(?m)^\s*<a href="(admin|warden|hod|student)_bharosa\.php".*?</a>\s*\r?\n?', ''
    if ($content -ne $newContent) {
        [System.IO.File]::WriteAllText($f.FullName, $newContent)
        Write-Host "Removed link from $($f.Name)"
    }
}
