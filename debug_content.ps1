$path = "c:\xampp\htdocs\nit_project\warden.html"
$bytes = [System.IO.File]::ReadAllBytes($path)
$str = [System.Text.Encoding]::UTF8.GetString($bytes)

# Check if first 3 bytes are BOM
if ($bytes.Length -ge 3) {
    Write-Host ("First 3 bytes: {0:X2} {1:X2} {2:X2}" -f $bytes[0], $bytes[1], $bytes[2])
}

$target = "onclick=" + [char]34 + "document.querySelector(" + [char]39 + ".sidebar" + [char]39 + ").classList.toggle(" + [char]39 + "open" + [char]39 + ")" + [char]34

Write-Host "Target: $target"
Write-Host "Contains: $($str.Contains($target))"

# Find the substring around 'menu-toggle'
$idx = $str.IndexOf("menu-toggle")
if ($idx -ge 0) {
    $chunk = $str.Substring($idx, [Math]::Min(200, $str.Length - $idx))
    Write-Host "Chunk: $chunk"
    
    # Show the chars around onclick
    $idx2 = $chunk.IndexOf("onclick")
    if ($idx2 -ge 0) {
        for ($i = $idx2; $i -lt [Math]::Min($idx2 + 80, $chunk.Length); $i++) {
            $ch = $chunk[$i]
            $code = [int]$ch
            Write-Host ("{0} (0x{1:X2})" -f $ch, $code)
        }
    }
}
