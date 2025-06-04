<?php
function get_live_video_id($channel_id) {
    $url = "https://www.youtube.com/channel/$channel_id/live";
    $html = @file_get_contents($url);
    if (!$html) return false;

    if (preg_match('/"videoId":"(.*?)"/', $html, $match)) {
        return $match[1];
    }

    return false;
}

function get_m3u8_url($video_id) {
    $url = "https://www.youtube.com/watch?v=$video_id";
    $html = @file_get_contents($url);
    if (!$html) return false;

    if (preg_match('/"hlsManifestUrl":"(https:[^"]+)"/', $html, $match)) {
        return stripslashes($match[1]);
    }

    return false;
}

if (isset($_GET['id'])) {
    $channel_id = htmlspecialchars($_GET['id']);
    $video_id = get_live_video_id($channel_id);

    if (!$video_id) {
        header("HTTP/1.1 404 Not Found");
        exit("❌ No live stream found for channel ID: $channel_id");
    }

    $m3u8_url = get_m3u8_url($video_id);

    if (!$m3u8_url) {
        header("HTTP/1.1 404 Not Found");
        exit("❌ Could not find m3u8 URL for video ID: $video_id");
    }

    $m3u8_content = @file_get_contents($m3u8_url);

    if (!$m3u8_content || strpos($m3u8_content, "#EXTM3U") === false) {
        header("HTTP/1.1 500 Internal Server Error");
        exit("❌ Failed to download valid m3u8 content.");
    }

    // Serve the M3U8 content directly with the correct headers
    header('Content-Type: application/vnd.apple.mpegurl');
    header('Content-Disposition: inline; filename="' . $channel_id . '.m3u8"');
    echo $m3u8_content;
    exit;
}
?>
