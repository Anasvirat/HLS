<?php
$path = __DIR__ . '/output/' . basename($_GET['file']);
if (file_exists($path)) {
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    header("Content-Type: " . ($ext === "ts" ? "video/MP2T" : "application/vnd.apple.mpegurl"));
    readfile($path);
} else {
    http_response_code(404);
    echo "File not found.";
}
