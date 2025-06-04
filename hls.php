<?php
// Use curl instead of file_get_contents for better reliability
function curl_get($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// Get the live video ID from channel
function get_live_video_id($channel_id) {
    $url = "https://www.youtube.com/channel/$channel_id/live";
    $html = curl_get($url);
    if (!$html) return false;

    if (preg_match('/"videoId":"(.*?)"/', $html, $match)) {
        return $match[1];
    }

    return false;
}

// Get the HLS manifest (.m3u8) URL from the video page
function get_m3u8_url($video_id) {
    $url = "https://www.youtube.com/watch?v=$video_id";
    $html = curl_get($url);
    if (!$html) return false;

    if (preg_match('/"hlsManifestUrl":"(https:[^"]+)"/', $html, $match)) {
        return stripslashes($match[1]); // Remove escaping
    }

    return false;
}

// Main logic
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

    // Fetch the actual M3U8 content
    $m3u8_content = curl_get($m3u8_url);

    if (!$m3u8_content || strpos($m3u8_content, "#EXTM3U") === false) {
        header("HTTP/1.1 500 Internal Server Error");
        exit("❌ Failed to download valid m3u8 content.");
    }

    // Output the M3U8 content directly
    header('Content-Type: application/vnd.apple.mpegurl');
    header('Content-Disposition: inline; filename="' . $channel_id . '.m3u8"');
    echo $m3u8_content;
    exit;
}
?>
