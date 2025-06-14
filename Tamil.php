<?php
set_time_limit(0);

$sourceUrl = "https://ts-j8bh.onrender.com/box.ts?id=4";
$outputDir = __DIR__ . "/output";
$playlistPath = "$outputDir/Tamil.m3u8";
$segmentTime = 5;
$maxSegments = 60;

if (!is_dir($outputDir)) mkdir($outputDir);

// Start FFmpeg in background to continuously segment stream
$ffmpegCmd = sprintf(
    'ffmpeg -hide_banner -loglevel error -i "%s" -c copy -f segment -segment_time %d -segment_list "%s" -segment_format mpegts "%s/seg_%%03d.ts" > /dev/null 2>&1 &',
    $sourceUrl,
    $segmentTime,
    $playlistPath,
    $outputDir
);
exec($ffmpegCmd);
echo "✅ FFmpeg started.\n";

// Begin cleaner loop to maintain latest 5 min of segments
echo "🧹 Segment cleaner running...\n";

while (true) {
    $segments = glob("$outputDir/seg_*.ts");
    usort($segments, fn($a, $b) => filemtime($a) - filemtime($b));

    // Delete old segments beyond the latest 60
    if (count($segments) > $maxSegments) {
        $toDelete = array_slice($segments, 0, count($segments) - $maxSegments);
        foreach ($toDelete as $seg) {
            @unlink($seg);
        }
    }

    // Refresh playlist based on current files
    $validSegments = glob("$outputDir/seg_*.ts");
    usort($validSegments, fn($a, $b) => filemtime($a) - filemtime($b));
    $sequence = 0;

    $m3u8 = "#EXTM3U\n";
    $m3u8 .= "#EXT-X-VERSION:3\n";
    $m3u8 .= "#EXT-X-TARGETDURATION:$segmentTime\n";
    $m3u8 .= "#EXT-X-MEDIA-SEQUENCE:0\n";

    foreach ($validSegments as $file) {
        $m3u8 .= "#EXTINF:$segmentTime,\n" . basename($file) . "\n";
        $sequence++;
    }

    file_put_contents($playlistPath, $m3u8);

    echo "🔄 Updated playlist with " . count($validSegments) . " segments.\n";
    sleep($segmentTime);
}
