<?php
$parts = [
    __DIR__ . '/hero-video-01.b64',
    __DIR__ . '/hero-video-02.b64',
    __DIR__ . '/hero-video-03.b64',
    __DIR__ . '/hero-video-04.b64',
    __DIR__ . '/hero-video-05.b64'
];

$encoded = '';

foreach ($parts as $part) {
    if (!file_exists($part)) {
        http_response_code(404);
        exit;
    }

    $content = file_get_contents($part);
    if ($content === false) {
        http_response_code(500);
        exit;
    }

    $encoded .= preg_replace('/\s+/', '', $content);
}

$video = base64_decode($encoded, true);

if ($video === false) {
    http_response_code(500);
    exit;
}

header('Content-Type: video/mp4');
header('Content-Length: ' . strlen($video));
header('Cache-Control: public, max-age=86400');
header('Accept-Ranges: bytes');

echo $video;
