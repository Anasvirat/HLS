<?php
// Helper: cURL fetch with browser headers
function curl_get($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/114.0.0.0 Safari/537.36',
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// Extract live video ID from a channel ID
function get_live_video_id($channel_id) {
    $url = "https://www.youtube.com/channel/$channel_id/live";
    $html = curl_get($url);
    if (!$html) return false;

    if (preg_match('/"videoId":"(.*?)"/', $html, $match)) {
        return $match[1];
    }

    return false;
}

// Extract M3U8 link from video page
function get_m3u8_url($video_id) {
    $url = "https://www.youtube.com/watch?v=$video_id";
    $html = curl_get($url);
    if (!$html) return false;

    if (preg_match('/"hlsManifestUrl":"(https:[^"]+)"/', $html, $match)) {
        return stripslashes($match[1]);
    }

    return false;
}

// Main
if (isset($_GET['id'])) {
    $channel_id = htmlspecialchars($_GET['id']);
    $video_id = get_live_video_id($channel_id);

    if (!$video_id) {
        header("HTTP/1.1 404 Not Found");
        exit("❌ No active live stream found for channel ID: $channel_id");
    }

    $m3u8_url = get_m3u8_url($video_id);

    if (!$m3u8_url) {
        header("HTTP/1.1 404 Not Found");
        exit("❌ The video is not currently LIVE. Please try again when the channel is broadcasting.");
    }

    $m3u8_content = curl_get($m3u8_url);

    if (!$m3u8_content || strpos($m3u8_content, "#EXTM3U") === false) {
        header("HTTP/1.1 500 Internal Server Error");
        exit("❌ Failed to download valid m3u8 content.");
    }

    // Serve M3U8 content
    header("Content-Type: application/vnd.apple.mpegurl");
    header("Content-Disposition: inline; filename=\"$channel_id.m3u8\"");
    echo $m3u8_content;
    exit;
} else {
    header("Content-Type: text/plain");
    exit("❗ Usage: hls.php?id=YOUR_CHANNEL_ID");
}
?>
