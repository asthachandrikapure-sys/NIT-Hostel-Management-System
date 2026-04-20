$projectDir = "c:\xampp\htdocs\nit_project"
$count = 0

$target = "onclick=" + [char]34 + "document.querySelector(" + [char]39 + ".sidebar" + [char]39 + ").classList.toggle(" + [char]39 + "open" + [char]39 + ")" + [char]34
$replacement = 'onclick="toggleSidebar()"'

Write-Host "Target pattern: $target"

$utf8NoBom = New-Object System.Text.UTF8Encoding $false

# Get all HTML and PHP files in the directory
$htmlFiles = Get-ChildItem -Path "$projectDir\*.html"
$phpFiles = Get-ChildItem -Path "$projectDir\*.php"
$files = @($htmlFiles) + @($phpFiles)

Write-Host "Total files to check: $($files.Count)"

foreach ($f in $files) {
    $content = [System.IO.File]::ReadAllText($f.FullName)
    
    if ($content.Contains($target)) {
        $content = $content.Replace($target, $replacement)
        [System.IO.File]::WriteAllText($f.FullName, $content, $utf8NoBom)
        Write-Host "FIXED: $($f.Name)"
        $count++
    }
}

Write-Host "`nFixed $count files"
