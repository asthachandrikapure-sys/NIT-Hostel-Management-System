<?php
$dir = new DirectoryIterator(__DIR__);
$script = "<script>\n    window.addEventListener(\"pageshow\", function (event) {\n        if (event.persisted) {\n            window.location.reload();\n        }\n    });\n</script>";

foreach ($dir as $fileinfo) {
    if (!$fileinfo->isDot() && $fileinfo->isFile()) {
        $ext = $fileinfo->getExtension();
        if ($ext === "html" || $ext === "php") {
            $content = file_get_contents($fileinfo->getRealPath());
            // Check if it has </head> and doesnt already have the script
            if (stripos($content, "</head>") !== false && stripos($content, "window.addEventListener(\"pageshow\"") === false && stripos($content, "window.addEventListener('pageshow'") === false) {
                $content = str_ireplace("</head>", $script . "\n</head>", $content);
                file_put_contents($fileinfo->getRealPath(), $content);
                echo "Updated " . $fileinfo->getFilename() . "\n";
            }
        }
    }
}
echo "Done.";
?>
