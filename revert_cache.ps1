$projectDir = "c:\xampp\htdocs\nit_project"
$count = 0

$headers = "`r`n`r`n// Prevent browser caching`r`nheader(`"Cache-Control: no-cache, no-store, must-revalidate`");`r`nheader(`"Pragma: no-cache`");`r`nheader(`"Expires: 0`");"

$files = Get-ChildItem -Path "$projectDir\*.php"
$utf8NoBom = New-Object System.Text.UTF8Encoding $false

foreach ($f in $files) {
    $content = [System.IO.File]::ReadAllText($f.FullName)
    
    if ($content.Contains("// Prevent browser caching") -and $content.Contains("must-revalidate")) {
        $content = $content.Replace($headers, "")
        [System.IO.File]::WriteAllText($f.FullName, $content, $utf8NoBom)
        Write-Host "Reverted headers in: $($f.Name)"
        $count++
    }
}

Write-Host "`nReverted $count files."
